<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LoadingApprovalDecision extends Notification
{
    use Queueable;

    public function __construct(
        public TripRequest $trip,
        public bool $approved,
        public ?string $note = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => $this->approved ? 'loading_approval_approved' : 'loading_approval_rejected',
            'trip_id' => $this->trip->id,
            'trip_number' => $this->trip->trip_number,
            'message' => $this->approved
                ? "Driver bantu muat untuk trip {$this->trip->trip_number} disetujui."
                : "Driver bantu muat untuk trip {$this->trip->trip_number} ditolak.".($this->note ? " Catatan: {$this->note}" : ''),
            'note' => $this->note,
            'url' => '/trips/'.$this->trip->id,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
