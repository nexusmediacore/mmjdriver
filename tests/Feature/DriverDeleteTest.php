<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_driver_on_trip_cannot_be_deleted(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driver = Driver::firstOrFail();
        $driver->update(['status' => Driver::STATUS_ON_TRIP]);

        $response = $this->actingAs($superadmin)->delete(route('admin.drivers.destroy', $driver->id));

        $response->assertRedirect(route('admin.drivers'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('drivers', ['id' => $driver->id]);
    }

    public function test_driver_can_be_deleted_and_cleans_up_relations(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driver = Driver::firstOrFail();
        $driverUser = $driver->user;
        $vehicle = Vehicle::firstOrFail();

        // Pair vehicle
        $driver->allowedVehicles()->sync([$vehicle->id]);
        $vehicle->update(['primary_driver_id' => $driver->id]);

        // Assign to a trip in DRIVER_ASSIGNED status
        $trip = TripRequest::firstOrFail();
        $trip->update([
            'status' => TripRequest::STATUS_DRIVER_ASSIGNED,
            'assigned_driver_id' => $driver->id,
            'assigned_vehicle_id' => $vehicle->id,
        ]);

        TripAssignment::create([
            'trip_request_id' => $trip->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'role' => TripAssignment::ROLE_MAIN,
            'driver_response' => TripAssignment::RESPONSE_PENDING,
            'assigned_by' => $superadmin->id,
        ]);

        $driver->update(['status' => Driver::STATUS_STANDBY]);

        $response = $this->actingAs($superadmin)->delete(route('admin.drivers.destroy', $driver->id));

        $response->assertRedirect(route('admin.drivers'));
        $response->assertSessionHas('success');

        // Driver must be deleted
        $this->assertDatabaseMissing('drivers', ['id' => $driver->id]);

        // Trip assignment should be deleted
        $this->assertDatabaseMissing('trip_assignments', ['driver_id' => $driver->id]);

        // Primary driver on vehicle should be null
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'primary_driver_id' => null]);

        // Pivot driver_vehicle should be detached
        $this->assertDatabaseMissing('driver_vehicle', ['driver_id' => $driver->id]);

        // TripRequest should revert to APPROVED so it can be reassigned
        $trip->refresh();
        $this->assertEquals(TripRequest::STATUS_APPROVED, $trip->status);
        $this->assertNull($trip->assigned_driver_id);

        // AuditLog must be created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'DELETE_DRIVER',
            'record_id' => $driver->id,
        ]);
    }
}
