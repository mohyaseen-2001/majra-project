<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeCheckoutService
{
    public static function configured(): bool
    {
        return str_starts_with((string) config('services.stripe.secret'), 'sk_test_')
            && str_starts_with((string) config('services.stripe.webhook_secret'), 'whsec_');
    }

    public function start(Company $company, SubscriptionPlan $plan): string
    {
        $secret = (string) config('services.stripe.secret');
        if (! self::configured()) {
            throw new RuntimeException('Stripe Test Mode is not configured.');
        }

        $existing = $company->payments()->where('status', 'pending')->whereNotNull('checkout_session_id')
            ->where('created_at', '>=', now()->subDay())->with('subscription')->latest('id')->first();
        if ($existing) {
            try {
                $session = Http::withBasicAuth($secret, '')->connectTimeout(4)->timeout(10)
                    ->get('https://api.stripe.com/v1/checkout/sessions/'.rawurlencode($existing->checkout_session_id))
                    ->throw()->json();
            } catch (ConnectionException|RequestException $exception) {
                throw new RuntimeException('تعذّر التحقق من جلسة الدفع السابقة. حاول بعد قليل.', previous: $exception);
            }

            if ($session['status'] === 'open' && (int) $existing->subscription?->subscription_plan_id === $plan->id) {
                return $session['url'];
            }
            if ($session['status'] !== 'expired') {
                throw new RuntimeException('أكمل جلسة الدفع السابقة أو انتظر انتهاءها قبل اختيار باقة أخرى.');
            }
        }

        $subscription = $company->subscriptions()->create(['subscription_plan_id' => $plan->id, 'status' => 'pending']);
        $payment = $company->payments()->create([
            'subscription_id' => $subscription->id,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'status' => 'pending',
            'provider' => 'stripe',
        ]);
        $minorUnitAmount = (int) round(((float) $plan->price) * ($plan->currency === 'JOD' ? 1000 : 100));
        $params = [
            'mode' => 'payment',
            'payment_method_types[0]' => 'card',
            'expires_at' => now()->addMinutes(31)->timestamp,
            'success_url' => route('payment.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel'),
            'client_reference_id' => (string) $company->id,
            'customer_email' => $company->user->email,
            'metadata[company_id]' => (string) $company->id,
            'metadata[subscription_id]' => (string) $subscription->id,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => strtolower($plan->currency),
            'line_items[0][price_data][unit_amount]' => $minorUnitAmount,
            'line_items[0][price_data][product_data][name]' => 'مَجرى · '.$plan->name,
        ];

        try {
            $session = Http::asForm()->withBasicAuth($secret, '')->connectTimeout(5)->timeout(20)
                ->post('https://api.stripe.com/v1/checkout/sessions', $params)->throw()->json();
            if (empty($session['id']) || empty($session['url'])) {
                throw new RuntimeException('Stripe returned an incomplete Checkout Session.');
            }
            $payment->update(['checkout_session_id' => $session['id']]);

            return $session['url'];
        } catch (\Throwable $exception) {
            $stripeError = $exception instanceof RequestException ? $exception->response : null;
            logger()->warning('Stripe checkout failed', [
                'payment_id' => $payment->id,
                'exception' => $exception::class,
                'stripe_status' => $stripeError?->status(),
                'stripe_error' => $stripeError?->json('error.code'),
            ]);
            $payment->update(['status' => 'failed', 'metadata' => ['error' => 'checkout_session_unavailable']]);
            $subscription->update(['status' => 'failed']);
            $message = $exception instanceof ConnectionException
                ? 'تعذّر الاتصال بخدمة Stripe. حاول الدفع مرة أخرى بعد عودة الاتصال.'
                : 'تعذر إنشاء جلسة الدفع التجريبية. لم يتم تفعيل الاشتراك.';
            throw new RuntimeException($message, previous: $exception);
        }
    }

    public function completePaidSession(array $session, ?int $completedAt = null): void
    {
        if (! in_array($session['mode'] ?? null, ['payment', 'subscription'], true) || ($session['payment_status'] ?? null) !== 'paid') {
            return;
        }
        if (! is_string($session['id'] ?? null) || $session['id'] === '') {
            return;
        }

        DB::transaction(function () use ($session, $completedAt): void {
            $payment = Payment::query()->where('checkout_session_id', $session['id'])->lockForUpdate()->first();
            $subscription = $payment?->subscription;
            if (! $payment || $payment->status !== 'pending' || ! $subscription || $subscription->status !== 'pending') {
                return;
            }

            $start = $completedAt ? Carbon::createFromTimestamp($completedAt, config('app.timezone')) : now();
            $end = $subscription->plan->interval === 'year'
                ? $start->copy()->addYearNoOverflow()
                : $start->copy()->addMonthNoOverflow();
            $payment->update([
                'status' => 'paid',
                'payment_intent_id' => is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : null,
                'paid_at' => $start,
            ]);
            $subscription->update([
                'status' => 'active', 'stripe_customer_id' => $session['customer'] ?? null,
                'stripe_subscription_id' => $session['subscription'] ?? null,
                'starts_at' => $start, 'ends_at' => $end,
            ]);
        });
    }
}
