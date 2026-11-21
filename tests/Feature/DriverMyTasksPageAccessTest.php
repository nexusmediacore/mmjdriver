<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverMyTasksPageAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_driver_my_tasks_page_loads_200(): void
    {
        $user = User::where('email', 'driver1@example.com')->first();
        $this->assertNotNull($user, 'Driver user not found');
        $this->assertEquals('DRIVER', $user->role->name);

        $response = $this->actingAs($user)->get('/driver/my-tasks');
        $response->assertStatus(200);
    }

    public function test_driver_page_renders_inertia(): void
    {
        $user = User::where('email', 'driver1@example.com')->first();
        $response = $this->actingAs($user)->get('/driver/my-tasks');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Driver/MyTasks'));
    }
}
