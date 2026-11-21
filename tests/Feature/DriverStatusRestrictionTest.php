<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Driver;
use App\Models\Role;
use App\Models\TripRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverStatusRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Buat driver bebas (tanpa perjalanan aktif) secara eksplisit agar tes
     * tidak bergantung pada data seeder yang bersifat acak.
     */
    protected function makeIdleDriver(array $attributes = []): Driver
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
            'license_number' => 'SIM-TEST-001',
            'phone' => '081200000000',
            'status' => Driver::STATUS_STANDBY,
            'willing_to_load' => true,
        ], $attributes));
    }

    public function test_only_three_statuses_allowed(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driver = $this->makeIdleDriver();

        $allowed = ['STANDBY', 'SICK', 'LEAVE'];
        $blocked = ['ON_TRIP', 'ASSIGNED', 'ACCEPTED', 'RETURNING', 'COMPLETED', 'OFF_DUTY', 'UNAVAILABLE'];

        foreach ($allowed as $status) {
            $response = $this->actingAs($superadmin)->put(route('admin.drivers.update', $driver->id), [
                'status' => $status,
            ]);

            $response->assertSessionHasNoErrors();
            $this->assertSame($status, $driver->fresh()->status, "Status $status seharusnya diterima");
        }

        foreach ($blocked as $status) {
            // Refresh instance agar status lokal sinkron dengan DB,
            // jika tidak Eloquent akan melewati query update.
            $driver->refresh()->update(['status' => 'STANDBY']);

            $response = $this->actingAs($superadmin)->put(route('admin.drivers.update', $driver->id), [
                'status' => $status,
            ]);

            $response->assertSessionHasErrors('status');
            $this->assertSame('STANDBY', $driver->fresh()->status, "Status $status seharusnya ditolak");
        }

        $driver->refresh()->update(['status' => 'STANDBY']);
    }

    public function test_edit_only_accepts_status_field(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driver = $this->makeIdleDriver([
            'license_number' => 'SIM-KEEP-1',
            'phone' => '081111',
            'vehicle_plate' => 'B 1 KEEP',
            'vehicle_type' => 'Sedan',
        ]);

        // Kirim field lain bersama status — field lain TIDAK BOLEH berubah
        $response = $this->actingAs($superadmin)->put(route('admin.drivers.update', $driver->id), [
            'status' => 'STANDBY',
            'license_number' => 'HACKED-999',
            'phone' => '000',
            'vehicle_plate' => 'HACK 1',
            'vehicle_type' => 'Hacked',
            'willing_to_load' => true,
        ]);

        $response->assertSessionHasNoErrors();

        $driver->refresh();
        $this->assertSame('SIM-KEEP-1', $driver->license_number, 'Lisensi tidak boleh berubah lewat Edit');
        $this->assertSame('081111', $driver->phone, 'Telepon tidak boleh berubah lewat Edit');
        $this->assertSame('B 1 KEEP', $driver->vehicle_plate, 'Plat tidak boleh berubah lewat Edit');
        $this->assertSame('Sedan', $driver->vehicle_type, 'Tipe tidak boleh berubah lewat Edit');
    }

    public function test_inactive_driver_with_active_trip_cannot_be_set(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driver = $this->makeIdleDriver();

        // Buat perjalanan aktif milik driver
        $employee = User::where('email', 'employee1@example.com')->firstOrFail();

        TripRequest::create([
            'trip_number' => 'TRIP-TEST-GUARD-1',
            'requestor_id' => $employee->id,
            'department_id' => $driver->user->department_id ?? Department::first()?->id,
            'request_type' => 'BUSINESS_TRIP',
            'destination' => 'Uji Guard',
            'purpose' => 'Uji guard driver aktif',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addDay()->setHour(8),
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_ON_TRIP,
            'assigned_driver_id' => $driver->id,
            'created_by' => $employee->id,
            'updated_by' => $employee->id,
        ]);

        $response = $this->actingAs($superadmin)->put(route('admin.drivers.update', $driver->id), [
            'status' => 'SICK',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame('STANDBY', $driver->fresh()->status, 'Driver dengan perjalanan aktif tidak boleh dipasifkan');
    }
}
