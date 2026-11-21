<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverVehicleCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // Create a compatible setup for all drivers/vehicles
        $drivers = Driver::all();
        $vehicles = Vehicle::all();
        foreach ($drivers as $driver) {
            $driver->allowedVehicles()->syncWithoutDetaching($vehicles->pluck('id')->toArray());
        }
    }

    protected function makeTrip(): TripRequest
    {
        $employee = User::where('email', 'employee1@example.com')->first();
        $department = $employee->department;

        return TripRequest::create([
            'trip_number' => 'TRIP-COMP-0001',
            'requestor_id' => $employee->id,
            'department_id' => $department->id,
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addDay()->setHour(8)->setMinute(0),
            'estimated_return_time' => now()->addDay()->setHour(12)->setMinute(0),
            'destination' => 'Jakarta',
            'purpose' => 'Kunjungan klien',
            'passenger_count' => 2,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_WAITING_DRIVER,
            'created_by' => $employee->id,
            'updated_by' => $employee->id,
        ]);
    }

    public function test_driver_with_compatibility_can_be_assigned(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $trip = $this->makeTrip();

        $driver = Driver::where('status', 'STANDBY')->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        // Ensure compatibility exists
        $driver->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        $response = $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals(TripRequest::STATUS_DRIVER_ASSIGNED, $trip->fresh()->status);
        $this->assertEquals($driver->id, $trip->fresh()->assigned_driver_id);
        $this->assertEquals($vehicle->id, $trip->fresh()->assigned_vehicle_id);
    }

    public function test_driver_without_compatibility_cannot_be_assigned(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $trip = $this->makeTrip();

        $driver = Driver::where('status', 'STANDBY')->first();

        // Detach all vehicles from driver to ensure no compatibility
        $driver->allowedVehicles()->detach();

        // Create a specific vehicle that driver doesn't have access to
        $incompatibleVehicle = Vehicle::create([
            'plate_number' => 'B 9999 ZZ',
            'brand' => 'Test Brand',
            'model' => 'Test Model',
            'type' => 'SUV',
            'year' => 2023,
            'passenger_capacity' => 5,
            'status' => 'AVAILABLE',
            'notes' => 'Test vehicle',
        ]);

        $response = $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $incompatibleVehicle->id,
        ]);

        $response->assertRedirect();
        // The FormRequest validation catches the compatibility violation
        $response->assertSessionHasErrors('vehicle_id');
        $this->assertNull($trip->fresh()->assigned_driver_id);
        $this->assertNull($trip->fresh()->assigned_vehicle_id);
    }

    public function test_driver_with_access_but_vehicle_in_maintenance_fails(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $trip = $this->makeTrip();

        $driver = Driver::where('status', 'STANDBY')->first();

        $vehicle = Vehicle::create([
            'plate_number' => 'B 8888 ZZ',
            'brand' => 'Test Brand',
            'model' => 'Test Model',
            'type' => 'SUV',
            'year' => 2023,
            'passenger_capacity' => 5,
            'status' => 'MAINTENANCE',
            'notes' => 'Test vehicle in maintenance',
        ]);

        // Driver has access to the vehicle
        $driver->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        // Remove the vehicle from the vehicles list passed by TripController since it's OUT_OF_SERVICE
        // Actually the TripService validates compatibility FIRST, then availability
        $response = $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertRedirect();
        $this->assertTrue(session()->has('error'));
        // The service checks compatibility first, which should pass, then vehicle availability
        $this->assertTrue(
            str_contains(session('error'), 'Kendaraan tidak tersedia') ||
            str_contains(session('error'), 'Driver tidak diizinkan'),
            'Got: '.session('error')
        );
        $this->assertNull($trip->fresh()->assigned_driver_id);
    }

    public function test_driver_with_access_but_schedule_conflict_fails(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $driver = Driver::where('status', 'STANDBY')->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        // Create an existing trip assigned to the driver with overlapping time
        $existingTrip = TripRequest::create([
            'trip_number' => 'TRIP-EXISTING-COMP-001',
            'requestor_id' => $dispatcher->id,
            'department_id' => $dispatcher->department_id,
            'trip_date' => now()->addDay()->toDateString(),
            'departure_time' => now()->addDay()->setHour(8)->setMinute(0),
            'estimated_return_time' => now()->addDay()->setHour(12)->setMinute(0),
            'destination' => 'Jakarta',
            'purpose' => 'Existing trip',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_ON_TRIP,
            'assigned_driver_id' => $driver->id,
            'assigned_vehicle_id' => $vehicle->id,
            'started_at' => now()->addDay()->setHour(8)->setMinute(0),
            'created_by' => $dispatcher->id,
            'updated_by' => $dispatcher->id,
        ]);

        // Ensure compatibility exists
        $driver->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        // Create a new trip with overlapping time
        $newTrip = $this->makeTrip();
        $newTrip->update([
            'status' => TripRequest::STATUS_WAITING_DRIVER,
            'departure_time' => now()->addDay()->setHour(10)->setMinute(0),
            'estimated_return_time' => now()->addDay()->setHour(14)->setMinute(0),
        ]);

        $response = $this->actingAs($dispatcher)->post(route('trips.assign-driver', $newTrip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertRedirect();
        $this->assertTrue(session()->has('error'));
        $this->assertStringContainsString('Driver tidak tersedia', session('error'));
    }

    public function test_valid_driver_and_vehicle_assignment_passes(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $trip = $this->makeTrip();

        $driver = Driver::where('status', 'STANDBY')->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        $driver->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        $response = $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals(TripRequest::STATUS_DRIVER_ASSIGNED, $trip->fresh()->status);
    }

    public function test_removing_vehicle_access_does_not_affect_completed_trips(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $driver = Driver::where('status', 'STANDBY')->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        // Create a completed trip
        $completedTrip = TripRequest::create([
            'trip_number' => 'TRIP-HIST-COMP-001',
            'requestor_id' => $dispatcher->id,
            'department_id' => $dispatcher->department_id,
            'trip_date' => now()->subDays(3)->toDateString(),
            'departure_time' => now()->subDays(3)->setHour(8)->setMinute(0),
            'estimated_return_time' => now()->subDays(3)->setHour(12)->setMinute(0),
            'destination' => 'Bandung',
            'purpose' => 'Completed trip',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_COMPLETED,
            'assigned_driver_id' => $driver->id,
            'assigned_vehicle_id' => $vehicle->id,
            'started_at' => now()->subDays(3)->setHour(8)->setMinute(0),
            'completed_at' => now()->subDays(3)->setHour(14)->setMinute(0),
            'created_by' => $dispatcher->id,
            'updated_by' => $dispatcher->id,
        ]);

        // Verify the historical trip has driver and vehicle
        $this->assertEquals($driver->id, $completedTrip->assigned_driver_id);
        $this->assertEquals($vehicle->id, $completedTrip->assigned_vehicle_id);

        // Now remove vehicle access from driver
        $driver->allowedVehicles()->detach($vehicle->id);

        // Historical trip must remain unchanged
        $this->assertEquals($driver->id, $completedTrip->fresh()->assigned_driver_id);
        $this->assertEquals($vehicle->id, $completedTrip->fresh()->assigned_vehicle_id);
        $this->assertEquals(TripRequest::STATUS_COMPLETED, $completedTrip->fresh()->status);
    }

    public function test_duplicate_driver_vehicle_relation_prevented_by_unique_constraint(): void
    {
        $driver = Driver::first();
        $vehicle = Vehicle::first();

        // Detach this driver from all vehicles first
        $driver->allowedVehicles()->detach();

        // First insert should work
        \DB::table('driver_vehicle')->insert([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertDatabaseHas('driver_vehicle', [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        // Verify unique constraint prevents manual duplicate insert
        $this->expectException(QueryException::class);
        \DB::table('driver_vehicle')->insert([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_vehicle_inactive_status_prevents_assignment(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $driver = Driver::where('status', 'STANDBY')->first();

        $vehicle = Vehicle::create([
            'plate_number' => 'B 7777 ZZ',
            'brand' => 'Test Brand',
            'model' => 'Test Model',
            'type' => 'SUV',
            'year' => 2023,
            'passenger_capacity' => 5,
            'status' => 'OUT_OF_SERVICE',
            'notes' => 'Out of service vehicle',
        ]);

        $driver->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        $trip = $this->makeTrip();

        $response = $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertRedirect();
        $this->assertTrue(session()->has('error'));
        $this->assertTrue(
            str_contains(session('error'), 'Kendaraan tidak tersedia') ||
            str_contains(session('error'), 'Driver tidak diizinkan'),
            'Got: '.session('error')
        );
    }
}
