<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_invalid_phone_is_rejected_with_clear_error(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();

        // Telepon berisi email (kesalahan yang dilakukan pengguna)
        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Fiqhi',
            'email' => 'fiqhi.ibrahim11@gmail.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role_id' => $driverRole->id,
            'phone' => 'fiqhi.ibrahim11@gmail.com',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertFalse(User::where('email', 'fiqhi.ibrahim11@gmail.com')->exists());
    }

    public function test_valid_phone_is_accepted(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();

        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Fiqhi Ibrahim',
            'email' => 'fiqhi.ibrahim11@gmail.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role_id' => $driverRole->id,
            'phone' => '081234567890',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(User::where('email', 'fiqhi.ibrahim11@gmail.com')->exists());
    }

    public function test_required_fields_missing_shows_errors(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => '',
            'email' => '',
            'role_id' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'role_id']);
    }
}
