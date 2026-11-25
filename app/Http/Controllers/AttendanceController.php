<?php

namespace App\Http\Controllers;

use App\Http\Requests\DriverAttendanceRequest;
use App\Models\Driver;
use App\Models\DriverAttendance;
use App\Services\AttendanceRecapService;
use App\Services\DriverAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(
        protected DriverAttendanceService $attendanceService = new DriverAttendanceService,
    ) {}

    /**
     * Halaman absensi driver (check-in / check-out dengan foto selfie).
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $driver = $user->driver;

        if ($driver) {
            $today = $this->attendanceService->todayFor($driver);

            $history = DriverAttendance::where('driver_id', $driver->id)
                ->orderByDesc('date')
                ->limit(30)
                ->get()
                ->map(fn (DriverAttendance $a) => $this->formatAttendance($a));

            $activeTrip = $driver->activeTrip();

            return Inertia::render('Driver/Attendance', [
                'driver' => [
                    'id' => $driver->id,
                    'name' => $user->name,
                    'status' => $driver->status,
                    'is_on_trip' => $driver->isOnTrip(),
                    'active_trip' => $activeTrip ? [
                        'trip_number' => $activeTrip->trip_number,
                        'destination' => $activeTrip->destination,
                        'status' => $activeTrip->status,
                    ] : null,
                ],
                'today' => $today ? $this->formatAttendance($today) : null,
                'history' => $history,
                'office' => [
                    'latitude' => DriverAttendanceService::OFFICE_LATITUDE,
                    'longitude' => DriverAttendanceService::OFFICE_LONGITUDE,
                    'street_name' => DriverAttendanceService::OFFICE_STREET_NAME,
                    'max_radius_meters' => DriverAttendanceService::MAX_RADIUS_METERS,
                    'cutoff_time' => sprintf('%02d:%02d WIB', DriverAttendanceService::CUTOFF_HOUR, DriverAttendanceService::CUTOFF_MINUTE),
                ],
            ]);
        }

        // Admin / Superadmin / Dispatcher -> rekap absensi seluruh driver
        return $this->recap($request);
    }

    public function clockIn(DriverAttendanceRequest $request): RedirectResponse
    {
        try {
            $this->attendanceService->clockIn(
                $request->user()->driver,
                $request->file('photo'),
                $request->only(['lat', 'lng', 'notes'])
            );

            return back()->with('success', 'Absensi masuk berhasil dicatat.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }
    }

    public function clockOut(DriverAttendanceRequest $request): RedirectResponse
    {
        try {
            $this->attendanceService->clockOut(
                $request->user()->driver,
                $request->file('photo'),
                $request->only(['lat', 'lng', 'notes'])
            );

            return back()->with('success', 'Absensi pulang berhasil dicatat.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tampilkan foto selfie absensi — hanya untuk driver pemilik data,
     * Admin / Superadmin, dan Dispatcher.
     */
    public function photo(Request $request, DriverAttendance $attendance, string $type = 'in'): StreamedResponse
    {
        $user = Auth::user();
        $role = $user->role?->name;

        $isOwner = $user->driver && (int) $user->driver->id === (int) $attendance->driver_id;
        $isManagement = in_array($role, ['SUPERADMIN', 'ADMIN', 'DISPATCHER'], true);

        abort_unless($isOwner || $isManagement, 403);

        // Tipe foto dikirim dari frontend sebagai query string (?type=out),
        // bukan segmen route. Bila tidak dibaca dari query, nilai selalu
        // default 'in' dan foto pulang menampilkan foto masuk.
        $type = $request->query('type', $type) === 'out' ? 'out' : 'in';

        $path = $type === 'out'
            ? $attendance->clock_out_photo_path
            : $attendance->clock_in_photo_path;

        abort_if(! $path || ! Storage::disk(DriverAttendance::DISK)->exists($path), 404);

        return Storage::disk(DriverAttendance::DISK)->response($path, null, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Rekap absensi (Admin, Superadmin, Dispatcher).
     * Mendukung mode daily / weekly / monthly / yearly + anchor tanggal.
     */
    protected function recap(Request $request): Response
    {
        abort_unless(
            in_array(Auth::user()->role?->name, ['SUPERADMIN', 'ADMIN', 'DISPATCHER'], true),
            403
        );

        $mode = $request->query('mode', AttendanceRecapService::MODE_DAILY);
        if (! array_key_exists($mode, AttendanceRecapService::modes())) {
            $mode = AttendanceRecapService::MODE_DAILY;
        }

        $anchor = $request->query('anchor');
        $anchor = $anchor ? Carbon::parse($anchor)->toDateString() : Carbon::today()->toDateString();

        $recapService = app(AttendanceRecapService::class);
        [$start, $end, $label] = $recapService->range($mode, $anchor);

        $rows = $recapService->rows($mode, $anchor);
        $summary = $recapService->summary($mode, $anchor);

        return Inertia::render('Admin/DriverAttendances', [
            'mode' => $mode,
            'anchor' => $anchor,
            'period_label' => $label,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'rows' => $rows,
            'summary' => [
                'total_drivers' => $summary['total_drivers'],
                'present' => $summary['present'],
                'on_time' => $summary['present'] - $summary['late'],
                'late' => $summary['late'],
                'clocked_out' => $summary['clocked_out'],
                'on_trip' => Driver::with('user')->get()->filter(fn (Driver $d) => $d->isOnTrip())->count(),
                'absent' => max(0, $summary['total_drivers'] - count($rows)),
            ],
            'office' => [
                'latitude' => DriverAttendanceService::OFFICE_LATITUDE,
                'longitude' => DriverAttendanceService::OFFICE_LONGITUDE,
                'max_radius_meters' => DriverAttendanceService::MAX_RADIUS_METERS,
                'cutoff_time' => sprintf('%02d:%02d WIB', DriverAttendanceService::CUTOFF_HOUR, DriverAttendanceService::CUTOFF_MINUTE),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatAttendance(DriverAttendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'driver_id' => $attendance->driver_id,
            'driver_name' => $attendance->driver?->user?->name,
            'driver_status' => $attendance->driver?->status,
            'date' => $attendance->date?->toDateString(),
            'clock_in_at' => $attendance->clock_in_at?->toIso8601String(),
            'clock_out_at' => $attendance->clock_out_at?->toIso8601String(),
            'clock_in_photo_timestamp' => $attendance->clock_in_photo_timestamp?->toIso8601String(),
            'clock_out_photo_timestamp' => $attendance->clock_out_photo_timestamp?->toIso8601String(),
            'has_clock_in_photo' => (bool) $attendance->clock_in_photo_path,
            'has_clock_out_photo' => (bool) $attendance->clock_out_photo_path,
            'clock_in_lat' => $attendance->clock_in_lat,
            'clock_in_lng' => $attendance->clock_in_lng,
            'clock_in_distance_meters' => $attendance->clock_in_distance_meters,
            'is_late' => (bool) $attendance->is_late,
            'lateness_minutes' => (int) ($attendance->lateness_minutes ?? 0),
            'clock_out_lat' => $attendance->clock_out_lat,
            'clock_out_lng' => $attendance->clock_out_lng,
            'notes' => $attendance->notes,
            'work_duration_minutes' => $attendance->workDurationMinutes(),
        ];
    }
}
