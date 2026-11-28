<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Report;
use App\Models\RoadClosure;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_companies_with_a_current_operations_plan_can_open_the_operations_page(): void
    {
        $url = route('company.operations');
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->citizen()->create())->get($url)->assertForbidden();

        $company = $this->company();
        $this->actingAs($company->user)->get($url)->assertRedirect(route('company.subscription'));
        $this->get(route('company.areas'))->assertRedirect(route('company.subscription'));
        $this->get(route('company.reports'))->assertRedirect(route('company.subscription'));

        $this->subscribe($company, 'free', 0);
        $this->get($url)->assertRedirect(route('company.subscription'));

        $this->subscribe($company, 'monitor', 19);
        $this->get($url)->assertRedirect(route('company.subscription'))
            ->assertSessionHas('info', 'سجل الإغلاقات السابقة والملخص التشغيلي متاحان ضمن باقة العمليات.');
        $this->get(route('company.reports'))->assertOk()->assertDontSee($url);

        $subscription = $this->subscribe($company, 'operations', 39);
        $this->get($url)->assertOk();
        $this->get(route('company.dashboard'))->assertSee($url);

        $subscription->update(['ends_at' => now()->subMinute()]);
        $this->get($url)->assertRedirect(route('company.subscription'));
        $area = $company->areas()->create(['area_name' => 'Saved area', 'radius_km' => 5]);
        $this->delete(route('company.areas.destroy', $area))->assertRedirect(route('company.subscription'));
        $this->assertModelExists($area);
    }

    public function test_operations_filters_history_and_counts_while_monitoring_lists_current_closures(): void
    {
        $company = $this->company();
        $this->subscribe($company, 'operations', 39);
        $sahab = $company->areas()->create(['area_name' => 'Sahab', 'radius_km' => 5]);
        $company->areas()->create(['area_name' => 'Marka', 'radius_km' => 5]);
        $foreign = $this->company()->areas()->create(['area_name' => 'Foreign', 'radius_km' => 5]);

        $closures = [
            ['Sahab past closure', 'Sahab', 'closed', -3, -2],
            ['Sahab expired closure', 'Sahab', 'active', -2, -1],
            ['Sahab current closure', 'Sahab', 'active', -1, 1],
            ['Sahab future closure', 'Sahab', 'active', 1, 2],
            ['Sahab earlier closure', 'Sahab', 'closed', -20, -19],
            ['Marka past closure', 'Marka', 'closed', -3, -2],
            ['Foreign past closure', 'Foreign', 'closed', -3, -2],
        ];
        foreach ($closures as [$title, $area, $status, $start, $end]) {
            RoadClosure::create([
                'title' => $title, 'area_name' => $area, 'status' => $status,
                'latitude' => 31.9, 'longitude' => 36.0,
                'starts_at' => now()->addDays($start), 'ends_at' => now()->addDays($end),
            ]);
        }
        foreach ([['Sahab', 'high', 'approved'], ['Sahab', 'low', 'resolved'], ['Sahab', 'critical', 'pending'], ['Marka', 'high', 'approved']] as [$area, $risk, $status]) {
            Report::factory()->create(['area_name' => $area, 'risk_level' => $risk, 'status' => $status]);
        }

        $this->actingAs($company->user)->get(route('company.operations', [
            'area' => $sahab->id, 'from' => today()->subDays(7)->toDateString(), 'to' => today()->toDateString(),
        ]))->assertOk()
            ->assertViewHas('closures', fn ($closures) => $closures->total() === 2)
            ->assertViewHas('reportCount', 2)
            ->assertViewHas('highRiskCount', 1)
            ->assertViewHas('mostAffected', fn ($area) => $area->area_name === 'Sahab' && (int) $area->total === 2)
            ->assertSee('Sahab past closure')->assertSee('Sahab expired closure')
            ->assertDontSee('Sahab current closure')->assertDontSee('Sahab future closure')
            ->assertDontSee('Sahab earlier closure')->assertDontSee('Marka past closure')->assertDontSee('Foreign past closure');

        $this->get(route('company.operations', ['area' => $foreign->id]))->assertNotFound();
        $this->getJson(route('company.operations', [
            'from' => today()->toDateString(), 'to' => today()->subDay()->toDateString(),
        ]))->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->get(route('company.reports'))->assertOk()
            ->assertSee('Sahab current closure')->assertDontSee('Sahab past closure')
            ->assertDontSee('Sahab expired closure')->assertDontSee('Sahab future closure');
    }

    private function company(): Company
    {
        return Company::factory()->for(User::factory()->create(['role' => 'company']))->create();
    }

    private function subscribe(Company $company, string $slug, int $price): Subscription
    {
        $plan = SubscriptionPlan::create([
            'name' => $slug, 'slug' => $slug, 'price' => $price, 'currency' => 'USD', 'interval' => 'month',
        ]);
        $company->subscriptions()->current()->update(['status' => 'expired', 'ends_at' => now()]);

        return $company->subscriptions()->create([
            'subscription_plan_id' => $plan->id, 'status' => 'active',
            'starts_at' => now()->subHour(), 'ends_at' => now()->addMonth(),
        ]);
    }
}
