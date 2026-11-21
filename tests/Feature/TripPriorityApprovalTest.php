<?php

namespace Tests\Feature;

use App\Models\TripRequest;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripPriorityApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function makeTrip(array $attributes = []): TripRequest
    {
        $requestor = User::whereHas('role', fn ($q) => $q->where('name', 'ADMIN'))->firstOrFail();

        $base = [
            'trip_number' => 'TRIP-2026-9999',
            'requestor_id' => $requestor->id,
            'department_id' => $requestor->department_id,
            'destination' => 'Jakarta',
            'purpose' => 'Meeting klien',
            'departure_time' => now()->addDay()->setHour(8),
            'passenger_count' => 2,
            'priority' => TripRequest::PRIORITY_NORMAL,
            'status' => TripRequest::STATUS_WAITING_APPROVAL,
        ];

        $trip = new TripRequest(array_merge($base, $attributes));
        $trip->trip_number = 'TRIP-2026-'.str_pad((string) (TripRequest::max('id') + 999), 4, '0', STR_PAD_LEFT);
        $trip->save();

        return $trip;
    }

    public function test_admin_cannot_set_final_priority_directly(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('name', 'ADMIN'))->firstOrFail();

        $trip = (new TripService)->createTrip([
            'request_type' => 'BUSINESS_TRIP',
            'destination' => 'Surabaya',
            'purpose' => 'Kunjungan cabang',
            'trip_date' => now()->addDay()->toDateString(),
            'departure_time' => now()->addDay()->setHour(9)->setMinute(0),
            'passenger_count' => 3,
            // Admin mengirim URGENT, tetapi harus dipaksa menjadi NORMAL
            'priority' => 'VERY_URGENT',
            'requested_priority' => 'URGENT',
            'urgency_notes' => 'Jadwal klien berubah mendadak',
        ], $admin);

        // Prioritas FINAL tidak boleh URGENT/VERY_URGENT dari admin
        $this->assertSame(TripRequest::PRIORITY_NORMAL, $trip->priority);
        $this->assertSame('URGENT', $trip->requested_priority);
        $this->assertSame('Jadwal klien berubah mendadak', $trip->urgency_notes);

        $trip->delete();
    }

    public function test_only_superadmin_can_finalize_priority(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('name', 'ADMIN'))->firstOrFail();
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $employee = User::whereHas('role', fn ($q) => $q->where('name', 'EMPLOYEE'))->firstOrFail();
        $service = new TripService;

        $tripByAdmin = $this->makeTrip(['requested_priority' => 'URGENT', 'urgency_notes' => 'Klien mendadak']);
        $resultByAdmin = $service->approveTrip($tripByAdmin->fresh(), $admin, 'catatan', 'VERY_URGENT');
        $this->assertSame(TripRequest::PRIORITY_NORMAL, $resultByAdmin->priority);
        $this->assertNull($resultByAdmin->priority_set_by);

        $tripByEmployee = $this->makeTrip();
        $resultByEmployee = $service->approveTrip($tripByEmployee->fresh(), $employee, 'catatan', 'URGENT');
        $this->assertSame(TripRequest::PRIORITY_NORMAL, $resultByEmployee->priority);
        $this->assertNull($resultByEmployee->priority_set_by);

        $tripBySuper = $this->makeTrip(['requested_priority' => 'URGENT', 'urgency_notes' => 'Klien mendadak']);
        $resultBySuper = $service->approveTrip($tripBySuper->fresh(), $superadmin, 'setuju', 'VERY_URGENT');
        $this->assertSame('VERY_URGENT', $resultBySuper->priority);
        $this->assertSame('SUPERADMIN', $resultBySuper->priority_set_by);
        $this->assertSame('setuju', $resultBySuper->approval_notes);
    }

    public function test_superadmin_approve_request_requires_priority(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $trip = $this->makeTrip();

        $response = $this->actingAs($superadmin)->post(route('trips.approve', $trip->id), [
            'notes' => 'Tanpa prioritas',
        ]);

        $response->assertSessionHasErrors('priority');

        $trip->delete();
    }
}
