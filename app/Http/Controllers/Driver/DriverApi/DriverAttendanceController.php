<?php

namespace App\Http\Controllers\Driver\DriverApi;

use App\Http\Requests\DriverAttendanceRequest;
use App\Models\DriverAttendance;
use App\Services\DriverAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Absensi driver via API mobile (`/api/driver/attendance`).
 */
class DriverAttendanceController extends Controller
{
    public function __construct(
        protected DriverAttendanceService $attendanceService = new DriverAttendanceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if (! $driver) {
            return response()->json(['message' => 'Akun ini bukan driver.'], 403);
        }

        $today = $this->attendanceService->todayFor($driver);

        $history = DriverAttendance::where('driver_id', $driver->id)
            ->orderByDesc('date')
            ->limit(30)
            ->get()
            ->map(fn (DriverAttendance $a) => $this->format($a));

        return response()->json([
            'today' => $today ? $this->format($today) : null,
            'history' => $history,
        ]);
    }

    public function clockIn(DriverAttendanceRequest $request): JsonResponse
    {
        return $this->respond($request, 'clockIn', 'Absensi masuk berhasil dicatat.');
    }

    public function clockOut(DriverAttendanceRequest $request): JsonResponse
    {
        return $this->respond($request, 'clockOut', 'Absensi pulang berhasil dicatat.');
    }

    protected function respond(DriverAttendanceRequest $request, string $method, string $message): JsonResponse
    {
        $driver = $request->user()->driver;

        try {
            $attendance = $this->attendanceService->{$method}(
                $driver,
                $request->file('photo'),
                [
                    'lat' => $request->input('lat'),
                    'lng' => $request->input('lng'),
                    'notes' => $request->input('notes'),
                ],
                $request->user()
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $message,
            'attendance' => $this->format($attendance->load('driver.user')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function format(DriverAttendance $attendance): array
    {
        return [
            'id' => $attendance->id,
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
