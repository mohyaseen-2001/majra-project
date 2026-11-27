<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_report_review_page_renders_from_the_blade_app_bundle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('مراجعة البلاغات')
            ->assertSee('reports-rows');
    }

    public function test_admin_report_api_returns_json_for_authenticated_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson('/api/admin/reports')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total']]);
    }

    public function test_admin_can_delete_a_report_from_the_report_api(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = Report::factory()->create();

        $this->actingAs($admin)
            ->deleteJson('/api/admin/reports/'.$report->id)
            ->assertOk()
            ->assertJsonPath('message', 'تم الحذف.');

        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }
}
