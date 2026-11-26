<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'citizen',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('map', absolute: false));
    }

    public function test_company_registration_creates_company_profile(): void
    {
        $response = $this->post('/register', [
            'name' => 'شركة اختبار',
            'email' => 'company@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'company',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('company.dashboard', absolute: false));
        $this->assertDatabaseHas('companies', ['name' => 'شركة اختبار', 'status' => 'active']);
    }
}
