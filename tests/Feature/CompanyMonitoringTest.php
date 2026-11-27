<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Company;
use App\Models\Report;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_company_sees_nearby_reports_and_area_alerts(): void
    {
        $company = $this->company();
        $plan = SubscriptionPlan::create(['slug' => 'demo', 'name' => 'Demo', 'price' => 19, 'currency' => 'USD']);
        $subscription = $company->subscriptions()->create(['subscription_plan_id' => $plan->id, 'status' => 'active']);
        $company->areas()->create([
            'area_name' => 'Warehouse', 'center_latitude' => 31.95,
            'center_longitude' => 35.91, 'radius_km' => 5, 'alerts_enabled' => true,
        ]);
        Report::factory()->create(['title' => 'Report inside radius', 'area_name' => 'Other label', 'status' => 'approved', 'latitude' => 31.96, 'longitude' => 35.91]);
        Report::factory()->create(['title' => 'Report outside radius', 'area_name' => 'Warehouse', 'status' => 'approved', 'latitude' => 32.15, 'longitude' => 35.91]);
        Alert::create(['title' => 'Warehouse test alert', 'message' => 'Test only', 'area_name' => 'Warehouse', 'severity' => 'high', 'is_active' => true]);

        $this->actingAs($company->user)->get(route('company.dashboard'))
            ->assertOk()->assertSee('Report inside radius')->assertDontSee('Report outside radius')->assertSee('Warehouse test alert');
        $this->get(route('company.reports'))->assertOk()->assertSee('Report inside radius')->assertDontSee('Report outside radius');

        $subscription->update(['ends_at' => now()->subMinute()]);
        $this->get(route('company.reports'))->assertRedirect(route('company.subscription'));
        $this->post(route('company.areas.store'), ['area_name' => 'Other', 'radius_km' => 5])->assertRedirect(route('company.subscription'));
    }

    private function company(): Company
    {
        return Company::factory()->for(User::factory()->create(['role' => 'company']))->create();
    }
}
