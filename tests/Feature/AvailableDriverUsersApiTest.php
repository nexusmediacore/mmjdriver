<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailableDriverUsersApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_api_returns_driver_role_users_without_driver_record(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();

        // Buat akun DRIVER yang belum jadi driver
        User::create([
            'name' => 'Fiqhi',
            'email' => 'fiqhi-api@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $driverRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->getJson(route('admin.drivers.available-users'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'count',
            'users' => [['id', 'name', 'email']],
        ]);

        dump('count : '.$response->json('count'));
        dump('users : '.json_encode($response->json('users')));

        $this->assertTrue(
            collect($response->json('users'))->pluck('email')->contains('fiqhi-api@example.com')
        );
    }

    public function test_api_excludes_existing_drivers_and_other_roles(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        $response = $this->actingAs($superadmin)->getJson(route('admin.drivers.available-users'));
        $response->assertStatus(200);

        $emails = collect($response->json('users'))->pluck('email');

        // Sudah menjadi driver -> tidak boleh ada
        foreach (Driver::pluck('user_id') as $userId) {
            $driverUser = User::find($userId);
            if ($driverUser) {
                $this->assertFalse($emails->contains($driverUser->email), "Driver existing {$driverUser->email} tak boleh muncul");
            }
        }

        // Non-DRIVER -> tidak boleh ada
        foreach (['admin@example.com', 'dispatcher1@example.com', 'employee1@example.com', 'superadmin@example.com', 'approver1@example.com'] as $email) {
            $this->assertFalse($emails->contains($email), "Non-driver {$email} tak boleh muncul");
        }

        dump('count akhir : '.$response->json('count'));
    }
}
