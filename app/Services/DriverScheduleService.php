<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\TripRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Aktivasi trip terjadwal pada hari H (feature: status driver tetap STANDBY
 * untuk request yang belum masuk tanggalnya).
 *
 * Saat dispatcher menugaskan driver ke trip yang tanggalnya masih di masa
 * depan, status driver tidak diubah menjadi ASSIGNED/ACCEPTED agar driver
 * tetap terlihat standby dan bisa menerima tugas lain di hari berjalan.
 * Saat tanggal perjalanan tiba, mekanisme ini memajukan status tugas:
 *
 *   DRIVER_ASSIGNED     -> READY_TO_DEPART
 *   DRIVER_ACCEPTED     -> READY_TO_DEPART
 *
 * Status DRIVER tetap STANDBY selama perjalanan belum dimulai — driver masih
 * bisa dipilih untuk trip lain. Status baru menjadi ON_TRIP tepat saat
 * driver memulai perjalanan (Start Trip).
 */
class DriverScheduleService
{
    /**
     * Aktifkan semua trip terjadwal yang sudah memasuki hari H.
     *
     * @return array{activated: int, drivers_updated: int}
     */
    public function activateDueTrips(?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::now())->copy()->startOfDay();

        $trips = TripRequest::with(['assignments', 'driver'])
            ->whereIn('status', [TripRequest::STATUS_DRIVER_ASSIGNED, TripRequest::STATUS_DRIVER_ACCEPTED])
            // Hanya trip yang jadwalnya sudah tiba dan masih dalam jangka
            // wajar (belum lebih dari 2 hari lewat) — trip lama yang tidak
            // pernah diselesaikan tidak diangkat otomatis.
            ->whereDate('departure_time', '<=', $today->toDateString())
            ->whereDate('departure_time', '>=', $today->copy()->subDays(2)->toDateString())
            ->get();

        $activated = 0;
        $driversUpdated = 0;

        foreach ($trips as $trip) {
            DB::transaction(function () use ($trip, &$activated, &$driversUpdated) {
                $trip->update([
                    'status' => TripRequest::STATUS_READY_TO_DEPART,
                    'updated_by' => null,
                ]);

                $trip->statusLogs()->create([
                    'from_status' => TripRequest::STATUS_DRIVER_ACCEPTED,
                    'to_status' => TripRequest::STATUS_READY_TO_DEPART,
                    'changed_by' => null,
                    'notes' => 'Aktivasi otomatis pada hari keberangkatan (scheduled trip).',
                ]);

                $activated++;

                $driversUpdated += $this->syncDriversForTrip($trip);
            });
        }

        if ($activated > 0) {
            Log::info('Scheduled trips activated.', ['count' => $activated]);
        }

        return ['activated' => $activated, 'drivers_updated' => $driversUpdated];
    }

    /**
     * Pastikan status driver sesuai dengan trip aktif hari ini.
     * Driver yang tidak punya trip aktif hari ini dikembalikan ke STANDBY.
     *
     * @return array{drivers_updated: int}
     */
    public function refreshDriverStatuses(?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::now())->copy()->startOfDay();
        $updated = 0;

        foreach (Driver::all() as $driver) {
            if ($driver->isInactive()) {
                continue;
            }

            $activeTrip = TripRequest::where(function ($query) use ($driver) {
                $query->where('assigned_driver_id', $driver->id)
                    ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $driver->id));
            })
                ->whereIn('status', [
                    TripRequest::STATUS_ON_TRIP,
                    TripRequest::STATUS_COMPLETION_SUBMITTED,
                    TripRequest::STATUS_DRIVER_ACCEPTED,
                    TripRequest::STATUS_READY_TO_DEPART,
                ])
                ->whereDate('departure_time', '<=', $today->toDateString())
                ->orderBy('departure_time')
                ->first();

            if (! $activeTrip) {
                if (in_array($driver->status, [Driver::STATUS_ASSIGNED, Driver::STATUS_ACCEPTED], true)) {
                    $this->changeStatus($driver, Driver::STATUS_STANDBY);
                    $updated++;
                }

                continue;
            }

            // Trip yang belum dimulai (READY_TO_DEPART / DRIVER_ACCEPTED)
            // tidak mengubah status driver — tetap STANDBY sampai Start Trip.
            if (! in_array($activeTrip->status, [TripRequest::STATUS_ON_TRIP, TripRequest::STATUS_COMPLETION_SUBMITTED], true)) {
                // Pastikan status tidak menempel di ASSIGNED/ACCEPTED lama.
                if (in_array($driver->status, [Driver::STATUS_ASSIGNED, Driver::STATUS_ACCEPTED], true)) {
                    $this->changeStatus($driver, Driver::STATUS_STANDBY);
                    $updated++;
                }

                continue;
            }

            $target = Driver::STATUS_ON_TRIP;

            if ($driver->status !== $target) {
                $this->changeStatus($driver, $target);
                $updated++;
            }
        }

        return ['drivers_updated' => $updated];
    }

    /**
     * @return int jumlah driver yang diperbarui
     */
    protected function syncDriversForTrip(TripRequest $trip): int
    {
        $count = 0;
        $driverIds = $trip->assignments->pluck('driver_id')->push($trip->assigned_driver_id)
            ->filter()->unique()->values();

        foreach (Driver::whereIn('id', $driverIds)->get() as $driver) {
            if ($driver->isInactive()) {
                continue;
            }

            // Trip yang belum dimulai tidak mengubah status driver — tetap
            // STANDBY sampai Start Trip.
            $hasStartedTrip = TripRequest::where(function ($query) use ($driver) {
                $query->where('assigned_driver_id', $driver->id)
                    ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $driver->id));
            })
                ->whereIn('status', [TripRequest::STATUS_ON_TRIP, TripRequest::STATUS_COMPLETION_SUBMITTED])
                ->exists();

            if (! $hasStartedTrip) {
                if (in_array($driver->status, [Driver::STATUS_ASSIGNED, Driver::STATUS_ACCEPTED], true)) {
                    $this->changeStatus($driver, Driver::STATUS_STANDBY);
                    $count++;
                }

                continue;
            }

            $target = Driver::STATUS_ON_TRIP;

            if ($driver->status !== $target) {
                $this->changeStatus($driver, $target);
                $count++;
            }
        }

        return $count;
    }

    protected function changeStatus(Driver $driver, string $status): void
    {
        $from = $driver->status;

        $driver->update(['status' => $status, 'last_status_change_at' => now()]);

        $driver->statusLogs()->create([
            'from_status' => $from,
            'to_status' => $status,
            'notes' => 'Pembaruan otomatis jadwal (scheduled trip).',
            'ip_address' => request()->ip(),
            'user_agent' => 'scheduler',
        ]);
    }
}
