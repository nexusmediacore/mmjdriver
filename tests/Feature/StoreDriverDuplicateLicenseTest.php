<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreDriverDuplicateLicenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Buat akun user ber-role DRIVER yang belum terdaftar sebagai driver.
     */
    protected function newDriverUser(): User
    {
        $driverRoleId = Role::where('name', 'DRIVER')->value('id');
        $suffix = User::max('id') + random_int(100, 999);

        return User::create([
            'name' => "Driver Cadangan {$suffix}",
            'email' => "reserve-driver-{$suffix}@example.com",
            'password' => bcrypt('password'),
            'role_id' => $driverRoleId,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_duplicate_license_number_returns_validation_error_not_500(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $existing = Driver::whereHas('user')->firstOrFail();
        $newUser = $this->newDriverUser();

        $response = $this->actingAs($superadmin)->post(route('admin.drivers.store'), [
            'user_id' => $newUser->id,
            'license_number' => $existing->license_number,
            'phone' => '081200000000',
            'vehicle_type' => 'Truk',
            'willing_to_load' => '1',
        ]);

        $response->assertSessionHasErrors(['license_number']);
        $this->assertDatabaseMissing('drivers', ['user_id' => $newUser->id]);
    }

    public function test_user_already_registered_as_driver_cannot_be_registered_again(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $existing = Driver::whereHas('user')->firstOrFail();

        $response = $this->actingAs($superadmin)->post(route('admin.drivers.store'), [
            'user_id' => $existing->user_id,
            'license_number' => 'SIM-BARU-9999',
            'phone' => '081200000001',
            'vehicle_type' => 'Bus',
            'willing_to_load' => '1',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(1, Driver::where('user_id', $existing->user_id)->count());
    }

    public function test_valid_driver_can_be_created(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        $newUser = $this->newDriverUser();

        $response = $this->actingAs($superadmin)->post(route('admin.drivers.store'), [
            'user_id' => $newUser->id,
            'license_number' => 'SIM-UNIK-12345',
            'phone' => '081200000002',
            'vehicle_type' => 'Truk',
            'willing_to_load' => '1',
        ]);

        $response->assertRedirect(route('admin.drivers'));
        $this->assertDatabaseHas('drivers', ['user_id' => $newUser->id, 'license_number' => 'SIM-UNIK-12345']);
    }
}
