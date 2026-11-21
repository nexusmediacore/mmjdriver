<?php

namespace Tests\Feature;

use App\Models\TripRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageAndLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_driver_sees_friendly_403_page_for_foreign_trip(): void
    {
        $driver = User::whereHas('role', fn ($q) => $q->where('name', 'DRIVER'))->firstOrFail();

        // Cari trip yang BUKAN milik driver ini
        $foreignTrip = TripRequest::where('assigned_driver_id', '!=', $driver->driver?->id)
            ->whereNotNull('assigned_driver_id')
            ->firstOrFail();

        $response = $this->actingAs($driver)->get("/trips/{$foreignTrip->id}");

        $response->assertStatus(403);
        $response->assertInertia(fn ($page) => $page->component('Errors/Error')
            ->where('status', 403)
            ->where('title', 'Akses Ditolak'));
    }

    public function test_driver_can_still_view_own_trip(): void
    {
        $driver = User::whereHas('role', fn ($q) => $q->where('name', 'DRIVER'))->firstOrFail();

        $ownTrip = TripRequest::where('assigned_driver_id', $driver->driver?->id)->first();

        if (! $ownTrip) {
            $this->markTestSkipped('Driver tidak memiliki trip untuk diuji');
        }

        $response = $this->actingAs($driver)->get("/trips/{$ownTrip->id}");
        $response->assertStatus(200);
    }

    public function test_login_redirects_to_dashboard_when_intended_is_unsafe(): void
    {
        $driver = User::whereHas('role', fn ($q) => $q->where('name', 'DRIVER'))->firstOrFail();

        // Simulasikan user sempat mencoba akses halaman spesifik lalu login
        $response = $this->withSession(['url.intended' => 'http://localhost/trips/99'])->post('/login', [
            'email' => $driver->email,
            'password' => 'Pass@123',
        ]);

        // Harus ke dashboard, BUKAN /trips/99 yang akan 403
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_preserves_safe_intended_path(): void
    {
        $driver = User::whereHas('role', fn ($q) => $q->where('name', 'DRIVER'))->firstOrFail();

        $response = $this->withSession(['url.intended' => 'http://localhost/driver/my-tasks'])->post('/login', [
            'email' => $driver->email,
            'password' => 'Pass@123',
        ]);

        $response->assertRedirect('/driver/my-tasks');
    }

    public function test_404_renders_friendly_page_for_authenticated_user(): void
    {
        $driver = User::whereHas('role', fn ($q) => $q->where('name', 'DRIVER'))->firstOrFail();

        $response = $this->actingAs($driver)->get('/halaman-yang-tidak-ada');

        $response->assertStatus(404);
        $response->assertInertia(fn ($page) => $page->component('Errors/Error')->where('status', 404));
    }
}
