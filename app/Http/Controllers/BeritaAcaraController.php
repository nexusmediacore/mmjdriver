<?php

namespace App\Http\Controllers;

use App\Models\TripRequest;
use App\Services\BeritaAcaraService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\RedirectResponse;

class BeritaAcaraController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function generate(TripRequest $trip): RedirectResponse
    {
        $this->authorize('view', $trip);

        if ($trip->status !== TripRequest::STATUS_COMPLETED) {
            return back()->with('error', 'Berita Acara hanya dapat dibuat untuk perjalanan yang sudah diselesaikan.');
        }

        // Idempoten: gunakan service yang sudah unik per trip.
        app(BeritaAcaraService::class)->recordTripCompletion($trip);

        return back()->with('success', 'Berita Acara telah dibuat.');
    }

    public function download(TripRequest $trip)
    {
        $this->authorize('view', $trip);

        $ba = $trip->beritaAcara;

        if (! $ba) {
            return back()->with('error', 'Berita Acara tidak ditemukan.');
        }

        $pdf = PDF::loadView('pdf.berita-acara', [
            'trip' => $trip,
            'ba' => $ba,
        ]);

        $filename = 'berita-acara-'.$ba->ba_number.'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * @deprecated Dipindah ke BeritaAcaraService::generateBaNumber().
     */
    protected function generateBaNumber(): string
    {
        return app(BeritaAcaraService::class)->generateBaNumber();
    }
}
