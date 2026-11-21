<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverAccountFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_creating_driver_role_user_redirects_to_drivers_page(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();

        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Fiqhi Ibrahim',
            'email' => 'fiqhi2@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role_id' => $driverRole->id,
            'phone' => '081200009999',
        ]);

        $response->assertRedirect(route('admin.drivers'));
        $response->assertSessionHas('success');
    }

    public function test_creating_non_driver_user_stays_on_users_page(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $employeeRole = Role::where('name', 'EMPLOYEE')->firstOrFail();

        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Karyawan Baru',
            'email' => 'karyawan-baru@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role_id' => $employeeRole->id,
        ]);

        $response->assertRedirect(route('admin.users'));
    }

    public function test_drivers_page_shows_newly_created_driver_account(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();

        // Simulasi: user baru dibuat lewat form User Management
        $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Fiqhi Ibrahim',
            'email' => 'fiqhi3@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role_id' => $driverRole->id,
        ])->assertRedirect(route('admin.drivers'));

        // Halaman drivers yang baru dimuat HARUS memuat akun tersebut
        $this->actingAs($superadmin)->get('/admin/drivers')
            ->assertInertia(fn ($page) => $page
                ->where('available_users.0.email', 'fiqhi3@example.com')
                ->where('available_users.0.name', 'Fiqhi Ibrahim'));
    }
}
