<?php

namespace App\Http\Controllers;

use App\Models\BeritaAcara;
use App\Models\TripRequest;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

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

        if ($trip->beritaAcara) {
            return back()->with('error', 'Berita Acara untuk perjalanan ini sudah ada.');
        }

        $ba = BeritaAcara::create([
            'ba_number' => $this->generateBaNumber(),
            'trip_request_id' => $trip->id,
            'driver_id' => $trip->assigned_driver_id,
            'vehicle_id' => $trip->assigned_vehicle_id,
            'requestor_name' => $trip->requestor->name,
            'department_name' => $trip->department->name,
            'destination' => $trip->destination,
            'purpose' => $trip->purpose,
            'departure_time' => $trip->started_at,
            'return_time' => $trip->completed_at,
            'notes' => $trip->completion_notes,
            'signed_by' => Auth::user()->id,
            'signed_position' => Auth::user()->role->name,
            'signed_at' => now(),
        ]);

        return back()->with('success', 'Berita Acara {$ba->ba_number} telah dibuat.');
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

    protected function generateBaNumber(): string
    {
        $year = now()->year;
        $count = BeritaAcara::whereYear('created_at', $year)->max('id');
        $seq = $count + 1;

        return 'BA-'.$year.'-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
