<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Notifications\Notification;

class TripRejected extends Notification
{
    public function __construct(
        public TripRequest $trip,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'trip_rejected',
            'trip_id' => $this->trip->id,
            'trip_number' => $this->trip->trip_number,
            'message' => "Permintaan perjalanan {$this->trip->trip_number} ditolak. Alasan: {$this->reason}",
            'reason' => $this->reason,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
