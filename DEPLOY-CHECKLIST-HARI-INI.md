# Checklist Deploy — Fix Jam Berangkat, Tombol Edit Trip, Status Driver

Tanggal perubahan: 23 November 2026
Target server: Domainesia — `mmjdriver.site`, document root `/mmjdriver.site/deploy/public`

---

## A. FILE YANG HARUS DI-UPLOAD (11 file + 1 folder build)

Semua path relatif terhadap root project lokal. Di server, tempatkan dengan
struktur folder yang sama di dalam `/mmjdriver.site/deploy/`.

### Backend (PHP) — 9 file

| #   | File lokal                                                       | Tujuan di server                                                        |
| --- | ---------------------------------------------------------------- | ----------------------------------------------------------------------- |
| 1   | `app/Http/Controllers/TripController.php`                        | `deploy/app/Http/Controllers/TripController.php`                        |
| 2   | `app/Http/Controllers/Driver/DriverApi/DriverTaskController.php` | `deploy/app/Http/Controllers/Driver/DriverApi/DriverTaskController.php` |
| 3   | `app/Services/TripService.php`                                   | `deploy/app/Services/TripService.php`                                   |
| 4   | `app/Services/DriverScheduleService.php`                         | `deploy/app/Services/DriverScheduleService.php`                         |
| 5   | `app/Models/TripRequest.php`                                     | `deploy/app/Models/TripRequest.php`                                     |
| 6   | `app/Http/Requests/StoreTripRequest.php`                         | `deploy/app/Http/Requests/StoreTripRequest.php`                         |
| 7   | `app/Notifications/DriverAssigned.php`                           | `deploy/app/Notifications/DriverAssigned.php`                           |
| 8   | `config/app.php`                                                 | `deploy/config/app.php`                                                 |
| 9   | `routes/web.php`                                                 | `deploy/routes/web.php`                                                 |

### Frontend (React/Inertia source) — 3 file

| #   | File lokal                                          | Tujuan di server                            |
| --- | --------------------------------------------------- | ------------------------------------------- |
| 10  | `resources/js/Pages/Trips/Index.tsx`                | `deploy/resources/js/Pages/Trips/Index.tsx` |
| 11  | `resources/js/Pages/Trips/Show.tsx`                 | `deploy/resources/js/Pages/Trips/Show.tsx`  |
| 12  | `resources/js/Pages/Trips/Edit.tsx` (**file BARU**) | `deploy/resources/js/Pages/Trips/Edit.tsx`  |

### Hasil build (WAJIB — inilah yang dibaca browser)

| #   | Folder lokal                                          | Tujuan di server       |
| --- | ----------------------------------------------------- | ---------------------- |
| 13  | `public/build/` **seluruh isinya** (zip lalu extract) | `deploy/public/build/` |

> Hapus isi `deploy/public/build/` lama sebelum meng-upload yang baru, agar
> file asset dengan hash lama tidak menumpuk / bentrok manifest.

### TIDAK perlu di-upload

- `tests/**` (dua file test yang berubah hanya untuk local testing)
- `resources/js/Pages/Admin/Vehicles.tsx`, `app/Models/Vehicle.php`,
  `app/Http/Controllers/SectionController.php` — perubahan lain yang tidak
  terkait 3 fitur ini (abaikan bila tidak ingin ikut ter-deploy).

---

## B. ALUR PENGERJAAN DI SERVER (cPanel Domainesia)

### Langkah 1 — Backup dulu (5 menit)

1. cPanel → **File Manager** → masuk `/mmjdriver.site/deploy`
2. Kompres (Compress → ZIP) folder: `app`, `config`, `routes`, `resources/js/Pages/Trips`, `public/build`
   → simpan ZIP-nya sebagai `backup-sebelum-deploy-2026-11-23.zip` di luar folder deploy.

### Langkah 2 — Upload file PHP & TSX (File Manager)

1. Upload 9 file PHP ke path sesuai tabel A.
2. Upload 3 file TSX ke `resources/js/Pages/Trips/`.
   (Klik kanan file lama → kalau ragu, rename dulu jadi `.bak`.)
3. **`Edit.tsx` adalah file baru** — cukup upload.

### Langkah 3 — Upload build frontend

1. Di lokal: zip isi folder `public/build/` menjadi `build.zip`.
2. Upload `build.zip` ke `/mmjdriver.site/deploy/public/`, lalu extract
   (overwrite semua). Hapus `build.zip` setelah berhasil.

### Langkah 4 — Clear cache (WAJIB, sering jadi penyebab error 500)

cPanel → **Terminal** (atau SSH), lalu:

