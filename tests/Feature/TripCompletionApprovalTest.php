<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Driver;
use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penyelesaian perjalanan dual trigger (Driver & Employee) —
 * langsung COMPLETED tanpa approval Admin/SuperAdmin.
 */
class TripCompletionApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * @return array{0: TripRequest, 1: Driver, 2: User}
     */
    protected function makeOnTripTrip(): array
    {
        $driver = Driver::where('license_number', 'LIC-001')->firstOrFail();
        $driver->update(['status' => Driver::STATUS_ON_TRIP]);
        $vehicle = Vehicle::where('plate_number', 'B 1001 AA')->firstOrFail();
        $employee = User::where('email', 'employee1@example.com')->firstOrFail();

        $trip = TripRequest::create([
            'trip_number' => 'TRIP-CMP-'.uniqid(),
            'requestor_id' => $employee->id,
            'department_id' => $employee->department_id ?? Department::first()->id,
            'request_type' => 'BUSINESS_TRIP',
            'trip_date' => now()->toDateString(),
            'departure_time' => now()->subHours(2),
            'destination' => 'Solo',
            'purpose' => 'Uji penyelesaian perjalanan',
            'passenger_count' => 1,
            'priority' => 'NORMAL',
            'status' => TripRequest::STATUS_ON_TRIP,
            'assigned_driver_id' => $driver->id,
            'assigned_vehicle_id' => $vehicle->id,
            'started_at' => now()->subHours(2),
            'started_by' => $driver->user_id,
            'created_by' => $employee->id,
            'updated_by' => $employee->id,
        ]);

        $trip->assignments()->create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'role' => TripAssignment::ROLE_MAIN,
            'assigned_by' => $employee->id,
            'assigned_at' => now(),
            'driver_accepted_at' => now(),
            'driver_response' => 'ACCEPTED',
        ]);

        return [$trip, $driver, $employee];
    }

    public function test_driver_can_complete_trip_directly_without_approval(): void
    {
        [$trip, $driver] = $this->makeOnTripTrip();

        $response = $this->actingAs($driver->user)->post(route('trips.complete', $trip), [
            'notes' => 'Kembali ke kantor dengan aman',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $trip->refresh();

        $this->assertSame(TripRequest::STATUS_COMPLETED, $trip->status);
        $this->assertNotNull($trip->completed_at);
        $this->assertSame($driver->user_id, $trip->completed_by);
        $this->assertSame('Kembali ke kantor dengan aman', $trip->completion_notes);
        $this->assertNotNull($trip->actual_return_time);

        // Riwayat status & audit tercatat
        $this->assertDatabaseHas('trip_status_logs', [
            'trip_request_id' => $trip->id,
            'to_status' => TripRequest::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'record_type' => 'TripRequest',
            'record_id' => $trip->id,
            'action' => 'COMPLETE_TRIP',
        ]);
    }

    public function test_employee_requestor_can_complete_trip_when_driver_forgets(): void
    {
        [$trip, $driver, $employee] = $this->makeOnTripTrip();

        // Buang trip bawaan seeder lain yang masih mengikat driver ini.
        TripRequest::where(function ($q) use ($driver) {
            $q->where('assigned_driver_id', $driver->id)
                ->orWhereHas('assignments', fn ($sub) => $sub->where('driver_id', $driver->id));
        })
            ->whereKeyNot($trip->id)
            ->whereIn('status', [TripRequest::STATUS_ON_TRIP, TripRequest::STATUS_COMPLETION_SUBMITTED])
            ->delete();

        $response = $this->actingAs($employee)->post(route('trips.complete', $trip), [
            'notes' => 'Perjalanan sudah selesai, driver lupa menekan tombol',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $trip->refresh();

        $this->assertSame(TripRequest::STATUS_COMPLETED, $trip->status);
        $this->assertSame($employee->id, $trip->completed_by);
        $this->assertSame(Driver::STATUS_STANDBY, $driver->fresh()->status);
    }

    public function test_driver_becomes_standby_and_vehicle_available_after_completion(): void
    {
        [$trip, $driver] = $this->makeOnTripTrip();
        $vehicle = Vehicle::findOrFail($trip->assigned_vehicle_id);

        // Buang trip bawaan seeder lain yang masih mengikat driver ini agar
        // status driver benar-benar dipengaruhi trip yang sedang diuji.
        TripRequest::where(function ($q) use ($driver) {
            $q->where('assigned_driver_id', $driver->id)
                ->orWhereHas('assignments', fn ($sub) => $sub->where('driver_id', $driver->id));
        })
            ->whereKeyNot($trip->id)
            ->whereIn('status', [TripRequest::STATUS_ON_TRIP, TripRequest::STATUS_COMPLETION_SUBMITTED])
            ->delete();

        $this->actingAs($driver->user)->post(route('trips.complete', $trip));

        $this->assertSame(Driver::STATUS_STANDBY, $driver->fresh()->status);
        $this->assertSame('AVAILABLE', $vehicle->fresh()->status);
    }

    public function test_other_employee_cannot_complete_trip(): void
    {
        [$trip] = $this->makeOnTripTrip();
        $other = User::where('email', 'employee2@example.com')->firstOrFail();

        $this->actingAs($other)
            ->post(route('trips.complete', $trip))
            ->assertForbidden();

        $this->assertSame(TripRequest::STATUS_ON_TRIP, $trip->fresh()->status);
    }

    public function test_cannot_complete_trip_twice(): void
    {
        [$trip, $driver, $employee] = $this->makeOnTripTrip();

        $this->actingAs($driver->user)->post(route('trips.complete', $trip), ['notes' => 'Pertama']);

        // Sisi lain (pemohon) tidak bisa menekan lagi karena sudah COMPLETED.
        $this->actingAs($employee)
            ->post(route('trips.complete', $trip), ['notes' => 'Kedua'])
            ->assertForbidden();

        $this->assertSame(TripRequest::STATUS_COMPLETED, $trip->fresh()->status);
        $this->assertSame('Pertama', $trip->fresh()->completion_notes);
    }

    public function test_cannot_complete_trip_before_start(): void
    {
        [$trip, $driver] = $this->makeOnTripTrip();
        $trip->update(['started_at' => null, 'started_by' => null]);

        $response = $this->actingAs($driver->user)
            ->post(route('trips.complete', $trip));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(TripRequest::STATUS_ON_TRIP, $trip->fresh()->status);
    }

    public function test_driver_and_requestor_are_notified_after_completion(): void
    {
        [$trip, $driver, $employee] = $this->makeOnTripTrip();

        // Employee pemohon yang menyelesaikan — driver harus tetap diberi tahu.
        $this->actingAs($employee)->post(route('trips.complete', $trip));

        foreach ([$trip->fresh()->requestor, $driver->user] as $notifiable) {
            $this->assertTrue(
                $notifiable->notifications()->get()->contains(fn ($n) => $n['data']['type'] === 'trip_completion_approved'),
                "Notifikasi penyelesaian tidak diterima oleh {$notifiable->name}"
            );
        }
    }

    public function test_berita_acara_only_after_trip_is_completed(): void
    {
        [$trip, $driver] = $this->makeOnTripTrip();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        // Belum COMPLETED -> BA belum bisa dibuat
        $this->actingAs($admin)->post(route('trips.berita-acara.generate', $trip));
        $this->assertDatabaseMissing('berita_acaras', ['trip_request_id' => $trip->id]);

        $this->actingAs($driver->user)->post(route('trips.complete', $trip));

        $this->actingAs($admin)->post(route('trips.berita-acara.generate', $trip));
        $this->assertDatabaseHas('berita_acaras', ['trip_request_id' => $trip->id]);
    }
}
