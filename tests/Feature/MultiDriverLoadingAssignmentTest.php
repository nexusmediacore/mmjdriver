<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Driver;
use App\Models\Role;
use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 5 — Multi-driver assignment untuk bantuan loading (Purchasing).
 */
class MultiDriverLoadingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function dispatcher(): User
    {
        return User::where('email', 'dispatcher1@example.com')->firstOrFail();
    }

    /**
     * Trip Purchasing dengan kebutuhan bantuan loading.
     */
    protected function makePurchasingTrip(): TripRequest
    {
        $employee = User::where('email', 'employee1@example.com')->firstOrFail();

        return TripRequest::create([
            'trip_number' => 'TRIP-LOAD-'.uniqid(),
            'requestor_id' => $employee->id,
            'department_id' => Department::where('code', 'PRC')->value('id')
                ?? $employee->department_id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addHours(2),
            'estimated_return_time' => now()->addHours(5),
            'destination' => 'Gudang Supplier',
            'purpose' => 'Pengambilan barang dengan bantuan loading',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'requires_loading_assistance' => true,
            'status' => TripRequest::STATUS_WAITING_DRIVER,
            'created_by' => $employee->id,
            'updated_by' => $employee->id,
        ]);
    }

    protected function makeDriver(string $name, bool $willingToLoad = true): Driver
    {
        $role = Role::where('name', 'DRIVER')->firstOrFail();
        $opsDept = Department::where('code', 'OPS')->firstOrFail();

        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'department_id' => $opsDept->id,
            'phone' => '0819'.random_int(10000000, 99999999),
            'is_active' => true,
        ]);

        return Driver::create([
            'user_id' => $user->id,
            'license_number' => 'SIM-'.strtoupper(substr($name, 0, 3)).'-'.uniqid(),
            'phone' => $user->phone,
            'status' => Driver::STATUS_STANDBY,
            'willing_to_load' => $willingToLoad,
        ]);
    }

    public function test_purchasing_trip_with_loading_supports_multiple_drivers(): void
    {
        $trip = $this->makePurchasingTrip();
        $main = $this->makeDriver('Main Driver');
        $support1 = $this->makeDriver('Support Satu');
        $support2 = $this->makeDriver('Support Dua');

        $vehicle = Vehicle::whereIn('status', ['AVAILABLE'])->firstOrFail();
        $main->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        $result = (new TripService)->assignDriverAndVehicle(
            $trip->fresh(),
            $this->dispatcher(),
            $main->id,
            $vehicle->id,
            [$support1->id, $support2->id]
        );

        // Assignment utama + dua support tercatat di trip_assignments
        $this->assertSame(3, $result->assignments()->count());
        $this->assertTrue((bool) $result->assignments()->where('driver_id', $main->id)->whereIn('role', [TripAssignment::ROLE_MAIN, 'MAIN_DRIVER'])->exists());
        $this->assertTrue((bool) $result->assignments()->where('driver_id', $support1->id)->where('role', TripAssignment::ROLE_LOADING_SUPPORT)->exists());
        $this->assertTrue((bool) $result->assignments()->where('driver_id', $support2->id)->where('role', TripAssignment::ROLE_LOADING_SUPPORT)->exists());

        // Trip utama mengikuti driver utama
        $this->assertSame($main->id, $result->assigned_driver_id);
        $this->assertSame(TripRequest::STATUS_DRIVER_ASSIGNED, $result->status);

        // Semua driver ter-catat notifikasi tugas
        foreach ([$main, $support1, $support2] as $driver) {
            $this->assertGreaterThan(
                0,
                $driver->user->notifications()->where('type', 'App\Notifications\DriverAssigned')->count(),
                "Driver {$driver->user->name} harus menerima notifikasi tugas."
            );
        }

        // Loading request utama tercatat
        $this->assertDatabaseHas('loading_requests', [
            'trip_request_id' => $result->id,
            'driver_id' => $main->id,
        ]);
    }

    public function test_unwilling_loading_driver_rejected_as_support(): void
    {
        $trip = $this->makePurchasingTrip();
        $main = $this->makeDriver('Main Driver');
        $unwilling = $this->makeDriver('Pemalas', false);

        $vehicle = Vehicle::whereIn('status', ['AVAILABLE'])->firstOrFail();
        $main->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('bersedia membantu loading');

        (new TripService)->assignDriverAndVehicle(
            $trip->fresh(),
            $this->dispatcher(),
            $main->id,
            $vehicle->id,
            [$unwilling->id]
        );
    }

    public function test_multi_driver_is_rejected_for_trip_without_loading_need(): void
    {
        $trip = $this->makePurchasingTrip();
        $trip->update(['requires_loading_assistance' => false]);

        $main = $this->makeDriver('Main Driver');
        $extra = $this->makeDriver('Extra Driver');

        $vehicle = Vehicle::whereIn('status', ['AVAILABLE'])->firstOrFail();
        $main->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('bantuan loading');

        (new TripService)->assignDriverAndVehicle(
            $trip->fresh(),
            $this->dispatcher(),
            $main->id,
            $vehicle->id,
            [$extra->id]
        );
    }

    public function test_support_driver_with_conflicting_schedule_fails_entire_assignment(): void
    {
        $trip = $this->makePurchasingTrip();
        $main = $this->makeDriver('Main Driver');
        $busy = $this->makeDriver('Busy Driver');

        $vehicle = Vehicle::whereIn('status', ['AVAILABLE'])->firstOrFail();
        $main->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        // Buat trip lain yang bentrok jadwal dengan driver pendukung
        $employee = User::where('email', 'employee2@example.com')->firstOrFail();
        $other = TripRequest::create([
            'trip_number' => 'TRIP-CONFLICT-'.uniqid(),
            'requestor_id' => $employee->id,
            'department_id' => $employee->department_id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addHours(2),
            'estimated_return_time' => now()->addHours(4),
            'destination' => 'Bandung',
            'purpose' => 'Trip pembanding jadwal',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_DRIVER_ACCEPTED,
            'assigned_driver_id' => $busy->id,
            'created_by' => $employee->id,
            'updated_by' => $employee->id,
        ]);

        try {
            (new TripService)->assignDriverAndVehicle(
                $trip->fresh(),
                $this->dispatcher(),
                $main->id,
                $vehicle->id,
                [$busy->id]
            );
            $this->fail('Assignment harus gagal karena konflik jadwal driver pendukung.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('perjalanan lain', $e->getMessage());
        }

        // Transaksi rollback: TIDAK ADA assignment untuk trip ini
        $this->assertSame(0, $trip->assignments()->count());
        $this->assertNull($trip->fresh()->assigned_driver_id);
    }

    public function test_form_request_blocks_multi_driver_without_loading_flag(): void
    {
        $trip = $this->makePurchasingTrip();
        $trip->update(['requires_loading_assistance' => false]);

        $main = $this->makeDriver('Main Driver');
        $extra = $this->makeDriver('Extra Driver');
        $vehicle = Vehicle::whereIn('status', ['AVAILABLE'])->firstOrFail();
        $main->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        $response = $this->actingAs($this->dispatcher())->post(
            route('trips.assign-driver', $trip->id),
            [
                'driver_id' => $main->id,
                'vehicle_id' => $vehicle->id,
                'additional_driver_ids' => [$extra->id],
            ]
        );

        $response->assertSessionHasErrors(['additional_driver_ids']);
    }

    public function test_assign_endpoint_creates_support_assignments(): void
    {
        $trip = $this->makePurchasingTrip();
        $main = $this->makeDriver('Main Endpoint');
        $support = $this->makeDriver('Support Endpoint');

        $vehicle = Vehicle::whereIn('status', ['AVAILABLE'])->firstOrFail();
        $main->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        $response = $this->actingAs($this->dispatcher())->post(
            route('trips.assign-driver', $trip->id),
            [
                'driver_id' => $main->id,
                'vehicle_id' => $vehicle->id,
                'additional_driver_ids' => [$support->id],
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('whatsapp_link');

        $this->assertDatabaseHas('trip_assignments', [
            'trip_request_id' => $trip->id,
            'driver_id' => $support->id,
            'role' => TripAssignment::ROLE_LOADING_SUPPORT,
        ]);
        $this->assertDatabaseHas('trip_assignments', [
            'trip_request_id' => $trip->id,
            'driver_id' => $main->id,
            'role' => TripAssignment::ROLE_MAIN,
        ]);
    }

    public function test_support_driver_trip_appears_in_their_task_list(): void
    {
        $trip = $this->makePurchasingTrip();
        $main = $this->makeDriver('Main List');
        $support = $this->makeDriver('Support List');

        // Gunakan kendaraan yang tidak dipakai oleh trip seeder aktif
        $vehicle = Vehicle::create([
            'plate_number' => 'B '.random_int(1000, 9999).' TST',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'type' => 'PICKUP',
            'year' => (int) now()->year,
            'passenger_capacity' => 2,
            'status' => 'AVAILABLE',
        ]);
        $main->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        (new TripService)->assignDriverAndVehicle(
            $trip->fresh(),
            $this->dispatcher(),
            $main->id,
            $vehicle->id,
            [$support->id]
        );

        // Halaman my-tasks driver pendukung memuat trip ini
        $response = $this->actingAs($support->user)->get(route('driver.my-tasks'));
        $response->assertOk();

        $tasks = $response->viewData('page')['props']['tasks'] ?? [];
        $taskIds = collect($tasks['data'] ?? $tasks)->pluck('id');
        $this->assertContains($trip->id, $taskIds, 'Trip harus muncul di daftar tugas driver pendukung.');
    }
}
