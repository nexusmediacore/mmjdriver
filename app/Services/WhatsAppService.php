<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppService
{
    /**
     * Driver aktif: setting dari UI (database) menang atas .env.
     */
    protected function driver(): string
    {
        $saved = Setting::get('whatsapp_driver');

        return $saved ? (string) $saved : (string) config('whatsapp.driver', 'link');
    }

    /**
     * Token gateway: setting dari UI menang atas .env.
     */
    protected function token(): string
    {
        $saved = Setting::get('whatsapp_token');

        return (string) ($saved !== null && $saved !== '' ? $saved : config('whatsapp.token', ''));
    }

    /**
     * Apakah pengiriman otomatis aktif?
     *
     * Otomatis hanya berjalan bila driver gateway (fonnte/wablas) dipilih
     * DAN token sudah diisi. Bila tidak, fallback ke mode tautan (wa.me).
     */
    public function isAutoSendEnabled(): bool
    {
        $driver = $this->driver();

        return in_array($driver, ['fonnte', 'wablas'], true) && $this->token() !== '';
    }

    /**
     * Informasi konfigurasi aktif (untuk halaman Pengaturan).
     *
     * @return array{driver: string, has_token: bool, auto_send: bool}
     */
    public function configuration(): array
    {
        return [
            'driver' => $this->driver(),
            'has_token' => $this->token() !== '',
            'auto_send' => $this->isAutoSendEnabled(),
        ];
    }

    /**
     * Kirim pesan WhatsApp ke nomor tujuan.
     *
     * @return array{ok: bool, driver: string, number: string, link?: string, response?: mixed, error?: string}
     */
    public function send(string $number, string $message): array
    {
        $normalized = $this->normalizeNumber($number);
        $driver = $this->driver();

        if ($normalized === null) {
            if (config('whatsapp.silent_invalid_number', true)) {
                Log::warning('WhatsApp dilewati: nomor tidak valid.', ['raw_number' => $number]);

                return ['ok' => false, 'driver' => $driver, 'number' => (string) $number, 'error' => 'Nomor tidak valid.'];
            }

            throw new RuntimeException('Nomor WhatsApp tidak valid: '.$number);
        }

        // Gateway terkonfigurasi -> kirim otomatis
        if (in_array($driver, ['fonnte', 'wablas'], true) && $this->token() !== '') {
            $result = $this->sendViaGateway($driver, $normalized, $message, $this->token());

            // Bila gateway gagal (misal: token tidak valid), fallback ke tautan manual
            if (! $result['ok'] && config('whatsapp.fallback_to_link', true)) {
                Log::warning('WhatsApp gateway gagal; fallback ke tautan wa.me.', [
                    'driver' => $driver,
                    'error' => $result['error'] ?? 'unknown',
                ]);

                return [
                    'ok' => true,
                    'driver' => 'link',
                    'number' => $normalized,
                    'link' => $this->buildWaLink($normalized, $message),
                    'error' => $result['error'] ?: 'Gagal via gateway — tautan manual disediakan.',
                ];
            }

            return $result;
        }

        // Token belum diisi -> jangan diam-diam gagal; beri tautan manual
        if (in_array($driver, ['fonnte', 'wablas'], true)) {
            Log::warning('WhatsApp otomatis diminta tetapi token belum diisi; memakai tautan wa.me.', [
                'driver' => $driver,
            ]);

            return [
                'ok' => true,
                'driver' => 'link',
                'number' => $normalized,
                'link' => $this->buildWaLink($normalized, $message),
                'error' => 'Token gateway belum diisi — memakai tautan manual.',
            ];
        }

        return $this->sendViaGateway($driver, $normalized, $message, $this->token());
    }

    /**
     * Kirim melalui gateway (fonnte / wablas) atau mode log/link.
     */
    protected function sendViaGateway(string $driver, string $number, string $message, string $token = ''): array
    {
        // Mode log: hanya catat, tidak ada pengiriman
        if ($driver === 'log') {
            Log::channel(config('logging.default'))->info('WhatsApp (mode log — tidak terkirim)', [
                'to' => $number,
                'message' => $message,
            ]);

            return [
                'ok' => true,
                'driver' => 'log',
                'number' => $number,
                'link' => $this->buildWaLink($number, $message),
            ];
        }

        // Mode link: hasilkan tautan wa.me saja
        if ($driver === 'link') {
            return [
                'ok' => true,
                'driver' => 'link',
                'number' => $number,
                'link' => $this->buildWaLink($number, $message),
            ];
        }

        $token = $token !== '' ? $token : $this->token();

        if ($token === '') {
            Log::error('WhatsApp token belum dikonfigurasi.', ['driver' => $driver]);

            return [
                'ok' => false,
                'driver' => $driver,
                'number' => $number,
                'error' => 'WhatsApp token belum dikonfigurasi.',
            ];
        }

        try {
            $response = match ($driver) {
                'fonnte' => Http::asForm()
                    ->withHeaders(['Authorization' => $token])
                    ->timeout((int) config('whatsapp.timeout', 30))
                    ->post((string) config('whatsapp.endpoint'), [
                        'target' => $number,
                        'message' => $message,
                        'countryCode' => '62',
                    ]),

                'wablas' => Http::asForm()
                    ->withToken($token)
                    ->timeout((int) config('whatsapp.timeout', 30))
                    ->post((string) config('whatsapp.endpoint'), [
                        'phone' => $number,
                        'message' => $message,
                        'token' => $token,
                    ]),

                default => throw new RuntimeException("Driver WhatsApp tidak dikenal: {$driver}"),
            };

            $payload = $response->json() ?? $response->body();

            // Fonnte/Wablas kadang kembali HTTP 200 meski token tidak valid;
            // validasi berdasarkan field `status` pada body JSON bila tersedia.
            $gatewayOk = isset($payload['status']) ? (bool) $payload['status'] : $response->successful();

            if ($gatewayOk) {
                Log::info('WhatsApp terkirim.', ['driver' => $driver, 'to' => $number]);

                return ['ok' => true, 'driver' => $driver, 'number' => $number, 'response' => $payload];
            }

            Log::error('WhatsApp gagal dikirim.', [
                'driver' => $driver,
                'to' => $number,
                'status' => $response->status(),
                'payload' => $payload,
            ]);

            return [
                'ok' => false,
                'driver' => $driver,
                'number' => $number,
                'error' => $payload['reason'] ?? 'Gateway menolak permintaan.',
                'response' => $payload,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp exception: '.$e->getMessage(), ['to' => $number]);

            return ['ok' => false, 'driver' => $driver, 'number' => $number, 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalisasi nomor ke format internasional (6281234567890).
     * Mengembalikan null bila terlalu pendek.
     */
    public function normalizeNumber(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        // Buang semua karakter non-digit
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        // Bila diawali 62, anggap sudah internasional
        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        }

        // Buang 0 di depan (format lokal 08xx)
        $digits = ltrim($digits, '0');

        if ($digits === '') {
            return null;
        }

        $countryCode = (string) config('whatsapp.country_code', '62');
        $normalized = $countryCode.$digits;

        // Minimal 10 digit untuk nomor Indonesia (62 + 8xx...)
        return strlen($normalized) >= 10 ? $normalized : null;
    }

    /**
     * Bangun tautan click-to-chat WhatsApp.
     */
    public function buildWaLink(string $number, string $message): string
    {
        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