```bash
cd /home/USERNAME/mmjdriver.site/deploy
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

> Penting: `route:clear` wajib karena ada 2 route baru
> (`GET /trips/{trip}/edit` dan `PUT /trips/{trip}`).
> Kalau dulu pernah `route:cache` / `config:cache`, setelah clear ini
> **jangan langsung cache ulang** sebelum situs dipastikan normal.

### Langkah 5 — Permission

```bash
chmod -R 775 storage bootstrap/cache
```

(yang tercek: file PHP & TSX cukup 644.)

### Langkah 6 — Verifikasi mmjdriver.site

| Cek              | Cara                                                                                | Hasil yang benar                                                                                         |
| ---------------- | ----------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| HTTPS / SSL      | buka `https://mmjdriver.site`                                                       | gembok aktif, tanpa warning                                                                              |
| Tidak error 500  | buka halaman login & dashboard                                                      | normal                                                                                                   |
| Route edit aktif | buka `https://mmjdriver.site/trips/{id}/edit` pada trip DRAFT sebagai pemohon/admin | form edit tampil; trip non-draft → 403                                                                   |
| Bug jam          | buat trip baru dengan Jam Berangkat **13:00** → buka detail                         | tampil **13:00** (bukan 20:00)                                                                           |
| Tombol Edit      | halaman Daftar Perjalanan                                                           | ikon pensil hanya pada baris berstatus DRAFT                                                             |
| Status driver    | assign driver ke trip terjadwal besok                                               | driver tetap **Standby** & masih bisa dipilih untuk trip lain; jadi **On Trip** hanya setelah Start Trip |
| Log error        | `storage/logs/laravel.log` (File Manager)                                           | tidak ada error baru setelah langkah 2–6                                                                 |

### Langkah 7 — Cache ulang (opsional, setelah semua normal)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Rollback bila gagal

Extract kembali `backup-sebelum-deploy-2026-11-23.zip`, jalankan ulang
langkah 4 (clear cache).

---

## C. FILE TERKAIT ZONA WAKTU (khusus fix bug jam 13:00 → 20:00)

Perbaikan timezone **menyentuh 7 file** — ini yang harus ada di server agar
jam berangkat tidak bergeser:

| # | File | Peran dalam fix timezone |
|---|---|---|
| 1 | `app/Models/TripRequest.php` | **Paling penting.** Cast kolom `departure_time`, `estimated_return_time`, `actual_return_time` diubah dari `datetime` → `string`, sehingga Laravel TIDAK lagi mengonversi nilai dari/ke UTC saat baca/tulis DB |
| 2 | `config/app.php` | Tambah key baru `'timezone_display' => 'Asia/Jakarta'` (di bawah `'timezone' => 'UTC'`). Dipakai untuk format tampilan notifikasi |
| 3 | `app/Http/Controllers/TripController.php` | `index()` & `show()`: kirim `departure_time` sebagai string lokal `Y-m-d H:i:s` (sebelumnya `toISOString()` yang menambahkan `Z`/UTC) |
| 4 | `app/Http/Controllers/Driver/DriverApi/DriverTaskController.php` | `formatTask()`: `departure_time` & `estimated_return_time` dikirim sebagai string lokal (bukan ISO UTC) |
| 5 | `resources/js/Pages/Trips/Show.tsx` | `formatDateTime()`: normalisasi `spasi → T` sebelum `new Date(...)` agar diparse sebagai waktu LOKAL browser, bukan UTC |
| 6 | `resources/js/Pages/Trips/Index.tsx` | kolom tanggal di tabel juga dinormalisasi sama |
| 7 | `resources/js/Pages/Trips/Edit.tsx` | (file baru) input `datetime-local` mengirim format `Y-m-d\TH:i` lokal tanpa zona |

File pendukung (ikut ter-upload karena dipakai alur yang sama):
- `app/Notifications/DriverAssigned.php` — format jam WhatsApp/email pakai `timezone_display`
- `app/Services/TripService.php` — pesan WA BOD juga pakai `timezone_display`
- `app/Http/Requests/StoreTripRequest.php` — validasi `departure_time` tanpa konversi

**Yang TIDAK perlu diubah di server:**
- `.env` — tidak ada key timezone baru; `APP_TIMEZONE` tidak dipakai. Biarkan seperti sekarang.
- Database MySQL — tidak ada migration untuk fix ini. Kolom tetap `datetime`, isinya sudah waktu lokal.
- PHP timezone server (cPanel) — tidak perlu diubah; aplikasi menanganinya sendiri.

> Catatan: `app.timezone` di `config/app.php` tetap `'UTC'` (default Laravel,
> jangan diubah) — yang ditambahkan hanyalah `timezone_display` di bawahnya.

---

## D. RINGKASAN ISIUBAHAN (apa yang dikerjakan)

1. **Bug jam keberangkatan (13:00 → 20:00)**
    - `TripRequest.php`: kolom `departure_time`, `estimated_return_time`,
      `actual_return_time` tidak lagi di-cast `datetime` (tanpa konversi UTC).
    - `TripController.php` & `DriverTaskController.php`: kirim waktu sebagai
      string lokal `Y-m-d H:i:s` (bukan `toISOString()`).
    - `Trips/Show.tsx` & `Trips/Index.tsx`: normalisasi parsing ke waktu lokal.
    - `config/app.php`: tambah `timezone_display = Asia/Jakarta`.

2. **Tombol Edit khusus DRAFT**
    - Route baru + `TripController::edit()/update()` + `TripService::updateTrip()`.
    - Halaman baru `Trips/Edit.tsx`; ikon pensil di `Trips/Index.tsx`
      hanya muncul bila status `DRAFT`.

3. **Status driver tetap Standby sampai Start Trip**
    - `TripService::driverStatusAfterAssignment()` → STANDBY.
    - `DriverScheduleService`: aktivasi hari-H tidak lagi mengubah status
      driver; ON_TRIP hanya saat Start Trip.
    - Dropdown driver di `Trips/Show.tsx`: driver ter-assign tetap selectable.
