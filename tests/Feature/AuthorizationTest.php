<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_employee_cannot_access_admin_dashboard(): void
    {
        $employee = User::where('email', 'employee1@example.com')->first();
        $response = $this->actingAs($employee)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Employee/Dashboard'));
    }

    public function test_driver_cannot_access_admin_dashboard(): void
    {
        $driver = User::where('email', 'driver1@example.com')->first();
        $response = $this->actingAs($driver)->get('/dashboard');
        $response->assertInertia(fn ($page) => $page->component('Driver/Dashboard'));
    }

    public function test_unauthenticated_user_redirected(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_each_role_gets_correct_dashboard(): void
    {
        $roles = [
            'superadmin@example.com' => 'Admin/Dashboard',
            'admin@example.com' => 'Admin/Dashboard',
            'dispatcher1@example.com' => 'Dispatcher/Dashboard',
            'approver1@example.com' => 'Approver/Dashboard',
            'employee1@example.com' => 'Employee/Dashboard',
            'driver1@example.com' => 'Driver/Dashboard',
        ];

        foreach ($roles as $email => $expectedComponent) {
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user, "User $email not found");

            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertStatus(200);
            $response->assertInertia(fn ($page) => $page->component($expectedComponent));
        }
    }

    public function test_login_page_shows_for_guests(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }
}
