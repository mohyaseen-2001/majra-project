<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\StripeCheckoutService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckoutController extends Controller
{
    public function start(Request $request, StripeCheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:subscription_plans,id']]);
        $plan = SubscriptionPlan::query()->where('is_active', true)->findOrFail($data['plan_id']);
        $company = $request->user()->company()->firstOrCreate([], ['name' => $request->user()->name, 'status' => 'active']);
        try {
            return Cache::lock('company-checkout:'.$company->id, 45)->block(2, function () use ($checkout, $company, $plan) {
                if ($company->subscriptions()->current()->paidPlan()->exists()) {
                    return redirect()->route('company.subscription')->with('info', 'يمكنك اختيار باقة جديدة بعد انتهاء اشتراكك الحالي.');
                }

                if ((float) $plan->price <= 0) {
                    $company->subscriptions()->updateOrCreate(
                        ['subscription_plan_id' => $plan->id, 'status' => 'active'],
                        ['starts_at' => now(), 'ends_at' => null],
                    );

                    return redirect()->route('company.subscription')->with('success', 'تم تفعيل الباقة المجانية.');
                }

                return redirect()->away($checkout->start($company, $plan));
            });
        } catch (LockTimeoutException) {
            return redirect()->route('company.subscription')->with('info', 'هناك طلب دفع قيد الإعداد. حاول بعد قليل.');
        } catch (\RuntimeException $exception) {
            return redirect()->route('company.subscription')->with('error', $exception->getMessage());
        }
    }
}
