<?php

namespace App\Services;

use App\Models\BeritaAcara;
use App\Models\TripRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Pencatatan otomatis seluruh trip berstatus COMPLETED ke Berita Acara.
 *
 * Dipanggil dari submitCompletion() setiap kali trip selesai. Unique
 * constraint (source_type, source_id) mencegah duplikasi bila fungsi
 * dipanggil berulang.
 */
class BeritaAcaraService
{
    public function recordTripCompletion(TripRequest $trip): BeritaAcara
    {
        // Idempoten: bila sudah ada, kembalikan record lama.
        $existing = BeritaAcara::where('source_type', $trip->getMorphClass())
            ->where('source_id', $trip->getKey())
            ->first();

        if ($existing) {
            return $existing;
        }

        return BeritaAcara::create([
            'source_type' => $trip->getMorphClass(),
            'source_id' => $trip->getKey(),
            'ba_number' => $this->generateBaNumber(),
            'trip_request_id' => $trip->getKey(),
            'driver_id' => $trip->assigned_driver_id,
            'vehicle_id' => $trip->assigned_vehicle_id,
            'requestor_name' => $trip->requestor?->name ?? '-',
            'department_name' => $trip->department?->name ?? '-',
            'destination' => $trip->destination ?? '-',
            'purpose' => $trip->purpose ?? '-',
            'departure_time' => $trip->started_at,
            'return_time' => $trip->completed_at,
            'notes' => $trip->completion_notes,
            'signed_by' => Auth::id(),
            'signed_position' => Auth::user()?->role?->name,
            'signed_at' => now(),
        ]);
    }

    public function generateBaNumber(): string
    {
        $year = Carbon::now()->year;
        $seq = (int) (BeritaAcara::whereYear('created_at', $year)->max('id') ?? 0) + 1;

        return 'BA-'.$year.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
