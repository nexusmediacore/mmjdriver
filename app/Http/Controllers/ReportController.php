<?php

namespace App\Http\Controllers;

use App\Models\TripRequest;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', TripRequest::class);

        $dateFrom = request('date_from', now()->startOfMonth()->toDateString());
        $dateTo = request('date_to', now()->toDateString());

        $trips = TripRequest::with(['requestor', 'department', 'driver.user', 'vehicle'])
            ->whereDate('trip_date', '>=', $dateFrom)
            ->whereDate('trip_date', '<=', $dateTo)
            ->get();

        $stats = $this->buildStats($trips);

        return Inertia::render('Reports/Index', [
            'stats' => $stats,
            'byDepartment' => $this->groupBy($trips, 'department_id', 'department'),
            'byDriver' => $this->groupBy(
                $trips->where('assigned_driver_id', '>', 0),
                'assigned_driver_id',
                'driver.user'
            ),
            'byVehicle' => $this->groupBy(
                $trips->where('assigned_vehicle_id', '>', 0),
                'assigned_vehicle_id',
                'vehicle',
                ['brand', 'model']
            ),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    private function buildStats($trips): array
    {
        return [
            'total_trips' => $trips->count(),
            'completed_trips' => $trips->where('status', TripRequest::STATUS_COMPLETED)->count(),
            'cancelled_trips' => $trips->where('status', TripRequest::STATUS_CANCELLED)->count(),
            'pending_trips' => $trips->whereNotIn('status', [TripRequest::STATUS_COMPLETED, TripRequest::STATUS_CANCELLED, TripRequest::STATUS_REJECTED])->count(),
            'total_passengers' => $trips->sum('passenger_count'),
            'urgent_trips' => $trips->where('priority', TripRequest::PRIORITY_URGENT)->count() + $trips->where('priority', TripRequest::PRIORITY_VERY_URGENT)->count(),
        ];
    }

    private function groupBy($trips, string $groupKey, string $relationPath, string|array|null $labelKey = null): array
    {
        // Boleh beberapa atribut untuk dijadikan label, contoh: ['brand', 'model']
        $keys = is_array($labelKey) ? $labelKey : [$labelKey ?? 'name'];
        $primary = $keys[0];

        return $trips->groupBy($groupKey)->map(function ($group) use ($relationPath, $keys, $primary) {
            $related = data_get($group->first(), $relationPath);

            // Gabungkan atribut yang terisi, mis. "Toyota" + "Innova" -> "Toyota Innova"
            $label = collect($keys)
                ->map(fn ($k) => $related?->{$k} ?? null)
                ->filter()
                ->implode(' ');

            if ($label === '') {
                $label = 'Unknown';
            }

            return [
                $primary => $label,
                'name' => $label,
                'label' => $label,
                // Plat nomor tetap dibawa agar bisa dipakai bila diperlukan
                'plate_number' => $related?->plate_number,
                'brand' => $related?->brand,
                'model' => $related?->model,
                'count' => $group->count(),
            ];
        })->values()->toArray();
    }

    public function pdf()
    {
        $this->authorize('viewAny', TripRequest::class);

        $dateFrom = request('date_from', now()->startOfMonth()->toDateString());
        $dateTo = request('date_to', now()->toDateString());

        $trips = TripRequest::with(['requestor', 'department', 'driver.user', 'vehicle'])
            ->whereDate('trip_date', '>=', $dateFrom)
            ->whereDate('trip_date', '<=', $dateTo)
            ->get();

        $stats = $this->buildStats($trips);
        $byDepartment = $this->groupBy($trips, 'department_id', 'department');
        $byDriver = $this->groupBy(
            $trips->where('assigned_driver_id', '>', 0),
            'assigned_driver_id',
            'driver.user'
        );
        $byVehicle = $this->groupBy(
            $trips->where('assigned_vehicle_id', '>', 0),
            'assigned_vehicle_id',
            'vehicle',
            ['brand', 'model']
        );

        $pdf = PDF::loadView('pdf.report', [
            'trips' => $trips,
            'stats' => $stats,
            'byDepartment' => $byDepartment,
            'byDriver' => $byDriver,
            'byVehicle' => $byVehicle,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);

        return $pdf->download('laporan-perjalanan-'.$dateFrom.'-to-'.$dateTo.'.pdf');
    }
}
