<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminPageController;
use App\Models\Company;
use App\Models\Report;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_contact_form_and_role_boundaries(): void
    {
        foreach (['/', '/about', '/contact', '/pricing', '/safety', '/checkout', '/payment/success', '/payment/cancel', '/login', '/register', '/forgot-password'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->post('/contact', ['email' => 'invalid'])->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
        $this->post('/contact', [
            'name' => 'Demo visitor', 'email' => 'test@example.test',
            'subject' => 'Demo contact form', 'message' => 'Testing the contact form workflow.',
        ])->assertRedirect(route('contact'));
        $this->assertDatabaseHas('contacts', ['subject' => 'Demo contact form', 'status' => 'new']);
        $this->get('/map')->assertRedirect(route('login'));
        $this->post('/reports')->assertRedirect(route('login'));
        $this->getJson('/api/admin/reports')->assertUnauthorized();

        $this->actingAs(User::factory()->citizen()->create());
        foreach (['/dashboard', '/map', '/feed', '/reports/create', '/profile'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/company/dashboard')->assertForbidden();
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_sections_render_and_return_paginated_data(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->get('/admin/dashboard')->assertOk();
        foreach (AdminPageController::SECTIONS as $section) {
            $this->get('/admin/'.$section)->assertOk();
            $this->getJson('/api/admin/'.$section)->assertOk()
                ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total']]);
        }
    }

    public function test_citizen_report_upload_manual_review_points_and_resolution(): void
    {
        config(['services.gemini.key' => null]);
        Storage::fake('public');
        Http::preventStrayRequests();
        $citizen = User::factory()->citizen()->create();
        $admin = User::factory()->admin()->create();
        $this->actingAs($citizen)->post('/reports', ['title' => 'Incomplete'])->assertSessionHasErrors(['description', 'latitude', 'longitude', 'photo']);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jwioAAAAASUVORK5CYII=', true);
        $photo = UploadedFile::fake()->createWithContent('demo.png', $png);
        $submission = $this->post('/reports', [
            'title' => 'Demo flood workflow', 'description' => 'A demo description of flooding on a road.',
            'area_name' => 'Amman', 'latitude' => 31.95, 'longitude' => 35.91, 'photo' => $photo,
        ]);
        $report = Report::where('title', 'Demo flood workflow')->firstOrFail();
        $submission->assertRedirect(route('reports.show', $report->public_id));
        $this->assertSame('pending', $report->status);
        Storage::disk('public')->assertExists($report->media()->firstOrFail()->path);
        $this->assertDatabaseHas('ai_analyses', ['report_id' => $report->id, 'status' => 'unavailable']);
        $this->get('/feed')->assertDontSee($report->title);
        $this->actingAs(User::factory()->citizen()->create())->get(route('reports.show', $report->public_id))->assertNotFound();

        $this->actingAs($admin)->patchJson('/api/admin/reports/'.$report->id, [
            'status' => 'approved', 'risk_level' => 'high',
        ])->assertOk();
        $this->assertDatabaseHas('reliability_transactions', ['report_id' => $report->id, 'user_id' => $citizen->id]);
        $this->actingAs($citizen)->get('/feed')->assertSee($report->title);
        $this->get('/map')->assertSee($report->title);
        foreach (User::factory()->citizen()->count(3)->create() as $reviewer) {
            $this->actingAs($reviewer)->post(route('reports.confirm', $report->public_id), ['choice' => 'ended'])->assertRedirect();
        }
        $this->assertSame('resolved', $report->fresh()->status);
    }

    public function test_company_can_manage_its_areas_and_cannot_delete_another_company_area(): void
    {
        $company = Company::factory()->for(User::factory()->create(['role' => 'company']))->create();
        $other = Company::factory()->create();
        $plan = SubscriptionPlan::create(['slug' => 'paid', 'name' => 'Paid', 'price' => 19, 'currency' => 'USD']);
        $company->subscriptions()->create(['subscription_plan_id' => $plan->id, 'status' => 'active']);
        $otherArea = $other->areas()->create(['area_name' => 'Other area', 'radius_km' => 5]);
        $this->actingAs($company->user)->get('/dashboard')->assertRedirect(route('company.dashboard'));
        foreach (['/company/dashboard', '/company/areas', '/company/reports', '/company/subscription'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->post('/company/areas', ['area_name' => 'Demo warehouse', 'radius_km' => 4, 'center_latitude' => 31.95])
            ->assertSessionHasErrors('center_longitude');
        $this->post('/company/areas', ['area_name' => 'Demo warehouse', 'radius_km' => 4])->assertRedirect();
        $area = $company->areas()->where('area_name', 'Demo warehouse')->firstOrFail();
        $this->get('/company/areas')->assertSee('Demo warehouse')->assertDontSee('Other area');
        $this->delete(route('company.areas.destroy', $otherArea))->assertNotFound();
        $this->delete(route('company.areas.destroy', $area))->assertRedirect();
        $this->assertDatabaseMissing('company_areas', ['id' => $area->id]);
    }

    public function test_overdue_hazards_disappear_before_the_scheduled_cleanup_runs(): void
    {
        $report = Report::factory()->create(['status' => 'approved', 'expires_at' => now()->subMinute()]);
        $this->actingAs(User::factory()->citizen()->create())->get('/map')->assertDontSee($report->title);
        $this->get('/feed')->assertDontSee($report->title);
        $this->get(route('reports.show', $report->public_id))->assertNotFound();

        $this->artisan('reports:expire')->assertSuccessful();
        $this->assertSame('expired', $report->fresh()->status);
    }
}
