<?php

namespace App\Console\Commands;

use App\Services\DriverScheduleService;
use Illuminate\Console\Command;

/**
 * Aktivasi trip terjadwal pada hari H.
 *
 * Dijalankan otomatis oleh scheduler (setiap 15 menit) dan dapat dipanggil
 * manual: `php artisan trips:activate-scheduled`.
 */
class ActivateScheduledTrips extends Command
{
    protected $signature = 'trips:activate-scheduled {--refresh-drivers : Sinkronkan juga status seluruh driver}';

    protected $description = 'Aktifkan trip terjadwal yang sudah memasuki hari keberangkatan & sesuaikan status driver';

    public function handle(DriverScheduleService $service): int
    {
        $result = $service->activateDueTrips();

        $this->info("Trip diaktifkan: {$result['activated']} (driver diperbarui: {$result['drivers_updated']}).");

        if ($this->option('refresh-drivers')) {
            $refresh = $service->refreshDriverStatuses();
            $this->info("Status driver disinkronkan: {$refresh['drivers_updated']}.");
        }

        return self::SUCCESS;
    }
}
