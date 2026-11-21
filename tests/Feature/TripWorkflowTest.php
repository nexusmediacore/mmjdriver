<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\TripCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TripWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // Ensure all drivers have compatibility with all vehicles for existing tests
        $drivers = Driver::all();
        $vehicles = Vehicle::all();
        foreach ($drivers as $driver) {
            $driver->allowedVehicles()->syncWithoutDetaching($vehicles->pluck('id')->toArray());
        }
    }

    protected function makeTrip(array $attributes = []): TripRequest
    {
        $user = User::where('email', 'employee1@example.com')->first();
        $department = $user->department;

        return TripRequest::create(array_merge([
            'trip_number' => 'TRIP-TEST-0001',
            'requestor_id' => $user->id,
            'department_id' => $department->id,
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addDay()->setHour(8)->setMinute(0),
            'estimated_return_time' => now()->addDay()->setHour(12)->setMinute(0),
            'destination' => 'Jakarta',
            'destination_address' => 'Jl. Test No. 1',
            'purpose' => 'Kunjungan klien',
            'passenger_count' => 2,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_DRAFT,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $attributes));
    }

    public function test_employee_can_create_trip(): void
    {
        $employee = User::where('email', 'employee1@example.com')->first();
        Notification::fake();

        $response = $this->actingAs($employee)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'destination' => 'Bandung',
            'purpose' => 'Rapat internal',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addDay()->setHour(8)->setMinute(0)->format('Y-m-d H:i'),
            'estimated_return_time' => now()->addDay()->setHour(12)->setMinute(0)->format('Y-m-d H:i'),
            'passenger_count' => 2,
            'priority' => 'NORMAL',
            'passengers' => [
                ['name' => 'Penumpang 1', 'phone' => '081200000001', 'department' => 'IT', 'nik' => '', 'position' => ''],
            ],
        ]);

        $trip = TripRequest::where('destination', 'Bandung')->latest('id')->firstOrFail();

        $response->assertRedirect(route('trips.show', $trip));
        $this->assertModelExists($trip);
        Notification::assertSentTo(
            User::whereHas('role', fn ($query) => $query->where('name', 'SUPERADMIN'))->get(),
            TripCreatedNotification::class
        );
    }

    public function test_employee_can_submit_trip(): void
    {
        $employee = User::where('email', 'employee1@example.com')->first();
        $trip = $this->makeTrip();

        $response = $this->actingAs($employee)->post(route('trips.submit', $trip));

        $response->assertRedirect();
        $this->assertEquals(TripRequest::STATUS_WAITING_APPROVAL, $trip->fresh()->status);
    }

    public function test_approver_can_approve_trip(): void
    {
        $approver = User::where('email', 'approver1@example.com')->first();
        $trip = $this->makeTrip();
        $trip->update([
            'department_id' => $approver->department_id,
            'status' => TripRequest::STATUS_WAITING_APPROVAL,
        ]);

        $response = $this->actingAs($approver)->post(route('trips.approve', $trip), ['notes' => 'Disetujui']);

        $response->assertRedirect();
        $this->assertEquals(TripRequest::STATUS_WAITING_DRIVER, $trip->fresh()->status);
        $this->assertNotNull($trip->fresh()->approved_at);
        $this->assertEquals($approver->id, $trip->fresh()->approved_by);
    }

    public function test_approver_can_reject_trip(): void
    {
        $approver = User::where('email', 'approver1@example.com')->first();
        $trip = $this->makeTrip();
        $trip->update([
            'department_id' => $approver->department_id,
            'status' => TripRequest::STATUS_WAITING_APPROVAL,
        ]);

        $response = $this->actingAs($approver)->post(route('trips.reject', $trip), ['reason' => 'Tidak perlu perjalanan']);

        $response->assertRedirect();
        $this->assertEquals(TripRequest::STATUS_REJECTED, $trip->fresh()->status);
        $this->assertEquals('Tidak perlu perjalanan', $trip->fresh()->rejection_reason);
    }

    public function test_dispatcher_can_assign_driver_and_vehicle(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $trip = $this->makeTrip();
        $trip->update(['status' => TripRequest::STATUS_WAITING_DRIVER]);

        $driver = Driver::where('status', 'STANDBY')->where('willing_to_load', false)->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        $response = $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals(TripRequest::STATUS_DRIVER_ASSIGNED, $trip->fresh()->status);
        $this->assertEquals($driver->id, $trip->fresh()->assigned_driver_id);
        $this->assertEquals($vehicle->id, $trip->fresh()->assigned_vehicle_id);
    }

    public function test_driver_conflict_is_detected(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $driver = Driver::where('status', 'STANDBY')->where('willing_to_load', false)->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        // Create an existing trip assigned to the driver
        $existingTrip = TripRequest::create([
            'trip_number' => 'TRIP-EXISTING-001',
            'requestor_id' => User::where('email', 'employee1@example.com')->first()->id,
            'department_id' => User::where('email', 'employee1@example.com')->first()->department_id,
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

        // Try to assign the same driver to a new trip with overlapping time
        $newTrip = $this->makeTrip();
        $newTrip->update([
            'status' => TripRequest::STATUS_WAITING_DRIVER,
            'departure_time' => now()->addDay()->setHour(10)->setMinute(0),
            'estimated_return_time' => now()->addDay()->setHour(14)->setMinute(0),
        ]);

        $vehicle2 = Vehicle::where('status', 'AVAILABLE')->where('id', '!=', $vehicle->id)->first();

        $response = $this->actingAs($dispatcher)->post(route('trips.assign-driver', $newTrip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle2?->id ?? $vehicle->id,
        ]);

        // Should fail with driver conflict error
        $response->assertRedirect();
        $this->assertTrue(session()->has('error'));
        $this->assertStringContainsString('Driver tidak tersedia', session('error'));
    }

    public function test_vehicle_conflict_is_detected(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $driver = Driver::where('status', 'STANDBY')->where('willing_to_load', false)->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        // Create an existing trip assigned to the vehicle
        $existingTrip = TripRequest::create([
            'trip_number' => 'TRIP-EXISTING-V1',
            'requestor_id' => User::where('email', 'employee1@example.com')->first()->id,
            'department_id' => User::where('email', 'employee1@example.com')->first()->department_id,
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

        // Try to assign the same vehicle to a different trip with overlapping time
        $driver2 = Driver::where('status', 'STANDBY')->where('id', '!=', $driver->id)->first();
        $newTrip = $this->makeTrip();
        $newTrip->update([
            'status' => TripRequest::STATUS_WAITING_DRIVER,
            'departure_time' => now()->addDay()->setHour(9)->setMinute(0),
            'estimated_return_time' => now()->addDay()->setHour(13)->setMinute(0),
        ]);

        $response = $this->actingAs($dispatcher)->post(route('trips.assign-driver', $newTrip), [
            'driver_id' => $driver2?->id ?? $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertRedirect();
        $this->assertTrue(session()->has('error'));
        $this->assertStringContainsString('Kendaraan tidak tersedia', session('error'));
    }

    public function test_driver_can_accept_assignment(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $trip = $this->makeTrip();
        $trip->update(['status' => TripRequest::STATUS_WAITING_DRIVER]);

        $driver = Driver::where('status', 'STANDBY')->where('willing_to_load', false)->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $driverUser = $driver->user;
        $response = $this->actingAs($driverUser)->post(route('trips.driver-response', $trip), ['action' => 'accept']);

        $response->assertRedirect();
        $this->assertEquals(TripRequest::STATUS_DRIVER_ACCEPTED, $trip->fresh()->status);
        $this->assertEquals(TripAssignment::RESPONSE_ACCEPTED, $trip->fresh()->assignment->driver_response);
    }

    public function test_driver_can_reject_assignment_with_reason(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $trip = $this->makeTrip();
        $trip->update(['status' => TripRequest::STATUS_WAITING_DRIVER]);

        $driver = Driver::where('status', 'STANDBY')->where('willing_to_load', false)->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $driverUser = $driver->user;
        $response = $this->actingAs($driverUser)->post(route('trips.driver-response', $trip), [
            'action' => 'reject',
            'reason' => 'Saya sedang tidak bisa menerima tugas',
        ]);

        $response->assertRedirect();
        $this->assertEquals(TripRequest::STATUS_DRIVER_REJECTED, $trip->fresh()->status);
        $this->assertEquals(TripAssignment::RESPONSE_REJECTED, $trip->fresh()->assignment->driver_response);
        $this->assertEquals('Saya sedang tidak bisa menerima tugas', $trip->fresh()->assignment->driver_rejection_reason);
    }

    public function test_driver_can_start_and_complete_trip(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $trip = $this->makeTrip();
        $trip->update(['status' => TripRequest::STATUS_WAITING_DRIVER]);

        $driver = Driver::where('status', 'STANDBY')->where('willing_to_load', false)->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $driverUser = $driver->user;
        $this->actingAs($driverUser)->post(route('trips.driver-response', $trip), ['action' => 'accept']);

        // Start trip
        $response = $this->actingAs($driverUser)->post(route('trips.start', $trip));
        $response->assertRedirect();
        $this->assertEquals(TripRequest::STATUS_ON_TRIP, $trip->fresh()->status);
        $this->assertNotNull($trip->fresh()->started_at);

        // Complete trip -> langsung COMPLETED oleh driver (tanpa approval)
        $response = $this->actingAs($driverUser)->post(route('trips.complete', $trip), ['notes' => 'Selesai tepat waktu']);
        $response->assertRedirect();
        $this->assertEquals(TripRequest::STATUS_COMPLETED, $trip->fresh()->status);
        $this->assertNotNull($trip->fresh()->completed_at);
        $this->assertEquals('Selesai tepat waktu', $trip->fresh()->completion_notes);
    }

    public function test_trip_cannot_complete_before_start(): void
    {
        $dispatcher = User::where('email', 'dispatcher1@example.com')->first();
        $trip = $this->makeTrip();
        $trip->update(['status' => TripRequest::STATUS_WAITING_DRIVER]);

        $driver = Driver::where('status', 'STANDBY')->where('willing_to_load', false)->first();
        $vehicle = Vehicle::where('status', 'AVAILABLE')->first();

        $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $driverUser = $driver->user;
        $this->actingAs($driverUser)->post(route('trips.driver-response', $trip), ['action' => 'accept']);

        // Try to complete without starting -> tombol tidak tersedia (403),
        // status trip tetap DRIVER_ACCEPTED.
        $response = $this->actingAs($driverUser)->post(route('trips.complete', $trip));
        $response->assertForbidden();
        $this->assertEquals(TripRequest::STATUS_DRIVER_ACCEPTED, $trip->fresh()->status);
    }

    public function test_audit_log_records_trip_actions(): void
    {
        $employee = User::where('email', 'employee1@example.com')->first();

        $response = $this->actingAs($employee)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'destination' => 'Bandung',
            'purpose' => 'Rapat internal',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addDay()->setHour(8)->setMinute(0)->format('Y-m-d H:i'),
            'estimated_return_time' => now()->addDay()->setHour(12)->setMinute(0)->format('Y-m-d H:i'),
            'passenger_count' => 2,
            'priority' => 'NORMAL',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('audit_logs', 1);
        $log = AuditLog::latest()->first();
        $this->assertSame('CREATE_TRIP', $log->action);
        $this->assertSame($employee->id, $log->user_id);
        $this->assertSame('TripRequest', $log->module);
        $this->assertSame('TripRequest', $log->record_type);
        $this->assertSame(
            TripRequest::where('destination', 'Bandung')->latest('id')->value('id'),
            $log->record_id
        );
    }
}
