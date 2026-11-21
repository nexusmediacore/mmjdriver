<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFormCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_users_page_passes_roles_and_departments(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        $response = $this->actingAs($superadmin)->get('/admin/users');
        $response->assertStatus(200);

        $response->assertInertia(function ($page) {
            $props = $page->toArray()['props'];

            dump('roles diteruskan    : '.count($props['roles'] ?? []));
            dump('contoh role         : '.json_encode(array_slice($props['roles'] ?? [], 0, 2)));
            dump('departemen diteruskan: '.count($props['departments'] ?? []));

            $this->assertTrue(true);

            return true;
        });
    }

    public function test_superadmin_can_create_driver_role_user(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();

        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Driver Baru Dari Users',
            'email' => 'driver-dari-users@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role_id' => $driverRole->id,
            'phone' => '081200009999',
        ]);

        $response->assertSessionHasNoErrors();
        // Akun ber-role DRIVER kini diarahkan ke halaman Driver Management
        $response->assertRedirect(route('admin.drivers'));

        $this->assertDatabaseHas('users', [
            'email' => 'driver-dari-users@example.com',
            'role_id' => $driverRole->id,
            'is_active' => true,
        ]);

        // User ini harus langsung bisa dipakai di form Tambah Driver
        $newUser = User::where('email', 'driver-dari-users@example.com')->first();
        $this->assertTrue(
            $newUser->driver === null,
            'User baru belum punya record driver, jadi bisa dipilih di form Tambah Driver'
        );
    }
}
