<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_user_can_login_and_access_dashboard(): void
    {
        $employee = User::where('email', 'employee1@example.com')->first();
        $response = $this->actingAs($employee)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            return $page->component('Employee/Dashboard');
        });
    }

    public function test_driver_can_login_and_access_dashboard(): void
    {
        $driverUser = User::where('email', 'driver1@example.com')->first();
        $response = $this->actingAs($driverUser)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Driver/Dashboard'));
    }

    public function test_dispatcher_can_login_and_access_dashboard(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $response = $this->actingAs($dispatcher)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Dispatcher/Dashboard'));
    }

    public function test_approver_can_login_and_access_dashboard(): void
    {
        $approver = User::where('email', 'approver1@example.com')->first();
        $response = $this->actingAs($approver)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Approver/Dashboard'));
    }

    public function test_admin_can_login_and_access_dashboard(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));
    }

    public function test_superadmin_can_login_and_access_dashboard(): void
    {
        $superadmin = User::where('email', 'superadmin@example.com')->first();
        $response = $this->actingAs($superadmin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));
    }
}
