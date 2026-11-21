<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Driver;
use App\Models\TripRequest;
use App\Models\User;
use App\Notifications\DriverAssigned;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 3 — Tautan referensi pada Trip BOD.
 */
class BodTripLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function superadmin(): User
    {
        return User::where('email', 'superadmin@example.com')->firstOrFail();
    }

    protected function bodId(): int
    {
        return Department::where('code', 'BOD')->value('id');
    }

    public function test_bod_trip_with_https_link_is_saved_and_sent_to_driver(): void
    {
        $superadmin = $this->superadmin();
        $driver = Driver::where('license_number', 'LIC-001')->firstOrFail();
        $driver->update(['status' => 'STANDBY']);

        $trip = (new TripService)->createTrip([
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $this->bodId(),
            'bod_link' => 'https://maps.app.goo.gl/abc123',
        ], $superadmin);

        $this->assertSame('https://maps.app.goo.gl/abc123', $trip->fresh()->bod_link);

        $meta = [];
        $result = (new TripService)->assignDriverForBod($trip->fresh(), $superadmin, $driver->id, $meta);

        $this->assertSame('https://maps.app.goo.gl/abc123', $result->bod_link);

        // Pesan WhatsApp ke driver BOD menyertakan tautan
        $message = urldecode((string) $meta['wa_link']);
        $this->assertStringContainsString('https://maps.app.goo.gl/abc123', $message);
    }

    public function test_bod_link_appears_in_assignment_whatsapp_message(): void
    {
        $superadmin = $this->superadmin();
        $driver = Driver::where('license_number', 'LIC-003')->firstOrFail();

        $trip = (new TripService)->createTrip([
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $this->bodId(),
            'bod_link' => 'https://example.com/agenda.pdf',
        ], $superadmin);

        $trip = $trip->fresh();
        $message = (new DriverAssigned($trip))->toWhatsApp($driver->user);

        $this->assertStringContainsString('https://example.com/agenda.pdf', $message);
    }

    public function test_dangerous_link_schemes_are_rejected(): void
    {
        $superadmin = $this->superadmin();
        $driver = Driver::where('license_number', 'LIC-001')->firstOrFail();

        foreach (['javascript:alert(1)', 'data:text/html;base64,PHNjcmlwdD4=', 'ftp://example.com/file'] as $bad) {
            $response = $this->actingAs($superadmin)->post(route('trips.store'), [
                'request_type' => 'BUSINESS_TRIP',
                'department_id' => $this->bodId(),
                'driver_id' => $driver->id,
                'bod_link' => $bad,
            ]);

            $response->assertSessionHasErrors(['bod_link']);
        }

        // Tidak ada trip dengan link berbahaya yang tersimpan.
        $this->assertSame(
            0,
            TripRequest::whereIn('bod_link', [
                'javascript:alert(1)',
                'data:text/html;base64,PHNjcmlwdD4=',
                'ftp://example.com/file',
            ])->count()
        );
    }

    public function test_valid_http_link_is_accepted_via_store_endpoint(): void
    {
        $superadmin = $this->superadmin();
        $driver = Driver::where('license_number', 'LIC-001')->firstOrFail();

        $this->actingAs($superadmin)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $this->bodId(),
            'driver_id' => $driver->id,
            'bod_link' => 'http://example.com/agenda',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('trip_requests', [
            'department_id' => $this->bodId(),
            'bod_link' => 'http://example.com/agenda',
        ]);
    }

    public function test_bod_link_is_exposed_on_trip_detail_page(): void
    {
        $superadmin = $this->superadmin();
        $driver = Driver::where('license_number', 'LIC-001')->firstOrFail();

        $trip = (new TripService)->createTrip([
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $this->bodId(),
            'bod_link' => 'https://maps.google.com/?q=-6.2,106.8',
        ], $superadmin);

        $this->actingAs($superadmin)->get(route('trips.show', $trip))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('trip.bod_link', 'https://maps.google.com/?q=-6.2,106.8')
                ->where('trip.is_bod', true));
    }

    public function test_unsafe_stored_link_is_not_rendered(): void
    {
        $superadmin = $this->superadmin();

        $trip = TripRequest::create([
            'trip_number' => 'TRIP-XSS-'.uniqid(),
            'requestor_id' => $superadmin->id,
            'department_id' => $this->bodId(),
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addDay(),
            'destination' => 'Jakarta',
            'purpose' => 'Uji XSS',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_APPROVED,
            'bod_link' => 'javascript:alert(1)',
            'created_by' => $superadmin->id,
            'updated_by' => $superadmin->id,
        ]);

        // Accessor mengembalikan null untuk skema berbahaya
        $this->assertNull($trip->fresh()->bod_link);
    }
}
