<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Notifications\Notification;

class TripCreatedNotification extends Notification
{
    public function __construct(
        public TripRequest $trip
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'trip_created',
            'trip_id' => $this->trip->id,
            'trip_number' => $this->trip->trip_number,
            'message' => "Permintaan perjalanan baru {$this->trip->trip_number} telah dibuat.",
            'destination' => $this->trip->destination,
            'priority' => $this->trip->priority,
            'url' => '/trips/'.$this->trip->id,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
