<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencegah browser menyimpan (cache) halaman HTML & request Inertia.
 *
 * Tanpa ini, browser bisa menyajikan halaman dari cache saat pengguna
 * menekan tombol Back atau berpindah tab, sehingga data props Inertia
 * menjadi basi — misalnya dropdown "Pengguna" di Tambah Driver menampilkan
 * "Belum ada akun Driver" padahal akun sudah dibuat.
 */
class NoCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Selalu timpa agar browser tidak menyimpan salinan halaman.
        // Hanya berlaku untuk respons yang ditangani Laravel (grup web);
        // berkas statis di public/ dilayani langsung server, tidak terpengaruh.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
