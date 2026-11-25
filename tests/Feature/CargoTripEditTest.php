<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Driver;
use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CargoTripEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function cargoTrip(): TripRequest
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $purchasingId = Department::where('name', 'like', '%Purchasing%')->value('id')
            ?? Department::where('code', '!=', 'BOD')->value('id');

        return (new TripService)->createTrip([
            'request_type' => 'CARGO_PICKUP',
            'department_id' => $purchasingId,
            'cargo_pickup_location' => 'Gudang Lama',
            'cargo_description' => '5 dus dokumen',
            'destination' => 'Kantor Pusat',
            'purpose' => 'Muat barang lama',
            'passenger_count' => 1,
            'requires_loading_assistance' => true,
            'priority' => 'NORMAL',
        ], $superadmin);
    }

    public function test_cargo_edit_without_purpose_is_saved(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $trip = $this->cargoTrip();

        // Payload persis seperti frontend muat barang: TIDAK mengirim "purpose".
        $response = $this->actingAs($superadmin)->put(route('trips.update', $trip), [
            'request_type' => 'CARGO_PICKUP',
            'department_id' => $trip->department_id,
            'cargo_pickup_location' => 'Gudang Baru',
            'cargo_description' => '8 karung barang',
            'destination' => 'Kantor Cabang',
            'passenger_count' => 1,
            'requires_loading_assistance' => true,
        ]);

        $response->assertRedirect(route('trips.show', $trip));

        $trip->refresh();
        $this->assertSame('Gudang Baru', $trip->cargo_pickup_location);
        $this->assertSame('8 karung barang', $trip->cargo_description);
        $this->assertSame('Kantor Cabang', $trip->destination);
        // Data lama yang tidak diubah tidak ikut terhapus.
        $this->assertTrue($trip->requires_loading_assistance);
    }

    public function test_cargo_edit_validates_required_cargo_fields(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $trip = $this->cargoTrip();

        $response = $this->actingAs($superadmin)->from(route('trips.edit', $trip))->put(route('trips.update', $trip), [
            'request_type' => 'CARGO_PICKUP',
            'department_id' => $trip->department_id,
            'cargo_pickup_location' => '',
            'cargo_description' => '',
            'passenger_count' => 1,
        ]);

        $response->assertSessionHasErrors(['cargo_pickup_location', 'cargo_description']);
    }

    public function test_superadmin_can_edit_loading_support_drivers_on_draft(): void
    {
        // Superadmin mengedit trip DRAFT dan menentukan supir mana yang
        // diperbolehkan ikut muat — penugasan tersimpan, status tetap DRAFT.
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $trip = $this->cargoTrip();
        $this->assertSame(TripRequest::STATUS_DRAFT, $trip->status);

        $driverA = Driver::firstOrFail();
        $driverB = Driver::where('id', '!=', $driverA->id)->firstOrFail();

        $response = $this->actingAs($superadmin)->put(route('trips.update', $trip), [
            'request_type' => 'CARGO_PICKUP',
            'department_id' => $trip->department_id,
            'cargo_pickup_location' => 'Gudang Baru',
            'cargo_description' => 'Barang',
            'passenger_count' => 1,
            'requires_loading_assistance' => true,
            'loading_support_driver_ids' => [$driverA->id, $driverB->id],
        ]);

        $response->assertRedirect(route('trips.show', $trip));

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
        // Tidak ada assignment loading tersisa dari pemilihan lama.
        $this->assertSame(2, $trip->assignments()->where('role', TripAssignment::ROLE_LOADING_SUPPORT)->count());
        // Status tetap DRAFT.
        $this->assertSame(TripRequest::STATUS_DRAFT, $trip->refresh()->status);
    }

    public function test_cargo_edit_saves_loading_support_drivers(): void
    {
        // Muat barang: pemohon bisa memilih supir siapa saja yang ikut muat.
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $trip = $this->cargoTrip();

        $standbyDriver = Driver::firstOrFail();

        $response = $this->actingAs($superadmin)->put(route('trips.update', $trip), [
            'request_type' => 'CARGO_PICKUP',
            'department_id' => $trip->department_id,
            'cargo_pickup_location' => 'Gudang Baru',
            'cargo_description' => 'Barang',
            'passenger_count' => 1,
            'requires_loading_assistance' => true,
            'loading_support_driver_ids' => [$standbyDriver->id],
        ]);

        $response->assertRedirect(route('trips.show', $trip));

        $this->assertDatabaseHas('trip_assignments', [
            'trip_request_id' => $trip->id,
            'driver_id' => $standbyDriver->id,
            'role' => TripAssignment::ROLE_LOADING_SUPPORT,
        ]);
    }

    public function test_cargo_trip_never_enters_waiting_driver_status(): void
    {
        // Muat barang: alur approval TIDAK berakhir di WAITING_DRIVER.
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $trip = $this->cargoTrip();

        $this->actingAs($superadmin)->post(route('trips.submit', $trip));
        $this->actingAs($superadmin)->post(route('trips.approve', $trip), [
            'notes' => 'Setuju',
            'priority' => 'NORMAL',
        ]);

        $trip->refresh();
        $this->assertNotSame(TripRequest::STATUS_WAITING_DRIVER, $trip->status);
        $this->assertSame(TripRequest::STATUS_APPROVED, $trip->status);
    }

    public function test_cargo_trip_with_any_driver_saves_successfully(): void
    {
        // Pemohon bisa memilih supir siapa saja yang ikut muat.
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $purchasingId = Department::where('name', 'like', '%Purchasing%')->value('id')
            ?? Department::where('code', '!=', 'BOD')->value('id');
        $driver = Driver::firstOrFail();

        $trip = (new TripService)->createTrip([
            'request_type' => 'CARGO_PICKUP',
            'department_id' => $purchasingId,
            'cargo_pickup_location' => 'Gudang',
            'cargo_description' => 'Barang',
            'destination' => 'Kantor',
            'passenger_count' => 1,
            'requires_loading_assistance' => true,
            'notes' => 'Instruksi muat pagi',
            'loading_support_driver_ids' => [$driver->id],
            'priority' => 'NORMAL',
        ], $superadmin);

        $this->assertDatabaseHas('trip_assignments', [
            'trip_request_id' => $trip->id,
            'driver_id' => $driver->id,
            'role' => TripAssignment::ROLE_LOADING_SUPPORT,
        ]);
        $this->assertSame('Instruksi muat pagi', $trip->notes);
    }

    public function test_cargo_trip_saved_with_or_without_driver(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $purchasingId = Department::where('name', 'like', '%Purchasing%')->value('id')
            ?? Department::where('code', '!=', 'BOD')->value('id');

        $trip = (new TripService)->createTrip([
            'request_type' => 'CARGO_PICKUP',
            'department_id' => $purchasingId,
            'cargo_pickup_location' => 'Gudang',
            'cargo_description' => 'Barang',
            'destination' => 'Kantor',
            'passenger_count' => 1,
            'notes' => 'Tanpa driver',
            'priority' => 'NORMAL',
        ], $superadmin);

        $this->assertNull($trip->assigned_driver_id);
        $this->assertSame('Tanpa driver', $trip->notes);
    }
}
