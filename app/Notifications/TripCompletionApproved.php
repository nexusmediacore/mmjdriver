<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke Driver yang ditugaskan dan Employee pemohon setelah
 * penyelesaian perjalanan disetujui Admin / SuperAdmin.
 */
class TripCompletionApproved extends Notification
{
    public function __construct(
        public TripRequest $trip,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'trip_completion_approved',
            'trip_id' => $this->trip->id,
            'trip_number' => $this->trip->trip_number,
            'message' => "Penyelesaian perjalanan {$this->trip->trip_number} telah disetujui. Perjalanan resmi selesai.",
            'destination' => $this->trip->destination,
            'completed_at' => $this->trip->completed_at ? $this->trip->completed_at->toDateTimeString() : null,
            'url' => '/trips/'.$this->trip->id,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
