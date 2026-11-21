<?php

namespace App\Channels;

use App\Services\WhatsAppService;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(
        protected WhatsAppService $whatsApp
    ) {}

    /**
     * Kirim notifikasi lewat WhatsApp.
     * Notifiable harus punya property `phone` (atau method `routeNotificationForWhatsApp`).
     */
    public function send($notifiable, Notification $notification): array
    {
        $number = method_exists($notifiable, 'routeNotificationForWhatsApp')
            ? $notifiable->routeNotificationForWhatsApp()
            : ($notifiable->phone ?? null);

        if (! $number) {
            return ['ok' => false, 'error' => 'Notifiable tidak memiliki nomor telepon.'];
        }

        $message = method_exists($notification, 'toWhatsApp')
            ? $notification->toWhatsApp($notifiable)
            : $notification->toArray($notifiable);

        // Izin pesan berupa string; bila array, ubah jadi teks
        if (is_array($message)) {
            $message = $message['message'] ?? json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        return $this->whatsApp->send($number, (string) $message);
    }
}
