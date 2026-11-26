<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function start(Request $request, StripeCheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:subscription_plans,id']]);
        $plan = SubscriptionPlan::query()->where('is_active', true)->findOrFail($data['plan_id']);
        if ((float) $plan->price <= 0) {
            $company = $request->user()->company()->firstOrCreate([], ['name' => $request->user()->name, 'status' => 'active']);
            $company->subscriptions()->updateOrCreate(
                ['status' => 'active'],
                ['subscription_plan_id' => $plan->id, 'starts_at' => now(), 'ends_at' => null],
            );

            return redirect()->route('company.subscription')->with('success', 'تم تفعيل الباقة المجانية.');
        }

        $company = $request->user()->company()->firstOrCreate([], ['name' => $request->user()->name, 'status' => 'active']);
        try {
            return redirect()->away($checkout->start($company, $plan));
        } catch (\RuntimeException $exception) {
            return redirect()->route('company.subscription')->with('error', $exception->getMessage());
        }
    }
}
