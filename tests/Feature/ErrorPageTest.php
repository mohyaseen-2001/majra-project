<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_page_returns_the_weather_design(): void
    {
        $this->get('/missing-majra-page')
            ->assertNotFound()
            ->assertSee('class="cloud"', false)
            ->assertSeeText('Page not found');
    }

    public function test_access_denial_uses_the_design_for_pages_and_json_for_api_requests(): void
    {
        $this->actingAs(User::factory()->citizen()->create())
            ->get('/admin/dashboard')
            ->assertForbidden()
            ->assertSeeText('Access denied')
            ->assertSee('class="cloud"', false);

        $this->getJson('/api/admin/companies')
            ->assertForbidden()
            ->assertJsonStructure(['message'])
            ->assertDontSee('class="cloud"', false);
    }

    public function test_other_full_page_errors_keep_their_status_codes(): void
    {
        config(['app.debug' => false]);

        Route::get('/test-error/{status}', fn (string $status) => abort((int) $status));

        foreach ([419, 500, 503] as $status) {
            $this->get("/test-error/{$status}")
                ->assertStatus($status)
                ->assertSee('class="cloud"', false)
                ->assertSeeText((string) $status);
        }
    }
}
