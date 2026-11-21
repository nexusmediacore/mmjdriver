<?php

namespace Tests\Feature;

use App\Models\TripRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripIndexSortingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_superadmin_can_sort_trips_by_column(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        $employee = User::where('email', 'employee1@example.com')->firstOrFail();
        $department = $employee->department;

        TripRequest::create([
            'trip_number' => 'TRIP-2026-0001',
            'requestor_id' => $employee->id,
            'department_id' => $department->id,
            'destination' => 'Zzdestination',
            'purpose' => 'Meeting',
            'departure_time' => now()->addDays(2)->setHour(8),
            'priority' => TripRequest::PRIORITY_NORMAL,
            'status' => TripRequest::STATUS_DRAFT,
        ]);

        TripRequest::create([
            'trip_number' => 'TRIP-2026-0002',
            'requestor_id' => $employee->id,
            'department_id' => $department->id,
            'destination' => 'Aadestination',
            'purpose' => 'Survey',
            'departure_time' => now()->addDays(1)->setHour(8),
            'priority' => TripRequest::PRIORITY_URGENT,
            'status' => TripRequest::STATUS_SUBMITTED,
        ]);

        // Urut destination ASC: destinasi 'Aa...' harus paling awal
        $response = $this->actingAs($superadmin)->get(route('trips.index', ['sort_by' => 'destination', 'sort_dir' => 'asc']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Trips/Index')
            ->where('trips.data.0.destination', 'Aadestination')
            ->etc());

        // Urut destination DESC: destinasi 'Zz...' harus paling awal
        $response = $this->actingAs($superadmin)->get(route('trips.index', ['sort_by' => 'destination', 'sort_dir' => 'desc']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Trips/Index')
            ->where('trips.data.0.destination', 'Zzdestination')
            ->etc());
    }

    public function test_invalid_sort_parameter_falls_back_to_default(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        $response = $this->actingAs($superadmin)->get(route('trips.index', ['sort_by' => 'not-a-column', 'sort_dir' => 'sideways']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Trips/Index'));
    }
}
