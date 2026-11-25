<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LoadingApprovalRequested extends Notification
{
    use Queueable;

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
            'type' => 'loading_approval_requested',
            'trip_id' => $this->trip->id,
            'trip_number' => $this->trip->trip_number,
            'message' => "Trip {$this->trip->trip_number} memilih driver bantu muat dan menunggu persetujuan Anda.",
            'destination' => $this->trip->destination,
            'url' => '/trips/'.$this->trip->id,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
