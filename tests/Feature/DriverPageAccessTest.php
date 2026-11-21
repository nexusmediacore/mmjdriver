<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverPageAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_driver_my_tasks_page_works(): void
    {
        $user = User::where('email', 'driver1@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('DRIVER', $user->role->name);

        $response = $this->actingAs($user)->get('/driver/my-tasks');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Driver/MyTasks'));
    }

    public function test_driver_trip_history_page_works(): void
    {
        $user = User::where('email', 'driver1@example.com')->first();
        $response = $this->actingAs($user)->get('/driver/trip-history');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Driver/TripHistory'));
    }

    public function test_dashboard_works_for_driver(): void
    {
        $user = User::where('email', 'driver1@example.com')->first();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }
}
