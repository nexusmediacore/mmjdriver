<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke Admin / SuperAdmin ketika driver atau pemohon mengajukan
 * penyelesaian perjalanan (status COMPLETION_SUBMITTED).
 */
class TripCompletionRequested extends Notification
{
    public function __construct(
        public TripRequest $trip,
        public ?string $submittedByName = null,
        public ?string $notes = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'trip_completion_requested',
            'trip_id' => $this->trip->id,
            'trip_number' => $this->trip->trip_number,
            'message' => "Pengajuan selesai perjalanan {$this->trip->trip_number} menunggu persetujuan Anda.",
            'destination' => $this->trip->destination,
            'submitted_by' => $this->submittedByName,
            'url' => '/trips/'.$this->trip->id,
            'notes' => $this->notes,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
