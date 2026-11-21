<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_falls_back_to_link_when_gateway_returns_invalid_token(): void
    {
        Setting::set('whatsapp_driver', 'fonnte', 'whatsapp');
        Setting::set('whatsapp_token', 'token-salah', 'whatsapp');

        Http::fake([
            'api.fonnte.com/*' => Http::response([
                'reason' => 'invalid token',
                'status' => false,
            ], 200),
        ]);

        $wa = new WhatsAppService;
        $result = $wa->send('081300009999', 'Pesan uji');

        $this->assertTrue($result['ok']);
        $this->assertSame('link', $result['driver']);
        $this->assertStringContainsString('wa.me', $result['link']);
        $this->assertStringContainsString('token', $result['error']);
    }

    public function test_still_sends_via_gateway_when_valid(): void
    {
        Setting::set('whatsapp_driver', 'fonnte', 'whatsapp');
        Setting::set('whatsapp_token', 'token-valid', 'whatsapp');

        Http::fake([
            'api.fonnte.com/*' => Http::response([
                'status' => true,
                'id' => ['abc123'],
            ], 200),
        ]);

        $wa = new WhatsAppService;
        $result = $wa->send('081300009999', 'Pesan uji');

        $this->assertTrue($result['ok']);
        $this->assertSame('fonnte', $result['driver']);
    }
}
