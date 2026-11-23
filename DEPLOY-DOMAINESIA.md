# Panduan Deploy ke Domainesia (Shared Hosting cPanel)

Aplikasi ini adalah Laravel + Inertia. Di shared hosting (cPanel Domainesia) tidak ada
SSH root, Redis, queue worker daemon, dan `php artisan serve` tidak dipakai.
Ikuti langkah berikut.

---

## 1. Persiapan di Lokal (sebelum upload)

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build          # hasil masuk ke public/build
```

Pastikan `.env` sudah benar (lihat bagian bawah). **JANGAN upload** file ini:
- `.git/`, `node_modules/`, `tests/`, `.env.example`, `.phpunit.result.cache`
- Folder `mobile/` (Flutter/Android) tidak perlu di-upload ke server.

## 2. Struktur Folder di Hosting

Cara paling mudah di Domainesia: upload seluruh project ke `~/laravel-app`,
lalu arahkan **document root** domain ke `~/laravel-app/public`.

Kalau domain utama (public_html tidak bisa diubah), pakai file `.htaccess`
yang sudah disediakan di root project ini untuk me-redirect ke folder `public/`.

Langkah via cPanel:
1. **File Manager** → buat folder `~/laravel-app`
2. Upload ZIP project, extract di situ
3. Buat folder `~/laravel-app/storage` dan sub-foldernya writable (755):
   `storage/framework/{cache,sessions,views}`, `storage/logs`, `bootstrap/cache`
4. **Domains** → pilih domain → ubah **Document Root** ke `/laravel-app/public`

## 3. Database MySQL

Di cPanel Domainesia:
1. **MySQL Databases** → buat database `maritama_mmjdriver`
2. Buat user `maritama_maritama_mmjdriver` dengan password
3. Add user to database, grant **ALL PRIVILEGES**

Isi `.env`:
```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=maritama_mmjdriver
DB_USERNAME=maritama_maritama_mmjdriver
DB_PASSWORD=marita999jayaterus12345
```

> **Catatan**: di shared hosting host-nya **harus `localhost`**, bukan IP eksternal.

## 4. Import / Jalankan Migration

Pakai cPanel **Terminal** atau **SSH** (jika aktif):

```bash
cd ~/laravel-app
php artisan migrate --force
php artisan storage:link        # kalau butuh symlink untuk file upload
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Kalau belum pernah generate key di server:
```bash
php artisan key:generate --force
```

Kalau SSH tidak tersedia, export/import dump SQL via **phpMyAdmin**.

## 5. `.env` yang Direkomendasikan

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domainanda.com

DB_HOST=localhost
DB_DATABASE=maritama_mmjdriver
DB_USERNAME=maritama_maritama_mmjdriver
DB_PASSWORD=...

SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_STORE=database
LOG_STACK=daily
LOG_LEVEL=error
```

Jangan pakai `redis`, `memcached`, atau `QUEUE_CONNECTION=database` di shared
hosting kecuali yakin ada worker yang jalan.

## 6. PHP Version

Di cPanel → **Select PHP Version** → pilih **PHP 8.2** atau lebih tinggi.
Ekstensi yang dibutuhkan: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `dom`, `xml`,
`gd` (untuk dompdf), `zip`, `fileinfo`, `bcmath`.

## 7. Permission

```bash
chmod -R 775 storage bootstrap/cache
```

Kalau muncul error `Permission denied` pada storage/logs, set `644` untuk file log
dan `755` untuk foldernya.

## 8. Troubleshooting Umum

| Masalah | Solusi |
|---|---|
| Blank page / 500 | Cek `APP_DEBUG=true` sementara, lihat error; cek `storage/logs/laravel.log` |
| "The stream or file could not be opened" | chmod 775 storage dan bootstrap/cache |
| Asset CSS/JS 404 | Pastikan `npm run build` sudah dijalankan & folder `public/build` ikut di-upload |
| Session tidak persisten | Pastikan tabel `sessions` ada; `php artisan migrate` |
| Mail tidak terkirim | Domainesia butuh SMTP eksternal; set `MAIL_MAILER=smtp` dengan host mail |
| WhatsApp gagal kirim | `WHATSAPP_TOKEN` harus diisi kalau pakai driver fonnte/wablas |

## 9. Cron (queue/scheduler)

Jika butuh scheduler Laravel, tambahkan cron di cPanel (tiap menit):

```
* * * * * cd /home/username/laravel-app && php artisan schedule:run >> /dev/null 2>&1
```

`QUEUE_CONNECTION=sync` berarti job dijalankan langsung, tanpa worker.

## 10. Checklist Sebelum Live

- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` sesuai domain asli
- [ ] `.env` TIDAK bisa diakses dari browser (uji: `https://domain/.env` harus 403)
- [ ] HTTPS aktif (SSL dari cPanel → AutoSSL/Let's Encrypt)
- [ ] Migrasi berhasil & tabel `sessions` ada
- [ ] `public/build` terupload
- [ ] Login admin bekerja, dan upload file (jika ada) masuk ke `storage/app/public`
