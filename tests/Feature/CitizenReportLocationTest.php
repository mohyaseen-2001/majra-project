<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenReportLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_choose_map_or_one_time_gps_on_report_form(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($citizen)
            ->get('/reports/create')
            ->assertOk()
            ->assertSee('استخدام GPS لمرة واحدة')
            ->assertSee('اختيار نقطة على الخريطة')
            ->assertSee('navigator.geolocation.getCurrentPosition')
            ->assertSee('maximumAge: 30000');
    }

    public function test_map_shows_actionable_feedback_when_location_access_fails(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($citizen)
            ->get('/map')
            ->assertOk()
            ->assertSee('المتصفح منع الوصول للموقع')
            ->assertSee('تأكد من تفعيل خدمات الموقع في الجهاز')
            ->assertSee('role="status" aria-live="polite"', false);
    }
}
