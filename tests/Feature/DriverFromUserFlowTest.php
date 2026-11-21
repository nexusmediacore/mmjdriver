<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverFromUserFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_full_flow_user_to_driver(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();

        // Langkah 1: buat akun ber-role DRIVER dari User Management
        $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Fiqhi Ibrahim',
            'email' => 'fiqhi.ibrahim11@gmail.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role_id' => $driverRole->id,
            'phone' => '081200009999',
        ])->assertSessionHasNoErrors();

        // Langkah 2: halaman Drivers harus mendeteksinya
        $this->actingAs($superadmin)->get('/admin/drivers')
            ->assertInertia(fn ($page) => $page
                ->where('available_users.0.email', 'fiqhi.ibrahim11@gmail.com'));

        // Langkah 3: daftarkan sebagai driver
        $newUser = User::where('email', 'fiqhi.ibrahim11@gmail.com')->firstOrFail();

        $this->actingAs($superadmin)->post(route('admin.drivers.store'), [
            'user_id' => $newUser->id,
            'license_number' => 'SIM-A-112233',
            'phone' => '081200009999',
            'vehicle_plate' => 'B 9 FIQ',
            'vehicle_type' => 'Minibus',
            'willing_to_load' => '1',
        ])->assertSessionHasNoErrors();

        // Langkah 4: driver terbuat dengan status STANDBY dan tidak lagi
        // muncul di dropdown available_users
        $this->assertDatabaseHas('drivers', [
            'user_id' => $newUser->id,
            'license_number' => 'SIM-A-112233',
            'status' => 'STANDBY',
        ]);

        $this->actingAs($superadmin)->get('/admin/drivers')
            ->assertInertia(fn ($page) => $page
                ->has('available_users', 0));

        dump('FULL FLOW OK: User DRIVER -> terdeteksi -> jadi Driver -> hilang dari dropdown');
    }
}
