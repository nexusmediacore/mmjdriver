<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverAssigned extends Notification
{
    use Queueable;

    public function __construct(
        public TripRequest $trip
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Simpan ke database (bell notification di aplikasi).
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'driver_assigned',
            'trip_id' => $this->trip->id,
            'trip_number' => $this->trip->trip_number,
            'message' => "Anda telah ditugaskan untuk perjalanan {$this->trip->trip_number} ke {$this->trip->destination}.",
            'destination' => $this->trip->destination,
            'departure_time' => $this->trip->departure_time?->toDateTimeString(),
            'bod_link' => $this->trip->bod_link,
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Email fallback bila WhatsApp gagal (token invalid, dll).
     */
    public function toMail($notifiable): MailMessage
    {
        $trip = $this->trip;

        return (new MailMessage)
            ->subject('🟢 Penugasan Trip Baru — '.$trip->trip_number)
            ->greeting('Halo '.($notifiable->name ?? 'Driver').',')
            ->line('Anda ditugaskan untuk perjalanan berikut:')
            ->line('No. Trip: '.$trip->trip_number)
            ->line('Tujuan: '.($trip->destination ?: '-'))
            ->line('Berangkat: '.($trip->departure_time?->format('d M Y H:i') ?? '-'))
            ->line('Keperluan: '.mb_substr((string) $trip->purpose, 0, 200))
            ->action('Buka Aplikasi MMJ Driver', url('/login'))
            ->line('Jika WhatsApp aktif, pesan otomatis dikirim ke nomor terdaftar.')
            ->salutation('Terima kasih — Tim MMJ Driver');
    }

    /**
     * Isi pesan WhatsApp yang dikirim ke driver.
     */
    public function toWhatsApp($notifiable): string
    {
        $trip = $this->trip;

        $lapar = "🟢 *MMJ DRIVER — PENUGASAN BARU*\n\n";
        $lapar .= "Anda ditugaskan untuk perjalanan berikut:\n\n";
        $lapar .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $lapar .= "No. Trip    : *{$trip->trip_number}*\n";
        $lapar .= 'Tujuan      : *'.($trip->destination ?: '-')."*\n";
        $lapar .= "Prioritas   : *{$trip->priority}*\n";
        $lapar .= 'Berangkat   : '.($trip->departure_time?->format('d M Y H:i') ?? '-')."\n";
        $lapar .= 'Estimasi    : '.($trip->estimated_return_time?->format('d M Y H:i') ?? '-')."\n";
        $lapar .= "━━━━━━━━━━━━━━━━━━━━━\n";

        if (filled($trip->request_type ?? null) && $trip->request_type === TripRequest::TYPE_GUEST_PICKUP) {
            $lapar .= "Jenis        : *Jemput Tamu*\n";
            $lapar .= 'Lokasi Jemput: '.($trip->guest_pickup_location ?: '-')."\n";
            $lapar .= 'Instansi    : '.($trip->guest_institution ?: '-')."\n";
            $lapar .= "━━━━━━━━━━━━━━━━━━━━━\n";
        }

        if (filled($trip->request_type ?? null) && $trip->request_type === TripRequest::TYPE_CARGO_PICKUP) {
            $lapar .= "Jenis        : *Muat Barang*\n";
            $lapar .= 'Lokasi Muat : '.($trip->cargo_pickup_location ?: '-')."\n";
            $lapar .= 'Barang      : '.($trip->cargo_description ?: '-')."\n";
            $lapar .= "━━━━━━━━━━━━━━━━━━━━━\n";
        }

        $lapar .= 'Keperluan   : '.mb_substr((string) $trip->purpose, 0, 140)."\n";

        // Trip BOD: sertakan tautan referensi (Google Maps / agenda / dokumen).
        if ($trip->bod_link) {
            $lapar .= 'Tautan      : '.$trip->bod_link."\n";
        }

        $lapar .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
        $lapar .= "Silakan buka menu *MMJ Driver* untuk menerima atau menolak penugasan.\n\n";
        $lapar .= '🔗 Buka sistem: '.route('driver.my-tasks')."\n";
        $lapar .= 'Terima kasih.';

        return $lapar;
    }
}
