<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripeActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_one_card_session_and_reuses_it_until_expiration(): void
    {
        config(['services.stripe.secret' => 'sk_test_demo', 'services.stripe.webhook_secret' => 'whsec_demo']);
        $company = Company::factory()->create();
        $company->user->update(['role' => 'company']);
        $plan = SubscriptionPlan::create(['slug' => 'paid', 'name' => 'Paid', 'currency' => 'USD', 'price' => 19]);
        $other = SubscriptionPlan::create(['slug' => 'other', 'name' => 'Other', 'currency' => 'USD', 'price' => 39]);
        $url = 'https://checkout.stripe.com/demo-session';
        $session = ['id' => 'cs_test_demo', 'url' => $url, 'status' => 'open'];

        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions/cs_test_demo' => Http::response($session),
            'https://api.stripe.com/v1/checkout/sessions' => Http::response($session),
        ]);

        $this->actingAs($company->user)->post(route('company.checkout'), ['plan_id' => $plan->id])->assertRedirect($url);
        $this->post(route('company.checkout'), ['plan_id' => $plan->id])->assertRedirect($url);
        $this->post(route('company.checkout'), ['plan_id' => $other->id])
            ->assertRedirect(route('company.subscription'))->assertSessionHas('error');

        Http::assertSent(function (Request $request): bool {
            parse_str($request->body(), $body);

            return $request->method() === 'POST'
                && ($body['mode'] ?? null) === 'payment' && ($body['allowed_payment_method_types'][0] ?? null) === 'card';
        });
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['checkout_session_id' => 'cs_test_demo', 'amount' => 19, 'status' => 'pending']);
    }

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
            'created' => strtotime('2026-01-31 12:00:00 UTC'),
            'data' => ['object' => ['id' => 'cs_test_demo', 'mode' => 'payment', 'payment_status' => 'paid']],
        ];
        $this->postJson('/api/stripe/webhook', $body)->assertStatus(400);
        $this->assertSame('pending', $subscription->fresh()->status);

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.json_encode($body), 'whsec_demo');
        $headers = ['Stripe-Signature' => "t=$timestamp,v1=$signature"];
        $this->postJson('/api/stripe/webhook', $body, $headers)->assertNoContent();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame('2026-01-31 12:00:00', $subscription->fresh()->starts_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-28 12:00:00', $subscription->fresh()->ends_at->utc()->format('Y-m-d H:i:s'));

        $startsAt = $subscription->fresh()->starts_at;
        $this->travel(10)->seconds();
        $this->postJson('/api/stripe/webhook', $body, $headers)->assertNoContent();
        $this->assertEquals($startsAt, $subscription->fresh()->starts_at);
    }

    public function test_company_cannot_start_checkout_during_a_paid_period(): void
    {
        $company = Company::factory()->create();
        $company->user->update(['role' => 'company']);
        $plan = SubscriptionPlan::create(['slug' => 'paid', 'name' => 'Paid', 'currency' => 'USD', 'price' => 19]);
        $subscription = $company->subscriptions()->create([
            'subscription_plan_id' => $plan->id, 'status' => 'active',
            'starts_at' => now(), 'ends_at' => now()->addMonthNoOverflow(),
        ]);
        Http::preventStrayRequests();

        $this->actingAs($company->user)->post(route('company.checkout'), ['plan_id' => $plan->id])
            ->assertRedirect(route('company.subscription'))->assertSessionHas('info');
        $this->get(route('company.subscription'))->assertOk()
            ->assertSee($subscription->ends_at->timezone('Asia/Amman')->format('Y-m-d H:i'));
        Http::assertNothingSent();
    }
}
