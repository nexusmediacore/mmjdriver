<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Driver;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TripRequestSeeder extends Seeder
{
    public function run(): void
    {
        $employee = User::whereHas('role', function ($q) {
            $q->where('name', 'EMPLOYEE');
        })->first();

        $department = Department::first();

        $drivers = Driver::with('user')->get();

        $statuses = [
            'COMPLETED', 'COMPLETED', 'ON_TRIP', 'APPROVED',
            'WAITING_APPROVAL', 'DRIVER_ASSIGNED', 'CANCELLED',
        ];

        for ($i = 1; $i <= 20; $i++) {
            $status = $statuses[array_rand($statuses)];
            $tripDate = Carbon::today()->subDays(rand(0, 5));
            $departure = $tripDate->copy()->setHour(rand(8, 10))->setMinute(0);

            $driver = null;
            if (in_array($status, ['COMPLETED', 'ON_TRIP', 'APPROVED', 'DRIVER_ASSIGNED']) && $drivers->isNotEmpty()) {
                $driver = $drivers->random();
            }

            $startedAt = null;
            $completedAt = null;
            $vehicleId = null;

            if (in_array($status, ['COMPLETED', 'ON_TRIP']) && $driver && $startedAt === null) {
                $startedAt = $departure->copy()->addHours(rand(0, 2));
                if ($status === 'COMPLETED') {
                    $completedAt = $startedAt->copy()->addHours(rand(1, 6));
                }
                $vehicle = Vehicle::inRandomOrder()->first();
                $vehicleId = $vehicle ? $vehicle->id : null;
            }

            TripRequest::firstOrCreate(
                ['trip_number' => 'TRIP-'.str_pad($i, 5, '0', STR_PAD_LEFT)],
                [
                    'requestor_id' => $employee->id,
                    'department_id' => $department->id,
                    'trip_date' => $tripDate->toDateString(),
                    'departure_time' => $departure->toDateTimeString(),
                    'estimated_return_time' => $departure->copy()->addHours(4)->toDateTimeString(),
                    'destination' => ['Jakarta', 'Bandung', 'Surabaya', 'Yogyakarta', 'Medan'][$i % 5],
                    'destination_address' => 'Jl. Dummy No. '.$i,
                    'purpose' => 'Kunjungan klien / rapat internal',
                    'passenger_count' => rand(1, 4),
                    'priority' => ['NORMAL', 'NORMAL', 'URGENT'][$i % 3],
                    'requires_loading_assistance' => $i % 3 === 0,
                    'status' => $status,
                    'created_by' => $employee->id,
                    'updated_by' => $employee->id,
                    'assigned_driver_id' => $driver ? $driver->id : null,
                    'assigned_vehicle_id' => $vehicleId,
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                ]
            );
        }
    }
}
