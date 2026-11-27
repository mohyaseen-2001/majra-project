<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_signed_paid_session_activates_the_subscription_once(): void
    {
        config(['services.stripe.secret' => 'sk_test_demo', 'services.stripe.webhook_secret' => 'whsec_demo']);
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::create(['slug' => 'demo', 'name' => 'Demo', 'currency' => 'USD', 'price' => 19]);
        $subscription = $company->subscriptions()->create(['subscription_plan_id' => $plan->id, 'status' => 'pending']);
        $payment = $company->payments()->create([
            'subscription_id' => $subscription->id, 'amount' => 19, 'currency' => 'USD',
            'provider' => 'stripe', 'status' => 'pending', 'checkout_session_id' => 'cs_test_demo',
        ]);

        $body = [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_demo', 'mode' => 'subscription', 'payment_status' => 'paid']],
        ];
        $this->postJson('/api/stripe/webhook', $body)->assertStatus(400);
        $this->assertSame('pending', $subscription->fresh()->status);

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.json_encode($body), 'whsec_demo');
        $headers = ['Stripe-Signature' => "t=$timestamp,v1=$signature"];
        $this->postJson('/api/stripe/webhook', $body, $headers)->assertNoContent();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);

        $startsAt = $subscription->fresh()->starts_at;
        $this->travel(10)->seconds();
        $this->postJson('/api/stripe/webhook', $body, $headers)->assertNoContent();
        $this->assertEquals($startsAt, $subscription->fresh()->starts_at);
    }
}
