<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
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
            'mode' => 'subscription',
            'success_url' => route('payment.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel'),
            'client_reference_id' => (string) $company->id,
            'customer_email' => $company->user->email,
            'metadata[company_id]' => (string) $company->id,
            'metadata[subscription_id]' => (string) $subscription->id,
            'subscription_data[metadata][subscription_id]' => (string) $subscription->id,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => strtolower($plan->currency),
            'line_items[0][price_data][unit_amount]' => $minorUnitAmount,
            'line_items[0][price_data][recurring][interval]' => $plan->interval === 'year' ? 'year' : 'month',
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
            $payment->update(['status' => 'failed', 'metadata' => ['error' => 'checkout_session_unavailable']]);
            $subscription->update(['status' => 'failed']);
            throw new RuntimeException('تعذر إنشاء جلسة الدفع التجريبية. لم يتم تفعيل الاشتراك.', previous: $exception);
        }
    }

    public function completePaidSession(array $session): void
    {
        if (($session['mode'] ?? null) !== 'subscription' || ($session['payment_status'] ?? null) !== 'paid') {
            return;
        }
        $payment = Payment::query()->where('checkout_session_id', $session['id'] ?? '')->with('subscription')->first();
        if (! $payment || ! $payment->subscription) {
            return;
        }

        $payment->update([
            'status' => 'paid',
            'payment_intent_id' => is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : null,
            'paid_at' => now(),
        ]);
        $payment->subscription->update([
            'status' => 'active', 'stripe_customer_id' => $session['customer'] ?? null,
            'stripe_subscription_id' => $session['subscription'] ?? null, 'starts_at' => now(),
        ]);
    }
}
