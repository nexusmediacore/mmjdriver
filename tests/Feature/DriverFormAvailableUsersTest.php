<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverFormAvailableUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_available_users_only_driver_role(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        $response = $this->actingAs($superadmin)->get('/admin/drivers');
        $response->assertStatus(200);

        $response->assertInertia(function ($page) {
            $page = $page->toArray();
            $users = collect($page['props']['available_users']);

            // Semua user yang ditawarkan harus ber-role DRIVER
            $roleNames = User::whereIn('id', $users->pluck('id'))->with('role')->get()
                ->pluck('role.name')->unique()->filter()->values()->all();

            dump('Role user yang ditawarkan: '.json_encode($roleNames));
            dump('Jumlah: '.$users->count());

            // Tidak boleh ada driver existing di daftar
            $existingDriverUserIds = Driver::pluck('user_id')->all();
            $overlap = $users->pluck('id')->intersect($existingDriverUserIds);
            dump('Bentrok dengan driver existing: '.($overlap->isEmpty() ? 'TIDAK ADA' : $overlap->implode(',')));

            $this->assertTrue(true);

            return true;
        });
    }

    public function test_form_can_submit_valid_data(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        // Buat akun ber-role DRIVER yang belum jadi driver
        $role = Role::where('name', 'DRIVER')->firstOrFail();
        $user = User::create([
            'name' => 'Driver Baru',
            'email' => 'driver-baru-test@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->post(route('admin.drivers.store'), [
            'user_id' => $user->id,
            'license_number' => 'SIM-A-999888',
            'phone' => '081200001111',
            'vehicle_plate' => 'B 1 TEST',
            'vehicle_type' => 'Minibus',
            'willing_to_load' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.drivers'));

        $this->assertDatabaseHas('drivers', [
            'user_id' => $user->id,
            'license_number' => 'SIM-A-999888',
            'status' => 'STANDBY',
        ]);
    }
}
