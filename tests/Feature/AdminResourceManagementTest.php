<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Report;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_road_closures_and_alerts(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/admin/road-closures', [
            'title' => '[عرض تجريبي] إغلاق شارع الوادي',
            'area_name' => 'عمّان',
            'status' => 'active',
            'latitude' => 31.95,
            'longitude' => 35.91,
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/api/admin/alerts', [
            'title' => '[عرض تجريبي] تحذير من ارتفاع منسوب المياه',
            'message' => 'بيانات توضيحية وليست حالة ميدانية حقيقية.',
            'severity' => 'warning',
            'area_name' => 'عمّان',
            'source' => 'demo',
        ])->assertCreated();
    }

    public function test_admin_can_create_a_company_user_with_its_company_profile(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/admin/users', [
            'name' => 'حساب شركة تجريبي',
            'email' => 'new-company@majra.demo',
            'password' => 'MajraCompany2026!',
            'role' => 'company',
            'company_name' => 'شركة نقل تجريبية',
            'business_type' => 'نقل',
            'is_suspended' => false,
        ])->assertCreated();

        $user = User::where('email', 'new-company@majra.demo')->firstOrFail();
        $this->assertSame('company', $user->role);
        $this->assertSame('شركة نقل تجريبية', $user->company->name);
    }

    public function test_company_manager_creates_account_and_company_together(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'شركة الشمال التجريبية',
            'account_name' => 'مسؤول الشركة',
            'email' => 'north-company@majra.demo',
            'password' => 'MajraCompany2026!',
            'registration_number' => 'JO-DEMO-2001',
            'business_type' => 'نقل',
            'status' => 'pending',
        ])->assertCreated();

        $this->assertDatabaseHas('companies', ['name' => 'شركة الشمال التجريبية']);
        $this->assertDatabaseHas('users', ['email' => 'north-company@majra.demo', 'role' => 'company']);
    }

    public function test_company_cannot_be_deleted_until_its_subscription_has_ended(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $plan = SubscriptionPlan::create([
            'slug' => 'demo-delete-check', 'name' => 'باقة اختبار', 'price' => 1,
            'currency' => 'JOD', 'interval' => 'month', 'features' => [], 'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'company_id' => $company->id, 'subscription_plan_id' => $plan->id,
            'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)->deleteJson('/api/admin/companies/'.$company->id)
            ->assertStatus(409)
            ->assertJsonPath('message', 'لا يمكن حذف الشركة قبل انتهاء اشتراكها النشط.');

        $subscription->update(['status' => 'expired', 'ends_at' => now()->subDay()]);

        $this->actingAs($admin)->deleteJson('/api/admin/companies/'.$company->id)->assertOk();
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('users', ['id' => $company->user_id]);
    }

    public function test_admin_resource_lists_are_limited_to_five_rows(): void
    {
        $admin = User::factory()->admin()->create();
        Report::factory()->count(7)->create();

        $this->actingAs($admin)->getJson('/api/admin/reports?per_page=50')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 7);
    }

    public function test_non_admin_cannot_create_admin_alerts(): void
    {
        $citizen = User::factory()->citizen()->create();

        $this->actingAs($citizen)->postJson('/api/admin/alerts', [
            'title' => 'تنبيه', 'message' => 'نص', 'severity' => 'info', 'source' => 'admin',
        ])->assertForbidden();
    }

    public function test_admin_can_edit_and_remove_alerts_closures_and_unused_plans(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $resources = [
            'alerts' => [
                ['title' => 'Demo alert', 'message' => 'Test alert only.', 'severity' => 'info', 'source' => 'admin'],
                ['severity' => 'warning'],
            ],
            'road-closures' => [
                ['title' => 'Demo closure', 'area_name' => 'Amman', 'status' => 'active', 'latitude' => 31.95, 'longitude' => 35.91],
                ['status' => 'closed'],
            ],
            'subscription-plans' => [
                ['name' => 'Demo plan', 'slug' => 'test-admin-plan', 'price' => 12, 'currency' => 'USD', 'interval' => 'month'],
                ['name' => 'Renamed demo plan'],
            ],
        ];
        foreach ($resources as $resource => [$create, $update]) {
            $endpoint = '/api/admin/'.$resource;
            $id = $this->postJson($endpoint, $create)->assertCreated()->json('data.id');
            $this->getJson($endpoint.'/'.$id)->assertOk();
            $result = $this->patchJson($endpoint.'/'.$id, $update)->assertOk();
            foreach ($update as $key => $value) {
                $result->assertJsonPath('data.'.$key, $value);
            }
            $this->deleteJson($endpoint.'/'.$id)->assertOk();
            $this->getJson($endpoint.'/'.$id)->assertNotFound();
        }
    }

    public function test_alerts_and_closures_keep_their_exact_deadlines_from_the_admin_form(): void
    {
        $end = now()->addHour()->utc()->startOfMinute();
        $this->actingAs(User::factory()->admin()->create());
        $alertId = $this->postJson('/api/admin/alerts', [
            'title' => 'Timed demo alert', 'message' => 'Test fixture', 'severity' => 'info',
            'source' => 'demo', 'expires_at' => $end->toIso8601String(), 'is_active' => true,
        ])->assertCreated()->json('data.id');
        $roadId = $this->postJson('/api/admin/road-closures', [
            'title' => 'Timed demo road closure', 'area_name' => 'Amman', 'status' => 'active',
            'latitude' => 31.95, 'longitude' => 35.91, 'ends_at' => $end->toIso8601String(),
        ])->assertCreated()->json('data.id');

        $this->assertTrue(\App\Models\Alert::findOrFail($alertId)->expires_at->equalTo($end));
        $this->assertTrue(\App\Models\RoadClosure::findOrFail($roadId)->ends_at->equalTo($end));
        $this->actingAs(User::factory()->citizen()->create())->get('/map')
            ->assertSee('Timed demo alert')->assertSee('Timed demo road closure');
    }
}
