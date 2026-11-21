<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Role;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DriverAssignmentWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_assignment_returns_whatsapp_link(): void
    {
        $dispatcher = User::whereHas('role', fn ($q) => $q->where('name', 'DISPATCHER'))->firstOrFail();
        Auth::login($dispatcher);

        // Buat driver sendiri agar tes tidak bergantung pada data seeder acak
        $driverRole = Role::where('name', 'DRIVER')->firstOrFail();
        $driverUser = User::create([
            'name' => 'Driver WA',
            'email' => 'driver-wa-link@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $driverRole->id,
            'phone' => '081300000005',
            'is_active' => true,
        ]);
        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'license_number' => 'SIM-WA-LINK',
            'phone' => '081300000005',
            'status' => 'STANDBY',
            'willing_to_load' => true,
        ]);

        $vehicle = Vehicle::whereIn('status', ['AVAILABLE', 'ASSIGNED'])->firstOrFail();
        $driver->allowedVehicles()->syncWithoutDetaching([$vehicle->id]);

        $employee = User::where('email', 'employee1@example.com')->firstOrFail();
        $trip = TripRequest::create([
            'trip_number' => 'TRIP-WA-'.now()->format('His'),
            'requestor_id' => $employee->id,
            'department_id' => $employee->department_id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->addDay()->toDateString(),
            'departure_time' => now()->addDay()->setHour(8),
            'destination' => 'Semarang',
            'purpose' => 'Uji notifikasi WhatsApp penugasan',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_WAITING_DRIVER,
            'created_by' => $employee->id,
            'updated_by' => $employee->id,
        ]);

        $response = $this->actingAs($dispatcher)->post(route('trips.assign-driver', $trip->id), [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('whatsapp_link');

        $link = session('whatsapp_link');
        dump('whatsapp_link : '.substr((string) $link, 0, 110).'...');

        $this->assertIsString($link);
        $this->assertStringStartsWith('https://wa.me/', $link);
        $this->assertStringContainsString('PENUGASAN', urldecode($link));

        // Notifikasi driver tetap terbuat di database
        $this->assertGreaterThan(0, $driver->user->notifications()->count());

        $trip->forceDelete();
    }

    public function test_whatsapp_link_driver_build_mode(): void
    {
        config(['whatsapp.driver' => 'link']);

        $service = app(WhatsAppService::class);
        $result = $service->send('081234567890', 'Uji pesan');

        dump('link driver  : '.$result['driver']);
        dump('nomor        : '.($result['number'] ?? '-'));
        dump('tautan       : '.substr($result['link'] ?? '', 0, 80).'...');

        $this->assertTrue($result['ok']);
        $this->assertSame('link', $result['driver']);
        $this->assertSame('6281234567890', $result['number']);
        $this->assertStringStartsWith('https://wa.me/6281234567890', $result['link']);
    }
}
