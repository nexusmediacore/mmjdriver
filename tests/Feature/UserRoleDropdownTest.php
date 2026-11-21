<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_role_dropdown_contains_driver_option(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        $this->actingAs($superadmin)->get('/admin/users')
            ->assertInertia(fn ($page) => $page
                ->where('roles.5.name', 'DRIVER')
                ->where('roles.5.id', Role::where('name', 'DRIVER')->value('id')));
    }

    public function test_user_row_has_role_id_for_presearch(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();

        // Akun ber-role DRIVER yang belum terdaftar sebagai driver
        $driverUser = User::create([
            'name' => 'Fiqhi',
            'email' => 'fiqhi-role-test@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $driverRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)->get('/admin/users')
            ->assertInertia(function ($page) use ($driverUser) {
                $row = collect($page->toArray()['props']['users'])->firstWhere('id', $driverUser->id);

                dump('role_id pada baris user : '.var_export($row['role_id'] ?? null, true));
                dump('role (nama)             : '.var_export($row['role'] ?? null, true));

                // role_id WAJIB ada agar dropdown Role bisa menampilkan pilihan
                $this->assertSame($driverUser->role_id, $row['role_id']);
                $this->assertSame('DRIVER', $row['role']);

                return true;
            });
    }

    public function test_update_user_can_change_role_to_driver(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();

        // Ubah user EMPLOYEE menjadi DRIVER
        $employee = User::whereHas('role', fn ($q) => $q->where('name', 'EMPLOYEE'))
            ->whereDoesntHave('driver')
            ->firstOrFail();

        $response = $this->actingAs($superadmin)->put(route('admin.users.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'role_id' => (string) $driverRole->id,
            'phone' => $employee->phone,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame($driverRole->id, $employee->fresh()->role_id);
    }

    public function test_edit_user_submission_succeeds(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();

        // Buat akun DRIVER sendiri agar tes tidak bergantung pada seeder
        $user = User::create([
            'name' => 'Fiqhi',
            'email' => 'fiqhi-edit-test@example.com',
            'password' => bcrypt('passwordasli'),
            'role_id' => $driverRole->id,
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        // Payload persis seperti FormModal dalam mode EDIT (password dibiarkan kosong)
        $response = $this->actingAs($superadmin)->put(route('admin.users.update', $user->id), [
            'name' => 'Fiqhi Ibrahim',
            'email' => 'fiqhi-edit-test@example.com',
            'role_id' => (string) $driverRole->id,
            'department_id' => '',
            'phone' => '081234567890',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users'));

        // Password lama harus tetap utuh
        $this->assertNotSame('', $user->fresh()->password);
        $this->assertSame('Fiqhi Ibrahim', $user->fresh()->name);
    }
}
