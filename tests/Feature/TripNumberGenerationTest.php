<?php

namespace Tests\Feature;

use App\Models\TripRequest;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripNumberGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_trip_number_always_uses_year_format(): void
    {
        $service = new TripService;

        // Tanpa data existing sama sekali
        $number = $service->generateTripNumber();
        $this->assertMatchesRegularExpression('/^TRIP-\d{4}-\d{4}$/', $number);
        $this->assertStringContainsString('TRIP-'.now()->year.'-', $number);
        $this->assertSame('TRIP-'.now()->year.'-0001', $number);

        // Buat trip dengan nomor tersebut, lalu generator harus lanjut ke 0002
        $user = User::where('email', 'employee1@example.com')->firstOrFail();
        TripRequest::create([
            'trip_number' => $number,
            'requestor_id' => $user->id,
            'department_id' => $user->department_id,
            'destination' => 'Jakarta',
            'purpose' => 'Meeting',
            'departure_time' => now()->addDay()->setHour(8),
            'priority' => TripRequest::PRIORITY_NORMAL,
            'status' => TripRequest::STATUS_DRAFT,
        ]);

        $this->assertSame('TRIP-'.now()->year.'-0002', $service->generateTripNumber());
    }

    public function test_sequence_continues_from_highest_in_current_year(): void
    {
        $user = User::where('email', 'employee1@example.com')->firstOrFail();
        $year = now()->year;

        TripRequest::create([
            'trip_number' => "TRIP-{$year}-0042",
            'requestor_id' => $user->id,
            'department_id' => $user->department_id,
            'destination' => 'Bandung',
            'purpose' => 'Survey',
            'departure_time' => now()->addDay()->setHour(8),
            'priority' => TripRequest::PRIORITY_NORMAL,
            'status' => TripRequest::STATUS_DRAFT,
        ]);

        $this->assertSame("TRIP-{$year}-0043", (new TripService)->generateTripNumber());
    }

    public function test_generated_number_is_unique_against_soft_deleted_records(): void
    {
        $user = User::where('email', 'employee1@example.com')->firstOrFail();
        $year = now()->year;

        $trip = TripRequest::create([
            'trip_number' => "TRIP-{$year}-0001",
            'requestor_id' => $user->id,
            'department_id' => $user->department_id,
            'destination' => 'Semarang',
            'purpose' => 'Kunjungan',
            'departure_time' => now()->addDay()->setHour(8),
            'priority' => TripRequest::PRIORITY_NORMAL,
            'status' => TripRequest::STATUS_DRAFT,
        ]);
        $trip->delete(); // soft delete — nomor tetap terpakai

        $this->assertSame("TRIP-{$year}-0002", (new TripService)->generateTripNumber());
    }
}
