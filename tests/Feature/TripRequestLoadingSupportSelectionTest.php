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

/**
 * Muat barang kini aktivitas internal kantor: penugasan driver pendukung
 * loading dihapus dari alur muat barang dan payload driver diabaikan.
 */
class TripRequestLoadingSupportSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function employee(): User
    {
        return User::where('email', 'employee1@example.com')->firstOrFail();
    }

    protected function makeDriver(string $name, string $status = Driver::STATUS_STANDBY, bool $willingToLoad = true): Driver
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
            'status' => $status,
            'willing_to_load' => $willingToLoad,
        ]);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'request_type' => TripRequest::TYPE_CARGO_PICKUP,
            'destination' => 'Gudang Supplier',
            'purpose' => 'Muat barang',
            'departure_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'requires_loading_assistance' => true,
            'cargo_pickup_location' => 'Gudang PT Maju Jaya',
            'cargo_description' => '5 dus dokumen',
            'loading_support_driver_ids' => [],
        ], $overrides);
    }

    public function test_cargo_trip_accepts_any_driver_selected(): void
    {
        // Muat barang: supir siapa saja (status apa pun) bisa ikut muat.
        $driverA = $this->makeDriver('Support A');
        $driverB = $this->makeDriver('Busy Support', Driver::STATUS_ASSIGNED);

        $response = $this->actingAs($this->employee())
            ->post(route('trips.store'), $this->validPayload([
                'loading_support_driver_ids' => [$driverA->id, $driverB->id],
            ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $trip = TripRequest::latest('id')->first();

        $this->assertDatabaseHas('trip_assignments', [
            'trip_request_id' => $trip->id,
            'driver_id' => $driverA->id,
            'role' => TripAssignment::ROLE_LOADING_SUPPORT,
        ]);
        $this->assertDatabaseHas('trip_assignments', [
            'trip_request_id' => $trip->id,
            'driver_id' => $driverB->id,
            'role' => TripAssignment::ROLE_LOADING_SUPPORT,
        ]);
        $this->assertDatabaseHas('loading_requests', [
            'trip_request_id' => $trip->id,
            'driver_id' => $driverA->id,
            'status' => 'PENDING',
        ]);

        // Trip muat barang tidak pernah masuk WAITING_DRIVER.
        $this->assertNotSame(TripRequest::STATUS_WAITING_DRIVER, $trip->status);
    }

    public function test_cargo_trip_saved_without_any_driver(): void
    {
        $response = $this->actingAs($this->employee())
            ->post(route('trips.store'), $this->validPayload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $trip = TripRequest::latest('id')->first();
        $this->assertNull($trip->assigned_driver_id);
        $this->assertDatabaseCount('trip_assignments', 0);
    }

    public function test_support_drivers_still_work_for_non_cargo_trip(): void
    {
        // Fitur penugasan loading tetap utuh untuk trip non-muat-barang
        // (dengan bantuan loading).
        $driverA = $this->makeDriver('Support A');
        $driverB = $this->makeDriver('Support B');

        $response = $this->actingAs($this->employee())
            ->post(route('trips.store'), array_merge($this->validPayload(), [
                'request_type' => TripRequest::TYPE_BUSINESS_TRIP,
                'cargo_pickup_location' => null,
                'cargo_description' => null,
                'loading_support_driver_ids' => [$driverA->id, $driverB->id],
            ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $trip = TripRequest::latest('id')->first();

        $this->assertDatabaseHas('trip_assignments', [
            'trip_request_id' => $trip->id,
            'driver_id' => $driverA->id,
            'role' => TripAssignment::ROLE_LOADING_SUPPORT,
        ]);
        $this->assertDatabaseHas('trip_assignments', [
            'trip_request_id' => $trip->id,
            'driver_id' => $driverB->id,
            'role' => TripAssignment::ROLE_LOADING_SUPPORT,
        ]);
        $this->assertDatabaseHas('loading_requests', [
            'trip_request_id' => $trip->id,
            'driver_id' => $driverA->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_rejects_support_drivers_without_loading_assistance_flag(): void
    {
        $driver = $this->makeDriver('Support Tanpa Flag');

        $response = $this->actingAs($this->employee())
            ->post(route('trips.store'), array_merge($this->validPayload(), [
                'request_type' => TripRequest::TYPE_BUSINESS_TRIP,
                'cargo_pickup_location' => null,
                'cargo_description' => null,
                'requires_loading_assistance' => false,
                'loading_support_driver_ids' => [$driver->id],
            ]));

        $response->assertSessionHasErrors(['loading_support_driver_ids']);
    }
}
