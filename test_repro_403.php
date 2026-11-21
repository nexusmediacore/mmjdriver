<?php

require __DIR__.'/vendor/autoload.php';

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Encryption\Encrypter;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$key = config('app.key');
$cipher = config('app.cipher', 'AES-256-CBC');
$rawKey = base64_decode(substr($key, 7));
$encrypter = new Encrypter($rawKey, $cipher);

$jar = new CookieJar;
$client = new Client([
    'base_uri' => 'http://127.0.0.1:8018',
    'cookies' => $jar,
    'http_errors' => false,
]);

// 1. GET login page untuk memperoleh cookie XSRF-TOKEN & laravel_session
$loginPage = $client->get('/login');
$xsrf = null;
foreach ($jar->toArray() as $c) {
    if ($c['Name'] === 'XSRF-TOKEN') {
        $xsrf = urldecode($c['Value']);
    }
}

if (! $xsrf) {
    echo "Server tidak berjalan atau XSRF-TOKEN tidak ditemukan\n";
    exit(1);
}

$token = $encrypter->decrypt($xsrf, false);
echo 'Token diperoleh: '.substr($token, 0, 16)."...\n";

$driver = User::whereHas('role', fn ($q) => $q->where('name', 'DRIVER'))->first();

// 2. Login sebagai driver
$resp = $client->post('/login', [
    'form_params' => ['_token' => $token, 'email' => $driver->email, 'password' => 'Pass@123'],
]);
echo 'Login: '.$resp->getStatusCode().' -> '.($resp->getHeaderLine('Location') ?: '(tanpa redirect)')."\n";

// 3. Akses dashboard
foreach (['/dashboard', '/driver/my-tasks', '/driver/trip-history', '/trips'] as $url) {
    $r = $client->get($url);
    $code = $r->getStatusCode();
    $body = (string) $r->getBody();
    $note = '';
    if ($code === 403) {
        $note = '  <<< 403 UNAUTHORIZED';
        if (preg_match('/unauthorized/i', $body)) {
            $note .= ' (pesan unauthorized)';
        }
    }
    echo str_pad($url, 22)." => $code$note\n";
}
