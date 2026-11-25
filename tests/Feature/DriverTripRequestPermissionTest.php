<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Driver;
use App\Models\TripRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTripRequestPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function driverUser(): User
    {
        $user = User::whereHas('role', fn ($q) => $q->where('name', 'DRIVER'))->firstOrFail();

        // Pastikan driver punya record Driver + departemen BOD.
        $driver = Driver::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'STANDBY', 'license_number' => 'LIC-'.str()->uuid()]
        );

        $user->update(['department_id' => Department::where('code', 'BOD')->value('id')]);

        return $user->fresh();
    }

    public function test_driver_can_create_bod_trip(): void
    {
        $user = $this->driverUser();
        $driver = Driver::where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => Department::where('code', 'BOD')->value('id'),
            'driver_id' => $driver->id,
            'trip_date' => now()->addDay()->toDateString(),
            'departure_time' => now()->addDay()->format('Y-m-d 08:00:00'),
            'destination' => 'Bandung',
            'purpose' => 'Agenda direksi',
            'passenger_count' => 1,
        ]);

        $response->assertRedirect();
        // Alur BOD: trip auto-approved lalu langsung ditugaskan ke driver
        // yang dipilih (status lanjutan DRIVER_ACCEPTED).
        $this->assertDatabaseHas('trip_requests', [
            'requestor_id' => $user->id,
            'assigned_driver_id' => $driver->id,
        ]);
        $this->assertContains(
            TripRequest::where('requestor_id', $user->id)->latest('id')->first()->status,
            [TripRequest::STATUS_APPROVED, TripRequest::STATUS_DRIVER_ACCEPTED]
        );
    }

    public function test_driver_cannot_create_regular_trip_via_payload(): void
    {
        $user = $this->driverUser();
        $driver = Driver::where('user_id', $user->id)->firstOrFail();
        $purchasingId = Department::where('name', 'like', '%Purchasing%')->value('id')
            ?? Department::where('code', '!=', 'BOD')->value('id');

        $response = $this->actingAs($user)->post(route('trips.store'), [
            'request_type' => 'CARGO_PICKUP',
            'department_id' => $purchasingId,
            'driver_id' => $driver->id,
            'cargo_pickup_location' => 'Gudang',
            'cargo_description' => 'Barang',
            'destination' => 'Kantor',
            'purpose' => 'Kerja',
            'passenger_count' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_driver_cannot_access_create_page_if_not_bod_department(): void
    {
        $user = $this->driverUser();
        $user->update(['department_id' => Department::where('code', '!=', 'BOD')->value('id')]);

        $this->actingAs($user)->get(route('trips.create'))->assertForbidden();
    }

    public function test_driver_bod_department_can_access_create_page(): void
    {
        $user = $this->driverUser();

        $this->actingAs($user)->get(route('trips.create'))->assertOk();
    }

    public function test_employee_still_can_create_regular_trip(): void
    {
        $user = User::whereHas('role', fn ($q) => $q->where('name', 'EMPLOYEE'))->firstOrFail();

        $response = $this->actingAs($user)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'destination' => 'Jakarta',
            'purpose' => 'Kunjungan klien',
            'departure_time' => now()->addDay()->format('Y-m-d 08:00:00'),
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'passengers' => [['name' => $user->name]],
        ]);

        $response->assertRedirect();
    }
}
