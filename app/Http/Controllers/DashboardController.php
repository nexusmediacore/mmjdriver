<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Driver;
use App\Models\DriverAttendance;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();
        $role = $user->role->name;

        $common = [
            'stats' => $this->getStats(),
        ];

        return match ($role) {
            'SUPERADMIN', 'ADMIN' => Inertia::render('Admin/Dashboard', [
                'stats' => [
                    'total_users' => User::count(),
                    'total_drivers' => Driver::count(),
                    'total_vehicles' => Vehicle::count(),
                    'total_departments' => Department::count(),
                    'total_trips' => TripRequest::count(),
                    'active_trips' => TripRequest::whereIn('status', ['ON_TRIP', 'COMPLETION_SUBMITTED', 'DRIVER_ACCEPTED', 'READY_TO_DEPART', 'DRIVER_ASSIGNED'])->count(),
                    'waiting_completion_approval' => TripRequest::where('status', TripRequest::STATUS_COMPLETION_SUBMITTED)->count(),
                    'completed_trips' => TripRequest::where('status', 'COMPLETED')->count(),
                    'trips_this_month' => TripRequest::whereMonth('created_at', Carbon::now()->month)->count(),
                ],
                'trip_scale' => $this->getTripScaleData(),
                'driver_duration' => $this->getDriverDurationData(),
            ]),
            'DISPATCHER' => Inertia::render('Dispatcher/Dashboard', [
                'stats' => [
                    'total_requests_today' => TripRequest::whereDate('created_at', today())->count(),
                    'waiting_approval' => TripRequest::where('status', 'WAITING_APPROVAL')->count(),
                    'waiting_driver' => TripRequest::where('status', 'WAITING_DRIVER')->count(),
                    'driver_standby' => Driver::where('status', 'STANDBY')->count(),
                    'driver_on_trip' => Driver::where('status', 'ON_TRIP')->count(),
                    'vehicle_available' => Vehicle::where('status', 'AVAILABLE')->count(),
                    'active_trips' => TripRequest::whereIn('status', ['ON_TRIP', 'COMPLETION_SUBMITTED', 'DRIVER_ACCEPTED', 'READY_TO_DEPART', 'DRIVER_ASSIGNED'])->count(),
                    'waiting_completion_approval' => TripRequest::where('status', TripRequest::STATUS_COMPLETION_SUBMITTED)->count(),
                    'completed_trips' => TripRequest::whereDate('completed_at', today())->count(),
                    'urgent_requests' => TripRequest::whereIn('priority', ['URGENT', 'VERY_URGENT'])
                        ->whereIn('status', ['APPROVED', 'WAITING_DRIVER', 'DRIVER_ASSIGNED', 'DRIVER_ACCEPTED'])
                        ->count(),
                ],
                'drivers' => Driver::with('user')->get()->map(fn ($d) => [
                    'id' => $d->id,
                    'name' => $d->user->name,
                    'status' => $d->status,
                ]),
                'recent_requests' => TripRequest::with('requestor')->latest('created_at')->limit(5)->get()
                    ->map(fn ($t) => [
                        'id' => $t->id,
                        'trip_number' => $t->trip_number,
                        'destination' => $t->destination,
                        'status' => $t->status,
                        'priority' => $t->priority,
                        'created_at' => $t->created_at,
                    ]),
            ]),
            'APPROVER' => Inertia::render('Approver/Dashboard', [
                'pending_requests' => TripRequest::with(['requestor', 'department'])
                    ->where('status', 'WAITING_APPROVAL')
                    ->latest()
                    ->get()
                    ->map(fn ($t) => [
                        'id' => $t->id,
                        'trip_number' => $t->trip_number,
                        'destination' => $t->destination,
                        'purpose' => $t->purpose,
                        'departure_time' => $t->departure_time,
                        'passenger_count' => $t->passenger_count,
                        'priority' => $t->priority,
                        'requestor' => ['name' => $t->requestor->name],
                        'department' => ['name' => $t->department->name],
                    ]),
            ]),
            'EMPLOYEE' => Inertia::render('Employee/Dashboard', [
                'my_trips' => TripRequest::where('requestor_id', $user->id)
                    ->latest()
                    ->get()
                    ->map(fn ($t) => [
                        'id' => $t->id,
                        'trip_number' => $t->trip_number,
                        'destination' => $t->destination,
                        'purpose' => $t->purpose,
                        'departure_time' => $t->departure_time,
                        'status' => $t->status,
                        'priority' => $t->priority,
                    ]),
            ]),
            'DRIVER' => Inertia::render('Driver/Dashboard', [
                'driver' => $this->getDriverData($user),
                'active_task' => $this->getActiveDriverTask($user),
                'next_task' => $this->getNextDriverTask($user),
                'attendance_today' => $this->getAttendanceToday($user),
            ]),
            default => Inertia::render('Dashboard'),
        };
    }

    protected function getStats(): array
    {
        return [];
    }

    protected function getTripScaleData(): array
    {
        $tripsByDepartment = TripRequest::selectRaw('department_id, COUNT(*) as count')
            ->with('department')
            ->groupBy('department_id')
            ->get();

        return $tripsByDepartment->map(fn ($item) => [
            'department' => $item->department?->name ?? 'Unknown',
            'count' => $item->count,
        ])->values()->toArray();
    }

    protected function getDriverDurationData(): array
    {
        $completedTrips = TripRequest::where('status', 'COMPLETED')
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->with('driver.user')
            ->get();

        $durationByDriver = $completedTrips->filter(fn ($t) => $t->driver)
            ->groupBy('assigned_driver_id')
            ->map(fn ($group) => [
                'driver' => $group->first()->driver->user->name,
                'avg_duration_hours' => round(
                    $group->avg(function ($t) {
                        return Carbon::parse($t->started_at)->diffInMinutes(Carbon::parse($t->completed_at)) / 60;
                    }),
                    2
                ),
                'total_trips' => $group->count(),
            ])->values();

        return $durationByDriver->toArray();
    }

    protected function getDriverData($user): ?array
    {
        $driver = $user->driver()->with('user')->first();
        if (! $driver) {
            return null;
        }

        return [
            'id' => $driver->id,
            'name' => $driver->user->name,
            'status' => $driver->status,
            'license_number' => $driver->license_number,
            'willing_to_load' => $driver->willing_to_load,
            'phone' => $driver->phone,
            'vehicle_plate' => $driver->vehicle_plate,
        ];
    }

    protected function getActiveDriverTask($user): ?array
    {
        $driver = $user->driver;
        if (! $driver) {
            return null;
        }
        $trip = TripRequest::where(function ($query) use ($driver) {
            $query->where('assigned_driver_id', $driver->id)
                ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $driver->id));
        })
            ->whereIn('status', [
                'DRIVER_ASSIGNED', 'DRIVER_ACCEPTED', 'READY_TO_DEPART', 'ON_TRIP',
                TripRequest::STATUS_COMPLETION_SUBMITTED,
            ])
            ->orderBy('departure_time')
            ->first();

        if (! $trip) {
            return null;
        }

        return [
            'id' => $trip->id,
            'trip_number' => $trip->trip_number,
            'destination' => $trip->destination,
            'purpose' => $trip->purpose,
            'departure_time' => $trip->departure_time,
            'status' => $trip->status,
            'bod_link' => $trip->bod_link,
            'can_complete' => $trip->status === TripRequest::STATUS_ON_TRIP,
            'vehicle' => $trip->vehicle ? [
                'plate_number' => $trip->vehicle->plate_number,
                'brand' => $trip->vehicle->brand,
                'model' => $trip->vehicle->model,
            ] : null,
        ];
    }

    /**
     * Ringkasan absensi driver hari ini (untuk tombol cepat di dashboard).
     *
     * @return array{clocked_in: bool, clocked_out: bool, clock_in_at: ?string, clock_out_at: ?string}|null
     */
    protected function getAttendanceToday(User $user): ?array
    {
        $driver = $user->driver;

        if (! $driver) {
            return null;
        }

        $attendance = DriverAttendance::where('driver_id', $driver->id)
            ->whereDate('date', today())
            ->first();

        return [
            'clocked_in' => $attendance?->clock_in_at !== null,
            'clocked_out' => $attendance?->clock_out_at !== null,
            'clock_in_at' => $attendance?->clock_in_at?->toIso8601String(),
            'clock_out_at' => $attendance?->clock_out_at?->toIso8601String(),
        ];
    }

    protected function getNextDriverTask($user): ?array
    {
        $driver = $user->driver;
        if (! $driver) {
            return null;
        }
        $trip = TripRequest::where(function ($query) use ($driver) {
            $query->where('assigned_driver_id', $driver->id)
                ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $driver->id));
        })
            ->whereIn('status', ['DRIVER_ASSIGNED', 'READY_TO_DEPART'])
            ->orderBy('departure_time')
            ->first();

        if (! $trip) {
            return null;
        }

        return [
            'id' => $trip->id,
            'trip_number' => $trip->trip_number,
            'destination' => $trip->destination,
            'purpose' => $trip->purpose,
            'departure_time' => $trip->departure_time,
            'bod_link' => $trip->bod_link,
        ];
    }
}
