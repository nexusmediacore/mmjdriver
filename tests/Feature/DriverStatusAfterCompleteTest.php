<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Driver;
use App\Models\Role;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverStatusAfterCompleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Buat driver + trip ON_TRIP yang bisa langsung diselesaikan.
     */
    protected function makeOnTripTrip(array $driverAttributes = []): array
    {
        $role = Role::where('name', 'DRIVER')->firstOrFail();
        $employee = User::where('email', 'employee1@example.com')->firstOrFail();

        $user = User::create([
            'name' => 'Driver Selesai',
            'email' => 'driver-selesai-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'department_id' => Department::first()?->id,
            'phone' => '081200001111',
            'is_active' => true,
        ]);

        $driver = Driver::create(array_merge([
            'user_id' => $user->id,
            'license_number' => 'SIM-SELESAI-001',
            'phone' => '081200001111',
            'status' => Driver::STATUS_ON_TRIP,
            'willing_to_load' => true,
        ], $driverAttributes));

        $vehicle = Vehicle::whereIn('status', ['AVAILABLE', 'ASSIGNED', 'ON_TRIP'])->firstOrFail();

        $trip = TripRequest::create([
            'trip_number' => 'TRIP-SELESAI-'.now()->format('His'),
            'requestor_id' => $employee->id,
            'department_id' => $employee->department_id ?? Department::first()?->id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->subHour(),
            'destination' => 'Solo',
            'purpose' => 'Uji status driver setelah selesai',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_ON_TRIP,
            'assigned_driver_id' => $driver->id,
            'assigned_vehicle_id' => $vehicle->id,
            'started_at' => now()->subHour(),
            'created_by' => $employee->id,
            'updated_by' => $employee->id,
        ]);

        return [$trip, $driver];
    }

    /**
     * Akhiri perjalanan langsung oleh driver — tanpa approval.
     */
    protected function submitAndApproveCompletion(TripRequest $trip, Driver $driver): TripRequest
    {
        $service = new TripService;

        return $service->submitCompletion($trip->fresh(), $driver->user, 'Perjalanan selesai');
    }

    public function test_driver_becomes_standby_after_completion_is_approved(): void
    {
        [$trip, $driver] = $this->makeOnTripTrip();

        $this->assertSame(Driver::STATUS_ON_TRIP, $driver->fresh()->status);

        $this->submitAndApproveCompletion($trip, $driver);

        $driver->refresh();

        dump('status trip    : '.$trip->fresh()->status);
        dump('status driver  : '.$driver->status.'  <-- harus STANDBY');

        $this->assertSame(TripRequest::STATUS_COMPLETED, $trip->fresh()->status);
        $this->assertSame(Driver::STATUS_STANDBY, $driver->status);
        $this->assertNotSame(Driver::STATUS_COMPLETED, $driver->status);
    }

    public function test_driver_can_be_reassigned_immediately_after_complete(): void
    {
        [$trip, $driver] = $this->makeOnTripTrip();

        $this->submitAndApproveCompletion($trip, $driver);
        $driver->refresh();

        // Driver sekarang STANDBY -> boleh ditugaskan lagi
        $this->assertFalse($driver->isInactive());
        $this->assertSame(Driver::STATUS_STANDBY, $driver->status);

        $vehicle = Vehicle::whereIn('status', ['AVAILABLE', 'ASSIGNED'])->firstOrFail();
        $driver->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        $employee = User::where('email', 'employee1@example.com')->firstOrFail();
        $newTrip = TripRequest::create([
            'trip_number' => 'TRIP-ULANG-'.now()->format('His'),
            'requestor_id' => $employee->id,
            'department_id' => $employee->department_id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->addDay()->toDateString(),
            'departure_time' => now()->addDay()->setHour(9),
            'destination' => 'Malang',
            'purpose' => 'Uji penugasan ulang',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_WAITING_DRIVER,
            'created_by' => $employee->id,
            'updated_by' => $employee->id,
        ]);

        $dispatcher = User::whereHas('role', fn ($q) => $q->where('name', 'DISPATCHER'))->firstOrFail();

        (new TripService)->assignDriverAndVehicle($newTrip->fresh(), $dispatcher, $driver->id, $vehicle->id);

        $this->assertSame(TripRequest::STATUS_DRIVER_ASSIGNED, $newTrip->fresh()->status);

        // Driver yang ditugaskan ke trip terjadwal tetap STANDBY sampai
        // Start Trip (perilaku baru).
        $this->assertSame(Driver::STATUS_STANDBY, $driver->fresh()->status);
    }

    public function test_inactive_driver_keeps_status_after_complete(): void
    {
        // Driver SICK yang menyelesaikan perjalanan harus TETAP SICK
        [$trip, $driver] = $this->makeOnTripTrip(['status' => Driver::STATUS_SICK]);

        $this->submitAndApproveCompletion($trip, $driver);
        $driver->refresh();

        dump('status driver SICK -> '.$driver->status.'  <-- harus tetap SICK');

        $this->assertSame(Driver::STATUS_SICK, $driver->status);
    }
}
