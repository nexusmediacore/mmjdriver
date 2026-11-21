<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatCardLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_dashboard_stat_links_are_accessible(): void
    {
        $user = User::where('email', 'superadmin@example.com')->first();
        $this->assertNotNull($user, 'superadmin@example.com not found');

        $routes = [
            'admin.users',
            'admin.drivers',
            'admin.vehicles',
            'admin.departments',
            'trips.index',
            'dispatcher.active-trips',
            'dispatcher.driver-availability',
            'dispatcher.vehicle-availability',
            'approver.pending-approvals',
            'approver.trip-history',
            'reports.index',
        ];

        foreach ($routes as $name) {
            $response = $this->actingAs($user)->get(route($name));
            $response->assertStatus(200);
        }
    }

    public function test_driver_dashboard_stat_links_accessible(): void
    {
        $user = User::where('email', 'driver1@example.com')->first();
        $this->assertNotNull($user);

        foreach (['driver.my-tasks', 'driver.trip-history'] as $name) {
            $response = $this->actingAs($user)->get(route($name));
            $response->assertStatus(200);
        }
    }
}
