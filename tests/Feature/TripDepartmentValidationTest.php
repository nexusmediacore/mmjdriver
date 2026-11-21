<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\TripRequest;
use App\Models\User;
use App\Notifications\TripCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TripDepartmentValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_without_department_gets_clear_error(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        // Lepas departemen untuk menguji penjagaan
        $superadmin->update(['department_id' => null]);

        $employeeRole = Role::where('name', 'EMPLOYEE')->firstOrFail();
        $pemohon = User::create([
            'name' => 'Pemohon Tanpa Dept',
            'email' => 'no-dept@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $employeeRole->id,
            'department_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($pemohon)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'destination' => 'Jakarta',
            'purpose' => 'Uji tanpa departemen',
            'departure_time' => now()->addDay()->setHour(9)->format('Y-m-d H:i'),
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'passengers' => [['name' => 'Pemohon']],
        ]);

        $response->assertSessionHasErrors('department_id');
        $this->assertStringContainsString(
            'belum memiliki departemen',
            session('errors')->first('department_id')
        );

        // Bukan SQLSTATE mentah
        $this->assertNull(session('exception') ?? null);
    }

    public function test_user_with_department_can_create_trip(): void
    {
        $employee = User::where('email', 'employee1@example.com')->firstOrFail();

        $response = $this->actingAs($employee)->post(route('trips.store'), [
            'request_type' => 'GUEST_PICKUP',
            'department_id' => '',
            'destination' => 'kantor',
            'guest_pickup_location' => 'bandara',
            'guest_institution' => 'pt mmj',
            'purpose' => 'menjemput tamu',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addDay()->setHour(13)->format('Y-m-d H:i'),
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'passengers' => [['name' => 'Tamu']],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('trip_requests', [
            'destination' => 'kantor',
            'guest_pickup_location' => 'bandara',
            'guest_institution' => 'pt mmj',
        ]);
    }

    public function test_superadmin_can_create_trip_for_a_selected_department(): void
    {
        $superadmin = User::where('email', 'superadmin@example.com')->firstOrFail();
        $department = Department::where('code', 'OPS')->firstOrFail();
        Notification::fake();

        $response = $this->actingAs($superadmin)->post(route('trips.store'), [
            'request_type' => 'GUEST_PICKUP',
            'department_id' => (string) $department->id,
            'destination' => 'kantor',
            'guest_pickup_location' => 'bandara',
            'guest_institution' => 'pt mmj',
            'purpose' => 'menjemput tamu',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addDay()->setHour(13)->format('Y-m-d H:i'),
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'passengers' => [['name' => 'Tamu']],
        ]);

        $trip = TripRequest::where('destination', 'kantor')->latest('id')->firstOrFail();

        $response->assertRedirect(route('trips.show', $trip));
        $this->assertDatabaseHas('trip_requests', [
            'id' => $trip->id,
            'department_id' => $department->id,
            'requestor_id' => $superadmin->id,
        ]);
        Notification::assertSentTo($superadmin, TripCreatedNotification::class);
    }

    public function test_superadmin_must_select_a_department_before_creating_a_trip(): void
    {
        $superadmin = User::where('email', 'superadmin@example.com')->firstOrFail();

        $response = $this->actingAs($superadmin)->post(route('trips.store'), [
            'request_type' => 'GUEST_PICKUP',
            'destination' => 'Tujuan Tanpa Departemen',
            'guest_pickup_location' => 'bandara',
            'guest_institution' => 'pt mmj',
            'purpose' => 'menjemput tamu',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->addDay()->setHour(13)->format('Y-m-d H:i'),
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'passengers' => [['name' => 'Tamu']],
        ]);

        $response->assertSessionHasErrors([
            'department_id' => 'Departemen wajib dipilih.',
        ]);
        $this->assertDatabaseMissing('trip_requests', [
            'destination' => 'Tujuan Tanpa Departemen',
            'requestor_id' => $superadmin->id,
        ]);
    }

    public function test_employee_cannot_assign_trip_to_another_department(): void
    {
        $employee = User::where('email', 'employee1@example.com')->firstOrFail();
        $otherDepartment = Department::where('id', '!=', $employee->department_id)->firstOrFail();

        $response = $this->actingAs($employee)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $otherDepartment->id,
            'destination' => 'Tidak Boleh Pindah Departemen',
            'purpose' => 'Uji pembatasan departemen',
            'departure_time' => now()->addDay()->setHour(9)->format('Y-m-d H:i'),
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'passengers' => [['name' => 'Pemohon']],
        ]);

        $response->assertSessionHasErrors('department_id');
        $this->assertDatabaseMissing('trip_requests', [
            'destination' => 'Tidak Boleh Pindah Departemen',
            'requestor_id' => $employee->id,
        ]);
    }

    public function test_all_users_now_have_department(): void
    {
        User::whereHas('role')->get()->each(function ($user) {
            if (in_array($user->role?->name, ['ADMIN', 'EMPLOYEE'])) {
                $this->assertNotNull($user->department_id, "User {$user->email} harus punya departemen");
            }
        });
    }
}
