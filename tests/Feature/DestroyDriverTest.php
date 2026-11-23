<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Driver;
use App\Models\Role;
use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function makeDriver(array $attributes = []): Driver
    {
        $role = Role::firstOrCreate(['name' => 'DRIVER'], ['description' => 'Driver']);

        $user = User::create([
            'name' => 'Driver Uji',
            'email' => 'driver-uji-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'department_id' => Department::first()?->id,
            'is_active' => true,
        ]);

        return Driver::create(array_merge([
            'user_id' => $user->id,
            'license_number' => 'SIM-TEST-'.uniqid(),
            'phone' => '081200000000',
            'status' => Driver::STATUS_STANDBY,
            'willing_to_load' => true,
        ], $attributes));
    }

    public function test_deletes_a_driver_even_with_trip_assignment_history(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driver = $this->makeDriver();

        $requestor = User::where('is_active', true)->firstOrFail();
        $tripRequest = TripRequest::create([
            'trip_number' => 'TRIP-TEST-'.uniqid(),
            'requestor_id' => $requestor->id,
            'department_id' => $requestor->department_id ?? Department::first()?->id,
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addDay()->setHour(8)->setMinute(0),
            'estimated_return_time' => now()->addDay()->setHour(12)->setMinute(0),
            'destination' => 'Jakarta',
            'destination_address' => 'Jl. Test No. 1',
            'purpose' => 'Kunjungan klien',
            'passenger_count' => 2,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_DRAFT,
            'created_by' => $requestor->id,
            'updated_by' => $requestor->id,
        ]);
        TripAssignment::create([
            'trip_request_id' => $tripRequest->id,
            'driver_id' => $driver->id,
            'driver_response' => 'PENDING',
            'assigned_by' => $superadmin->id,
        ]);

        $response = $this->actingAs($superadmin)
            ->delete(route('admin.drivers.destroy', $driver->id));

        $response->assertRedirect(route('admin.drivers'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('drivers', ['id' => $driver->id]);

        // Riwayat penugasan tetap ada, tapi tanpa mengacu ke driver yang dihapus.
        $this->assertDatabaseHas('trip_assignments', [
            'trip_request_id' => $tripRequest->id,
            'driver_id' => null,
        ]);
    }

    public function test_deletes_a_driver_without_assignments(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driver = $this->makeDriver();

        $response = $this->actingAs($superadmin)
            ->delete(route('admin.drivers.destroy', $driver->id));

        $response->assertRedirect(route('admin.drivers'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('drivers', ['id' => $driver->id]);
    }
}
