<?php

use App\Models\TripRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Konversi seluruh trip_number existing ke format TRIP-<tahun>-<urutan>.
     *
     * Tahun diambil dari created_at masing-masing trip, dan urutan direset
     * per tahun (trip pertama di suatu tahun menjadi 0001, dst.) berdasarkan
     * urutan created_at lalu trip_number semula.
     *
     * Trip yang sudah memakai format bertahun dipertahankan nomornya — hanya
     * trip dengan format lama TRIP-<angka> yang dirapikan. Pencocokan format
     * dilakukan di PHP agar berjalan di MySQL maupun SQLite.
     */
    public function up(): void
    {
        $all = TripRequest::withTrashed()
            ->orderBy('created_at')
            ->orderBy('trip_number')
            ->get(['id', 'trip_number', 'created_at']);

        // Offsets per tahun dari trip yang SUDAH memakai format bertahun,
        // supaya nomor legacy yang dikonversi tidak bentrok dengan mereka.
        $yearOffsets = [];

        // Trip berformat lama (TRIP-<angka>) yang harus dikonversi
        $legacy = [];

        foreach ($all as $trip) {
            $number = (string) $trip->trip_number;

            if (preg_match('/^TRIP-(\d{4})-(\d+)$/', $number, $m)) {
                // Sudah bertahun: pertahankan, catat offset tertinggi tahun tsb.
                $year = $this->yearFor($trip);
                $seq = (int) $m[2];

                // Konsistensi: bila blok tahun di nomor beda dengan tahun created_at,
                // tetap gunakan blok tahun pada nomor (nomor tidak diubah).
                $yearOffsets[$m[1]] = max($yearOffsets[$m[1]] ?? 0, $seq);
            } elseif (preg_match('/^TRIP-(\d+)$/', $number, $m)) {
                $legacy[] = $trip;
            }
        }

        foreach ($legacy as $trip) {
            $year = $this->yearFor($trip);
            $nextSeq = ($yearOffsets[$year] ?? 0) + 1;
            $candidate = 'TRIP-'.$year.'-'.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);

            // Jaga-jaga bila nomor kandidat ternyata sudah dipakai record lain
            while (TripRequest::withTrashed()->where('trip_number', $candidate)->where('id', '!=', $trip->id)->exists()) {
                $nextSeq++;
                $candidate = 'TRIP-'.$year.'-'.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
            }

            $yearOffsets[$year] = $nextSeq;

            TripRequest::withTrashed()->where('id', $trip->id)->update(['trip_number' => $candidate]);
        }
    }

    /**
     * Kembalikan nomor seperti semula: hilangkan blok tahun kembali ke TRIP-<angka>.
     */
    public function down(): void
    {
        $all = TripRequest::withTrashed()->get(['id', 'trip_number']);

        foreach ($all as $trip) {
            if (preg_match('/^TRIP-\d{4}-(\d+)$/', (string) $trip->trip_number, $m)) {
                TripRequest::withTrashed()->where('id', $trip->id)->update([
                    'trip_number' => 'TRIP-'.str_pad($m[1], 5, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }

    /**
     * Tahun yang dipakai untuk nomor baru: tahun dari created_at,
     * fallback ke tahun berjalan bila created_at kosong.
     */
    protected function yearFor(TripRequest $trip): string
    {
        return (optional($trip->created_at) instanceof Carbon ? $trip->created_at : Carbon::now())->format('Y');
    }
};
