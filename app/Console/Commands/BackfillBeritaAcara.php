<?php

namespace App\Console\Commands;

use App\Models\TripRequest;
use App\Services\BeritaAcaraService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('berita-acara:backfill')]
#[Description('Buat Berita Acara untuk seluruh trip COMPLETED yang belum memiliki record Berita Acara')]
class BackfillBeritaAcara extends Command
{
    public function handle(): int
    {
        $service = app(BeritaAcaraService::class);

        $trips = TripRequest::where('status', TripRequest::STATUS_COMPLETED)
            ->whereDoesntHave('beritaAcara')
            ->get();

        $count = 0;

        foreach ($trips as $trip) {
            $service->recordTripCompletion($trip->fresh());
            $count++;
        }

        $this->info("Berita Acara terbuat: {$count} dari {$trips->count()} trip COMPLETED tanpa record.");

        return self::SUCCESS;
    }
}
