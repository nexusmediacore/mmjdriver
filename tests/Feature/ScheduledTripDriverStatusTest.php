<?php

namespace Tests\Feature;

use App\Console\Commands\ActivateScheduledTrips;
use App\Models\Driver;
use App\Models\Role;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DriverScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Status driver saat ditugaskan & setelah penyelesaian perjalanan.
 */
class ScheduledTripDriverStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function driver(): Driver
    {
        return Driver::where('license_number', 'LIC-001')->firstOrFail();
    }

    protected function vehicle(): Vehicle
    {
        return Vehicle::where('plate_number', 'B 1001 AA')->firstOrFail();
    }

    protected function dispatcher(): User
    {
        return User::where('email', 'dispatcher1@example.com')->firstOrFail();
    }

    protected function employee(): User
    {
        return User::where('email', 'employee1@example.com')->firstOrFail();
    }

    protected function futureTrip(Driver $driver, Vehicle $vehicle, string $status = TripRequest::STATUS_WAITING_DRIVER): TripRequest
    {
        $trip = TripRequest::create([
            'trip_number' => 'TRIP-FUT-'.uniqid(),
            'requestor_id' => $this->employee()->id,
            'department_id' => $this->employee()->department_id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->addDays(3)->toDateString(),
            'departure_time' => now()->addDays(3)->setHour(9),
            'estimated_return_time' => now()->addDays(3)->setHour(13),
            'destination' => 'Bandung',
            'purpose' => 'Trip terjadwal masa depan',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => $status,
            'created_by' => $this->employee()->id,
            'updated_by' => $this->employee()->id,
        ]);

        $this->actingAs($this->dispatcher())
            ->post(route('trips.assign-driver', $trip->id), [
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
            ]);

        return $trip->fresh();
    }

    public function test_driver_becomes_assigned_when_assigned_to_future_trip(): void
    {
        $driver = $this->driver()->load('user');
        $trip = $this->futureTrip($driver, $this->vehicle());

        // Assignment tetap tercatat & trip DRIVER_ASSIGNED
        $this->assertSame(TripRequest::STATUS_DRIVER_ASSIGNED, $trip->status);
        $this->assertSame($driver->id, $trip->assigned_driver_id);
        $this->assertNotNull($trip->assignment);

        // Driver yang sudah ditugaskan tetap STANDBY sampai Start Trip
        // (perilaku baru — masih selectable untuk trip lain).
        $this->assertSame(Driver::STATUS_STANDBY, $driver->fresh()->status);
    }

    public function test_driver_becomes_accepted_after_accepting_future_trip(): void
    {
        $driver = $this->driver()->load('user');
        $trip = $this->futureTrip($driver, $this->vehicle(), TripRequest::STATUS_DRIVER_ASSIGNED);

        $this->actingAs($driver->user)->post(route('trips.driver-response', $trip->id), ['action' => 'accept']);

        $trip->refresh();
        $this->assertSame(TripRequest::STATUS_DRIVER_ACCEPTED, $trip->status);

        // Driver yang menerima penugasan tetap STANDBY sampai Start Trip.
        $this->assertSame(Driver::STATUS_STANDBY, $driver->fresh()->status);
    }

    public function test_driver_becomes_assigned_for_trip_departing_today(): void
    {
        $driver = $this->driver()->load('user');
        $vehicle = $this->vehicle();

        $trip = TripRequest::create([
            'trip_number' => 'TRIP-TODAY-'.uniqid(),
            'requestor_id' => $this->employee()->id,
            'department_id' => $this->employee()->department_id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addHour(),
            'estimated_return_time' => now()->addHours(5),
            'destination' => 'Yogyakarta',
            'purpose' => 'Trip hari ini',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_WAITING_DRIVER,
            'created_by' => $this->employee()->id,
            'updated_by' => $this->employee()->id,
        ]);

        $this->actingAs($this->dispatcher())
            ->post(route('trips.assign-driver', $trip->id), [
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
            ]);

        // Hari H pun driver tetap STANDBY sampai Start Trip.
        $this->assertSame(Driver::STATUS_STANDBY, $driver->fresh()->status);
    }

    public function test_driver_accepting_trip_today_becomes_accepted(): void
    {
        $driver = $this->driver()->load('user');
        $vehicle = $this->vehicle();

        $trip = TripRequest::create([
            'trip_number' => 'TRIP-TODAY2-'.uniqid(),
            'requestor_id' => $this->employee()->id,
            'department_id' => $this->employee()->department_id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addHours(2),
            'destination' => 'Solo',
            'purpose' => 'Trip hari ini dua',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_DRIVER_ASSIGNED,
            'assigned_driver_id' => $driver->id,
            'assigned_vehicle_id' => $vehicle->id,
            'created_by' => $this->employee()->id,
            'updated_by' => $this->employee()->id,
        ]);

        $this->actingAs($driver->user)->post(route('trips.driver-response', $trip->id), ['action' => 'accept']);

        $this->assertSame(Driver::STATUS_STANDBY, $driver->fresh()->status);
    }

    public function test_same_driver_can_be_assigned_to_another_future_trip_on_a_different_day(): void
    {
        $driver = $this->driver()->load('user');
        $trip1 = $this->futureTrip($driver, $this->vehicle());

        // Trip kedua di hari berbeda — tidak boleh dianggap bentrok.
        $trip2 = TripRequest::create([
            'trip_number' => 'TRIP-FUT2-'.uniqid(),
            'requestor_id' => $this->employee()->id,
            'department_id' => $this->employee()->department_id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->addDays(5)->toDateString(),
            'departure_time' => now()->addDays(5)->setHour(8),
            'estimated_return_time' => now()->addDays(5)->setHour(12),
            'destination' => 'Semarang',
            'purpose' => 'Trip lain lagi',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_WAITING_DRIVER,
            'created_by' => $this->employee()->id,
            'updated_by' => $this->employee()->id,
        ]);

        $this->actingAs($this->dispatcher())
            ->post(route('trips.assign-driver', $trip2->id), [
                'driver_id' => $driver->id,
                'vehicle_id' => $this->vehicle()->id,
            ]);

        $this->assertSame(TripRequest::STATUS_DRIVER_ASSIGNED, $trip2->fresh()->status);
        $this->assertNull(session('error'));
    }

    public function test_overlapping_future_trip_is_rejected(): void
    {
        $driver = $this->driver()->load('user');
        $this->futureTrip($driver, $this->vehicle());

        // Trip kedua pada TANGGAL & JAM YANG SAMA dengan trip pertama.
        $sameDay = TripRequest::create([
            'trip_number' => 'TRIP-DUP-'.uniqid(),
            'requestor_id' => $this->employee()->id,
            'department_id' => $this->employee()->department_id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->addDays(3)->toDateString(),
            'departure_time' => now()->addDays(3)->setHour(9)->addMinutes(30),
            'estimated_return_time' => now()->addDays(3)->setHour(12),
            'destination' => 'Solo',
            'purpose' => 'Bentrok dengan trip pertama',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_WAITING_DRIVER,
            'created_by' => $this->employee()->id,
            'updated_by' => $this->employee()->id,
        ]);

        $this->actingAs($this->dispatcher())
            ->post(route('trips.assign-driver', $sameDay->id), [
                'driver_id' => $driver->id,
                'vehicle_id' => $this->vehicle()->id,
            ]);

        // Konflik terdeteksi dari tabel trip_assignments (bukan kolom driver.status)
        // sehingga trip kedua gagal ditugaskan. Driver tetap STANDBY.
        $this->assertSame(TripRequest::STATUS_WAITING_DRIVER, $sameDay->fresh()->status);
        $this->assertNull($sameDay->fresh()->assignment);
        $this->assertSame(Driver::STATUS_STANDBY, $driver->fresh()->status);
    }

    public function test_activation_command_promotes_due_trip_and_driver(): void
    {
        $driver = $this->driver()->load('user');
        $trip = $this->futureTrip($driver, $this->vehicle(), TripRequest::STATUS_DRIVER_ASSIGNED);

        // Trip sudah lewat tanggal keberangkatannya? Tidak — paksa mundur 1 hari.
        $trip->update([
            'trip_date' => now()->subDay()->toDateString(),
            'departure_time' => now()->subDay()->setHour(9),
            'estimated_return_time' => now()->subDay()->setHour(12),
        ]);

        $driver->update(['status' => Driver::STATUS_STANDBY]);

        // Seeder membuat trip acak (mis. ON_TRIP) untuk driver ini pada hari
        // lampau yang akan memengaruhi sinkronisasi status. Bersihkan agar
        // pengujian terhadap trip yang dipersiapkan bersifat hermetik.
        TripRequest::whereKeyNot($trip->id)
            ->where(fn ($q) => $q
                ->where('assigned_driver_id', $driver->id)
                ->orWhereHas('assignments', fn ($a) => $a->where('driver_id', $driver->id)))
            ->delete();

        $result = (new DriverScheduleService)->activateDueTrips();

        // Trip yang dipersiapkan test harus terangkat menjadi READY_TO_DEPART
        // (trip seeder lain yang ikut terangkat tidak diperhatikan).
        $this->assertGreaterThanOrEqual(1, $result['activated']);

        $trip->refresh();
        $this->assertSame(TripRequest::STATUS_READY_TO_DEPART, $trip->status);

        // Trip terangkat READY_TO_DEPART, tetapi driver tetap STANDBY —
        // status baru menjadi ON_TRIP tepat saat Start Trip.
        $this->assertSame(Driver::STATUS_STANDBY, $driver->fresh()->status);

        // Log status tercatat
        $this->assertDatabaseHas('trip_status_logs', [
            'trip_request_id' => $trip->id,
            'to_status' => TripRequest::STATUS_READY_TO_DEPART,
        ]);
    }

    public function test_refresh_driver_statuses_returns_idle_assigned_driver_to_standby(): void
    {
        // Driver bersih tanpa trip aktif apapun (dibuat sendiri agar tidak
        // terpengaruh trip seeder hari ini).
        $user = User::create([
            'name' => 'Driver Idle',
            'email' => 'driver-idle-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'role_id' => Role::where('name', 'DRIVER')->value('id'),
            'phone' => '081299900001',
            'is_active' => true,
        ]);

        $driver = Driver::create([
            'user_id' => $user->id,
            'license_number' => 'SIM-IDLE-'.uniqid(),
            'phone' => '081299900001',
            'status' => Driver::STATUS_ASSIGNED,
            'willing_to_load' => false,
        ]);

        (new DriverScheduleService)->refreshDriverStatuses();

        $this->assertSame(Driver::STATUS_STANDBY, $driver->fresh()->status);
    }

    public function test_scheduler_command_runs_successfully(): void
    {
        $this->artisan(ActivateScheduledTrips::class)->assertSuccessful();
    }
}
