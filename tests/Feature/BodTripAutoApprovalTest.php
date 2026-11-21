<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Driver;
use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BodTripAutoApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_bod_department_exists_after_seeding(): void
    {
        $bod = Department::where('code', 'BOD')->first();

        $this->assertNotNull($bod);
        $this->assertSame('Board of Directors', $bod->name);
    }

    public function test_trip_from_bod_department_is_automatically_approved(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $bodId = Department::where('code', 'BOD')->value('id');

        $trip = (new TripService)->createTrip([
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $bodId,
            'destination' => 'Jakarta',
            'purpose' => 'Rapat direksi',
            'departure_time' => now()->addDay()->setHour(8),
            'passenger_count' => 3,
            'priority' => 'URGENT',
        ], $superadmin);

        // Langsung APPROVED tanpa submit/approval
        $this->assertSame(TripRequest::STATUS_APPROVED, $trip->status);
        $this->assertSame($superadmin->id, $trip->approved_by);
        $this->assertNotNull($trip->approved_at);

        // Tidak dapat di-submit lagi (bukan draft)
        $this->expectException(\Exception::class);
        (new TripService)->submitTrip($trip->fresh(), $superadmin);
    }

    public function test_bod_trip_can_be_created_without_trip_details(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $bodId = Department::where('code', 'BOD')->value('id');

        // BOD tidak mengisi informasi perjalanan sama sekali
        $trip = (new TripService)->createTrip([
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $bodId,
        ], $superadmin);

        $this->assertSame(TripRequest::STATUS_APPROVED, $trip->status);
        $this->assertSame('Menunggu konfirmasi BOD', $trip->destination);
        $this->assertSame('Perjalanan Board of Directors', $trip->purpose);
        $this->assertSame(1, $trip->passenger_count);
        $this->assertNotNull($trip->departure_time);
        $this->assertNotNull($trip->trip_date);
    }

    public function test_validation_accepts_bod_request_without_trip_details(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $bodId = Department::where('code', 'BOD')->value('id');
        $driver = Driver::whereHas('user')->firstOrFail();

        // Informasi perjalanan dikosongkan; hanya departemen + driver
        $response = $this->actingAs($superadmin)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $bodId,
            'driver_id' => $driver->id,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('trip_requests', [
            'department_id' => $bodId,
            'status' => TripRequest::STATUS_DRIVER_ACCEPTED,
            'destination' => 'Menunggu konfirmasi BOD',
            'assigned_driver_id' => $driver->id,
        ]);
    }

    public function test_bod_request_without_driver_is_rejected(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $bodId = Department::where('code', 'BOD')->value('id');

        $response = $this->actingAs($superadmin)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $bodId,
        ]);

        // BOD wajib memilih driver saat membuat trip
        $response->assertSessionHasErrors(['driver_id']);
    }

    public function test_non_bod_request_still_requires_trip_details(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $nonBodId = Department::where('code', '!=', 'BOD')->value('id');

        $response = $this->actingAs($superadmin)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $nonBodId,
        ]);

        $response->assertSessionHasErrors(['destination', 'purpose', 'departure_time', 'passenger_count']);
    }

    public function test_bod_trip_with_driver_is_assigned_and_wa_link_returned(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $bodId = Department::where('code', 'BOD')->value('id');
        $driver = Driver::whereHas('user')->firstOrFail();

        $trip = (new TripService)->createTrip([
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $bodId,
        ], $superadmin);

        $meta = [];
        $result = (new TripService)->assignDriverForBod($trip->fresh(), $superadmin, $driver->id, $meta);

        // Trip langsung berstatus driver-accepted tanpa perlu konfirmasi driver
        $this->assertSame(TripRequest::STATUS_DRIVER_ACCEPTED, $result->status);
        $this->assertSame($driver->id, $result->assigned_driver_id);

        $assignment = $result->assignment;
        $this->assertNotNull($assignment);
        $this->assertSame(TripAssignment::RESPONSE_ACCEPTED, $assignment->driver_response);
        $this->assertNotNull($assignment->driver_accepted_at);

        // Tautan WA dibuat (mode link default) untuk dikirim manual
        $this->assertArrayHasKey('wa_link', $meta);
        $this->assertNotNull($meta['wa_link']);
        $this->assertStringContainsString('wa.me', $meta['wa_link']);

        // Audit log tercatat
        $this->assertDatabaseHas('audit_logs', [
            'record_type' => 'TripRequest',
            'record_id' => $trip->id,
            'action' => 'ASSIGN_DRIVER_BOD',
        ]);
    }

    public function test_bod_store_with_driver_redirects_and_persists_assignment(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $bodId = Department::where('code', 'BOD')->value('id');
        $driver = Driver::whereHas('user')->firstOrFail();

        $response = $this->actingAs($superadmin)->post(route('trips.store'), [
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $bodId,
            'driver_id' => $driver->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('trip_requests', [
            'department_id' => $bodId,
            'status' => TripRequest::STATUS_DRIVER_ACCEPTED,
            'assigned_driver_id' => $driver->id,
        ]);

        $this->assertDatabaseHas('trip_assignments', [
            'driver_id' => $driver->id,
            'driver_response' => TripAssignment::RESPONSE_ACCEPTED,
        ]);
    }

    public function test_trip_from_regular_department_still_requires_approval(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $nonBodId = Department::where('code', '!=', 'BOD')->value('id');

        $trip = (new TripService)->createTrip([
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $nonBodId,
            'destination' => 'Bandung',
            'purpose' => 'Kunjungan klien',
            'departure_time' => now()->addDay()->setHour(8),
            'passenger_count' => 2,
            'priority' => 'NORMAL',
        ], $superadmin);

        $this->assertSame(TripRequest::STATUS_DRAFT, $trip->status);
        $this->assertNull($trip->approved_at);
    }

    public function test_bod_trip_records_auto_approval_in_audit_log(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $bodId = Department::where('code', 'BOD')->value('id');

        $trip = (new TripService)->createTrip([
            'request_type' => 'BUSINESS_TRIP',
            'department_id' => $bodId,
            'destination' => 'Surabaya',
            'purpose' => 'Monitoring cabang',
            'departure_time' => now()->addDay()->setHour(9),
            'passenger_count' => 2,
            'priority' => 'NORMAL',
        ], $superadmin);

        $this->assertDatabaseHas('audit_logs', [
            'record_type' => 'TripRequest',
            'record_id' => $trip->id,
            'action' => 'AUTO_APPROVE_TRIP',
        ]);

        $this->assertDatabaseHas('trip_status_logs', [
            'trip_request_id' => $trip->id,
            'to_status' => TripRequest::STATUS_APPROVED,
        ]);
    }
}
