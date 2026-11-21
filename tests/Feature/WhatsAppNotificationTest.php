<?php

namespace Tests\Feature;

use App\Channels\WhatsAppChannel;
use App\Models\Driver;
use App\Models\Setting;
use App\Models\TripRequest;
use App\Models\User;
use App\Notifications\DriverAssigned;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_notification_sent_to_whatsapp_channel(): void
    {
        $driver = Driver::with('user')->firstOrFail();
        $trip = TripRequest::firstOrFail();

        $channel = app(WhatsAppChannel::class);
        $result = $channel->send($driver->user, new DriverAssigned($trip));

        $this->assertTrue($result['ok'], json_encode($result));
        $this->assertSame('6281300000001', (new WhatsAppService)->normalizeNumber('081300000001'));
    }

    public function test_whatsapp_message_contains_trip_details(): void
    {
        $trip = TripRequest::firstOrFail();
        $notification = new DriverAssigned($trip);
        $message = $notification->toWhatsApp(User::first());

        $this->assertStringContainsString('PENUGASAN BARU', $message);
        $this->assertStringContainsString($trip->trip_number, $message);
        $this->assertStringContainsString($trip->destination, $message);
    }

    public function test_send_via_fonnte_gateway(): void
    {
        config([
            'whatsapp.driver' => 'fonnte',
            'whatsapp.token' => 'test-token',
            'whatsapp.endpoint' => 'https://api.fonnte.com/send',
        ]);

        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'id' => ['abc123']], 200),
        ]);

        $service = app(WhatsAppService::class);
        $result = $service->send('081300000001', 'Pesan uji');

        $this->assertTrue($result['ok']);
        $this->assertSame('fonnte', $result['driver']);
        $this->assertSame('6281300000001', $result['number']);
    }

    public function test_invalid_number_is_skipped_not_crash(): void
    {
        $service = app(WhatsAppService::class);
        $result = $service->send('nomor-salah', 'Pesan uji');

        $this->assertFalse($result['ok']);
        $this->assertSame('Nomor tidak valid.', $result['error']);
    }

    public function test_missing_token_falls_back_to_link(): void
    {
        // Gateway dipilih tetapi token belum diisi -> jangan gagal keras,
        // berikan tautan wa.me agar tetap bisa dikirim manual.
        Setting::set('whatsapp_driver', 'fonnte', 'whatsapp');
        Setting::forget('whatsapp_token');

        $service = app(WhatsAppService::class);
        $result = $service->send('081300000001', 'Pesan uji');

        $this->assertTrue($result['ok']);
        $this->assertSame('link', $result['driver']);
        $this->assertStringStartsWith('https://wa.me/', $result['link']);
        $this->assertFalse($service->isAutoSendEnabled());

        Setting::forget('whatsapp_driver');
    }

    public function test_whatsapp_channel_is_registered(): void
    {
        $driver = Driver::with('user')->firstOrFail();
        $trip = TripRequest::firstOrFail();

        // Proses notifikasi melalui manager Laravel; bila channel belum
        // terdaftar akan melempar "Driver [whatsapp] not supported".
        try {
            $driver->user->notify(new DriverAssigned($trip));
            $registered = true;
        } catch (\Throwable $e) {
            dump($e->getMessage());
            $registered = false;
        }

        $this->assertTrue($registered, 'Channel whatsapp harus terdaftar di AppServiceProvider');

        // Pastikan channel terpanggil untuk notifikasi ini
        $driver->user->refresh();
        $this->assertGreaterThan(0, $driver->user->notifications()->count());
    }
}
