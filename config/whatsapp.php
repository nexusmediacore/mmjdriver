<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver Pengiriman WhatsApp
    |--------------------------------------------------------------------------
    |
    | Pilihan driver:
    | - "fonnte"  : Gateway Fonnte (https://fonnte.com)  — direkomendasikan
    | - "wablas"  : Gateway Wablas   (https://wablas.com)
    | - "log"     : Hanya menulis ke log (untuk pengembangan, tidak terkirim)
    | - "link"    : Hasilkan tautan wa.me untuk dikirim manual
    |
    */

    'driver' => env('WHATSAPP_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Kredensial Gateway
    |--------------------------------------------------------------------------
    */

    'token' => env('WHATSAPP_TOKEN', ''),

    'endpoint' => env('WHATSAPP_ENDPOINT', 'https://api.fonnte.com/send'),

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Pesan
    |--------------------------------------------------------------------------
    */

    // Kode negara default saat nomor tidak memakai format internasional
    'country_code' => env('WHATSAPP_COUNTRY_CODE', '62'),

    // Batalkan pengiriman bila nomor kosong/tidak valid, bukan melempar error
    'silent_invalid_number' => true,

    // Timeout request ke gateway (detik)
    'timeout' => env('WHATSAPP_TIMEOUT', 30),

    // Bila gateway gagal (misal token tidak valid), otomatis fallback ke tautan wa.me
    'fallback_to_link' => env('WHATSAPP_FALLBACK_TO_LINK', true),

];
