<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverMyTasksErrorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_my_tasks_page_returns_200(): void
    {
        $user = User::where('email', 'driver1@example.com')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/driver/my-tasks');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Driver/MyTasks'));
    }
}
