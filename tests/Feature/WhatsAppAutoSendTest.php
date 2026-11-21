<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppAutoSendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_setting_driver_wins_over_env(): void
    {
        Setting::set('whatsapp_driver', 'fonnte', 'whatsapp');

        $wa = new WhatsAppService;
        $cfg = $wa->configuration();

        dump('driver dari DB : '.$cfg['driver']);
        $this->assertSame('fonnte', $cfg['driver']);
    }

    public function test_auto_send_active_when_gateway_and_token_set(): void
    {
        Setting::set('whatsapp_driver', 'fonnte', 'whatsapp');
        Setting::set('whatsapp_token', 'token-rahasia', 'whatsapp');

        $wa = new WhatsAppService;
        dump('auto_send : '.var_export($wa->isAutoSendEnabled(), true));

        $this->assertTrue($wa->isAutoSendEnabled());
    }

    public function test_auto_send_inactive_without_token(): void
    {
        Setting::set('whatsapp_driver', 'fonnte', 'whatsapp');
        Setting::forget('whatsapp_token');

        $wa = new WhatsAppService;
        $this->assertFalse($wa->isAutoSendEnabled());
    }

    public function test_auto_send_inactive_for_link_mode(): void
    {
        Setting::set('whatsapp_driver', 'link', 'whatsapp');

        $wa = new WhatsAppService;
        $this->assertFalse($wa->isAutoSendEnabled());
    }

    public function test_assignment_sends_automatically_when_configured(): void
    {
        Setting::set('whatsapp_driver', 'fonnte', 'whatsapp');
        Setting::set('whatsapp_token', 'token-rahasia', 'whatsapp');

        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'id' => ['abc']], 200),
        ]);

        $wa = new WhatsAppService;
        $result = $wa->send('081300009999', 'Uji kirim otomatis');

        dump('hasil kirim : '.json_encode(['ok' => $result['ok'], 'driver' => $result['driver']]));

        $this->assertTrue($result['ok']);
        $this->assertSame('fonnte', $result['driver']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'fonnte.com'));
    }

    public function test_settings_page_shows_whatsapp_config(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        Setting::set('whatsapp_driver', 'fonnte', 'whatsapp');
        Setting::set('whatsapp_token', 'token-rahasia', 'whatsapp');

        $response = $this->actingAs($superadmin)->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('whatsapp.driver', 'fonnte')
            ->where('whatsapp.auto_send', true)
            ->where('whatsapp.has_token', true));
    }

    public function test_superadmin_can_update_whatsapp_settings(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        $response = $this->actingAs($superadmin)->post(route('admin.settings.update'), [
            'whatsapp_driver' => 'fonnte',
            'whatsapp_token' => 'token-baru-123',
            'whatsapp_endpoint' => 'https://api.fonnte.com/send',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertSame('fonnte', Setting::get('whatsapp_driver'));
        $this->assertSame('token-baru-123', Setting::get('whatsapp_token'));

        $wa = new WhatsAppService;
        $this->assertTrue($wa->isAutoSendEnabled());
    }
}
