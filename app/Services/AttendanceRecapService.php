<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverAttendance;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Agregasi rekap absensi driver per periode:
 * daily / weekly / monthly / yearly. Logika pencatatan absensi
 * (DriverAttendanceService) tetap tidak berubah.
 */
class AttendanceRecapService
{
    public const MODE_DAILY = 'daily';

    public const MODE_WEEKLY = 'weekly';

    public const MODE_MONTHLY = 'monthly';

    public const MODE_YEARLY = 'yearly';

    /**
     * Hitung rentang [start, end] untuk mode & anchor tanggal.
     *
     * @return array{start: CarbonImmutable, end: CarbonImmutable, label: string}
     */
    public function range(string $mode, ?string $anchor = null): array
    {
        $anchor = CarbonImmutable::parse($anchor ?? Carbon::today()->toDateString());

        return match ($mode) {
            self::MODE_WEEKLY => [
                $anchor->startOfWeek(Carbon::MONDAY),
                $anchor->endOfWeek(Carbon::SUNDAY),
                'Minggu '.$anchor->startOfWeek(Carbon::MONDAY)->format('d M').' - '.$anchor->endOfWeek(Carbon::SUNDAY)->format('d M Y'),
            ],
            self::MODE_MONTHLY => [
                $anchor->startOfMonth(),
                $anchor->endOfMonth(),
                $anchor->translatedFormat('F Y'),
            ],
            self::MODE_YEARLY => [
                $anchor->startOfYear(),
                $anchor->endOfYear(),
                (string) $anchor->year,
            ],
            default => [
                $anchor->startOfDay(),
                $anchor->endOfDay(),
                $anchor->translatedFormat('d F Y'),
            ],
        };
    }

    /**
     * Baris rekap absensi (dengan agregasi status kehadiran).
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(string $mode, ?string $anchor = null): array
    {
        [$start, $end] = $this->range($mode, $anchor);

        $attendances = DriverAttendance::with('driver.user')
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->orderBy('date')
            ->orderBy('clock_in_at')
            ->get();

        $drivers = Driver::with('user')->orderBy('id')->get();

        // Baris per driver dengan rekap agregat kehadiran.
        return $drivers->map(function (Driver $driver) use ($attendances) {
            $records = $attendances->where('driver_id', $driver->id);

            $present = $records->whereNotNull('clock_in_at')->count();
            $late = $records->where('is_late', true)->count();
            $clockedOut = $records->whereNotNull('clock_out_at')->count();

            $totalMinutes = $records
                ->map(fn (DriverAttendance $a) => $a->workDurationMinutes())
                ->filter()
                ->sum();

            $latenessMinutes = (int) $records->sum('lateness_minutes');

            return [
                'driver_id' => $driver->id,
                'driver_name' => $driver->user?->name ?? '-',
                'driver_status' => $driver->status,
                'records' => $records->map(fn (DriverAttendance $a) => [
                    'id' => $a->id,
                    'date' => $a->date?->toDateString(),
                    'clock_in_at' => $a->clock_in_at?->toIso8601String(),
                    'clock_out_at' => $a->clock_out_at?->toIso8601String(),
                    'clock_in_photo_path' => $a->clock_in_photo_path,
                    'clock_out_photo_path' => $a->clock_out_photo_path,
                    'is_late' => (bool) $a->is_late,
                    'lateness_minutes' => (int) ($a->lateness_minutes ?? 0),
                    'work_duration_minutes' => $a->workDurationMinutes(),
                ])->values()->all(),
                'summary' => [
                    'present' => $present,
                    'late' => $late,
                    'clocked_out' => $clockedOut,
                    'total_minutes' => $totalMinutes,
                    'lateness_minutes' => $latenessMinutes,
                ],
            ];
        })->values()->all();
    }

    /**
     * Ringkasan agregat seluruh driver untuk periode terpilih.
     *
     * @return array<string, mixed>
     */
    public function summary(string $mode, ?string $anchor = null): array
    {
        [$start, $end] = $this->range($mode, $anchor);

        $attendances = DriverAttendance::whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get();

        $totalDrivers = Driver::count();

        return [
            'total_drivers' => $totalDrivers,
            'present' => $attendances->whereNotNull('clock_in_at')->count(),
            'late' => $attendances->where('is_late', true)->count(),
            'clocked_out' => $attendances->whereNotNull('clock_out_at')->count(),
            'total_work_minutes' => (int) $attendances->map(fn (DriverAttendance $a) => $a->workDurationMinutes())->filter()->sum(),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ];
    }

    public static function modes(): array
    {
        return [
            self::MODE_DAILY => 'Harian',
            self::MODE_WEEKLY => 'Mingguan',
            self::MODE_MONTHLY => 'Bulanan',
            self::MODE_YEARLY => 'Tahunan',
        ];
    }
}
