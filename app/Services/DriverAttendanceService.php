<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\DriverAttendance;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class DriverAttendanceService
{
    /** Maksimal ukuran foto selfie: 5 MB. */
    public const MAX_PHOTO_BYTES = 5 * 1024 * 1024;

    /** Ekstensi yang diizinkan (dari MIME yang sudah divalidasi). */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Koordinat & Alamat Kantor PT. MARITA MAKMUR JAYA (Pekanbaru, Riau)
     * Titik koordinat: 0.5345679, 101.4258138
     */
    public const OFFICE_LATITUDE = 0.5345679;

    public const OFFICE_LONGITUDE = 101.4258138;

    public const OFFICE_STREET_NAME = 'Jl. Angkasa, Pekanbaru';

    /**
     * Radius absensi yang diizinkan (dalam meter).
     */
    public const MAX_RADIUS_METERS = 100.0;

    /**
     * Batas jam masuk tepat waktu (WIB / Asia/Jakarta).
     * Lewat dari pukul 07:30 WIB dinyatakan telat.
     */
    public const CUTOFF_HOUR = 7;

    public const CUTOFF_MINUTE = 45;

    public const TIMEZONE = 'Asia/Jakarta';

    /**
     * Hitung jarak dua titik koordinat (dalam meter) menggunakan rumus Haversine.
     */
    public function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    /**
     * Validasi lokasi GPS terhadap radius kantor yang ditentukan.
     * Mengembalikan jarak dalam meter jika valid, atau melempar ValidationException jika di luar radius.
     */
    public function validateOfficeRadius(?float $lat, ?float $lng): float
    {
        if ($lat === null || $lng === null) {
            throw ValidationException::withMessages([
                'location' => 'Lokasi GPS wajib diaktifkan. Absensi harus berada dalam radius 100 meter dari kantor.',
            ]);
        }

        $distance = $this->calculateDistanceMeters(
            $lat,
            $lng,
            self::OFFICE_LATITUDE,
            self::OFFICE_LONGITUDE
        );

        if ($distance > self::MAX_RADIUS_METERS) {
            $formatted = number_format($distance, 1, ',', '.');
            throw ValidationException::withMessages([
                'location' => "Anda berada di luar jangkauan ({$formatted} meter dari kantor). Absensi hanya dapat dilakukan dalam radius maksimal 100 meter dari kantor PT. Marita Makmur Jaya.",
            ]);
        }

        return $distance;
    }

    /**
     * Tentukan apakah waktu absensi masuk terhitung terlambat (lewat 07:45 WIB).
     *
     * @return array{is_late: bool, lateness_minutes: int}
     */
    public function evaluateLateness(Carbon $time): array
    {
        $timeInWib = $time->copy()->setTimezone(self::TIMEZONE);
        $cutoff = $timeInWib->copy()->setTime(self::CUTOFF_HOUR, self::CUTOFF_MINUTE, 0);

        if ($timeInWib->greaterThan($cutoff)) {
            $minutes = (int) ceil($cutoff->diffInRealSeconds($timeInWib) / 60);

            return [
                'is_late' => true,
                'lateness_minutes' => max(1, $minutes),
            ];
        }

        return [
            'is_late' => false,
            'lateness_minutes' => 0,
        ];
    }

    /**
     * Catat absensi masuk.
     *
     * @param  array{lat?: float|string|null, lng?: float|string|null, notes?: string|null}  $data
     */
    public function clockIn(Driver $driver, UploadedFile $photo, array $data = [], $actor = null): DriverAttendance
    {
        $lat = isset($data['lat']) && $data['lat'] !== null && $data['lat'] !== '' ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) && $data['lng'] !== null && $data['lng'] !== '' ? (float) $data['lng'] : null;

        $distance = $this->validateOfficeRadius($lat, $lng);

        return DB::transaction(function () use ($driver, $photo, $data, $actor, $lat, $lng, $distance) {
            $today = Carbon::today();
            $attendance = $this->findOrCreateToday($driver, $today);

            if ($attendance->isClockedIn()) {
                throw new ConflictHttpException('Anda sudah melakukan absensi masuk hari ini.');
            }

            $now = Carbon::now();
            $lateness = $this->evaluateLateness($now);

            $path = $this->storePhoto($photo, $driver, 'in', $lat, $lng);

            $attendance->update([
                'clock_in_at' => $now,
                'clock_in_photo_path' => $path,
                'clock_in_photo_timestamp' => $now,
                'clock_in_lat' => $lat,
                'clock_in_lng' => $lng,
                'clock_in_distance_meters' => $distance,
                'is_late' => $lateness['is_late'],
                'lateness_minutes' => $lateness['lateness_minutes'],
                'notes' => $data['notes'] ?? $attendance->notes,
            ]);

            $this->audit($actor ?? $driver->user, 'CLOCK_IN_ATTENDANCE', $attendance);

            return $attendance->fresh();
        });
    }

    /**
     * Catat absensi pulang.
     *
     * @param  array{lat?: float|string|null, lng?: float|string|null, notes?: string|null}  $data
     */
    public function clockOut(Driver $driver, UploadedFile $photo, array $data = [], $actor = null): DriverAttendance
    {
        $lat = isset($data['lat']) && $data['lat'] !== null && $data['lat'] !== '' ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) && $data['lng'] !== null && $data['lng'] !== '' ? (float) $data['lng'] : null;

        $distance = $this->validateOfficeRadius($lat, $lng);

        return DB::transaction(function () use ($driver, $photo, $data, $actor, $lat, $lng) {
            $attendance = DriverAttendance::where('driver_id', $driver->id)
                ->whereDate('date', Carbon::today())
                ->first();

            if (! $attendance || ! $attendance->isClockedIn()) {
                throw new ConflictHttpException('Anda belum melakukan absensi masuk hari ini.');
            }

            if ($attendance->isClockedOut()) {
                throw new ConflictHttpException('Anda sudah melakukan absensi pulang hari ini.');
            }

            $path = $this->storePhoto($photo, $driver, 'out', $lat, $lng);

            $attendance->update([
                'clock_out_at' => now(),
                'clock_out_photo_path' => $path,
                'clock_out_photo_timestamp' => now(),
                'clock_out_lat' => $lat,
                'clock_out_lng' => $lng,
                'notes' => $data['notes'] ?? $attendance->notes,
            ]);

            $this->audit($actor ?? $driver->user, 'CLOCK_OUT_ATTENDANCE', $attendance);

            return $attendance->fresh();
        });
    }

    /**
     * Absensi driver pada tanggal tertentu (null bila belum ada).
     */
    public function todayFor(Driver $driver): ?DriverAttendance
    {
        return DriverAttendance::where('driver_id', $driver->id)
            ->whereDate('date', Carbon::today())
            ->first();
    }

    protected function findOrCreateToday(Driver $driver, Carbon $date): DriverAttendance
    {
        // lockForUpdate mencegah dua request check-in bersamaan membuat 2 baris.
        $existing = DriverAttendance::where('driver_id', $driver->id)
            ->whereDate('date', $date)
            ->lockForUpdate()
            ->first();

        return $existing ?? DriverAttendance::create([
            'driver_id' => $driver->id,
            'date' => $date->toDateString(),
        ]);
    }

    /**
     * Simpan foto selfie dengan nama file yang dibangkitkan sistem,
     * serta membubuhkan watermark timestamp permanen ke gambar jika ekstensi GD tersedia.
     * Lokasi: storage/app/private/driver-attendances/{driverId}/{uuid}.{ext}
     */
    protected function storePhoto(UploadedFile $photo, Driver $driver, string $type, ?float $lat = null, ?float $lng = null): string
    {
        $extension = strtolower($photo->extension() ?: 'jpg');

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = 'jpg';
        }

        $filename = sprintf('%s-%s.%s', $type, (string) str()->uuid(), $extension);
        $relativeDir = "driver-attendances/{$driver->id}";
        $relativeFullPath = "{$relativeDir}/{$filename}";

        $label = ($type === 'in' ? 'ABSEN MASUK' : 'ABSEN PULANG').' • '.($driver->user?->name ?? 'DRIVER');
        $watermarkedBinary = $this->applyServerTimestampWatermark(
            (string) file_get_contents($photo->getRealPath()),
            $label,
            $extension,
            $lat,
            $lng
        );

        Storage::disk(DriverAttendance::DISK)->put($relativeFullPath, $watermarkedBinary);

        return $relativeFullPath;
    }

    /**
     * Bubuhkan overlay timestamp pada biner gambar menggunakan GD.
     * Jika GD tidak tersedia atau format gambar tidak didukung/rusak,
     * fungsi secara aman mengembalikan biner asli tanpa merusak alur absensi.
     */
    protected function applyServerTimestampWatermark(
        string $binary,
        string $label,
        string $extension,
        ?float $lat = null,
        ?float $lng = null
    ): string {
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
            return $binary;
        }

        try {
            $image = @imagecreatefromstring($binary);
            if (! $image) {
                return $binary;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Jika gambar terlalu kecil (misal dummy test 1x1), lewati pembubuhan visual
            if ($width < 30 || $height < 30) {
                imagedestroy($image);

                return $binary;
            }

            // Timestamp waktu sekarang dalam zona waktu aplikasi
            $timestampText = now()->translatedFormat('l, d F Y • H:i:s').' WIB';

            // Koordinat & Jalan
            $locationLines = [];
            if ($lat !== null && $lng !== null) {
                $locationLines[] = sprintf('Koordinat: %.6f, %.6f', $lat, $lng);
                $locationLines[] = 'Lokasi: '.self::OFFICE_STREET_NAME;
            }

            // Hitung ukuran baris watermark proporsional
            $lineCount = 2 + count($locationLines);
            $lineHeight = 16;
            $barHeight = max(55, (int) round($height * 0.12), ($lineCount * $lineHeight) + 16);
            $barY = $height - $barHeight;

            // Baris latar hitam transparan (alpha: 0 = opaque, 127 = completely transparent)
            imagealphablending($image, true);
            imagesavealpha($image, true);
            $bgOverlay = imagecolorallocatealpha($image, 15, 23, 42, 35);
            if ($bgOverlay !== false) {
                imagefilledrectangle($image, 0, $barY, $width, $height, $bgOverlay);
            }

            // Garis aksen biru di atas baris info
            $accent = imagecolorallocate($image, 37, 99, 235);
            if ($accent !== false) {
                imagefilledrectangle($image, 0, $barY, $width, $barY + 3, $accent);
            }

            // Teks label & timestamp menggunakan built-in font GD (1–5)
            $textColor = imagecolorallocate($image, 255, 255, 255);
            $timestampColor = imagecolorallocate($image, 252, 211, 77); // amber / gold
            $locationColor = imagecolorallocate($image, 147, 197, 253); // blue light

            $currY = $barY + 6;

            if ($textColor !== false) {
                imagestring($image, 4, 12, $currY, strtoupper($label), $textColor);
                $currY += 16;
            }

            if ($timestampColor !== false) {
                imagestring($image, 3, 12, $currY, $timestampText, $timestampColor);
                $currY += 14;
            }

            if ($locationColor !== false) {
                foreach ($locationLines as $locLine) {
                    imagestring($image, 2, 12, $currY, $locLine, $locationColor);
                    $currY += 12;
                }
            }

            ob_start();
            if ($extension === 'png' && function_exists('imagepng')) {
                imagepng($image);
            } elseif ($extension === 'webp' && function_exists('imagewebp')) {
                imagewebp($image, null, 90);
            } elseif (function_exists('imagejpeg')) {
                imagejpeg($image, null, 90);
            } elseif (function_exists('imagepng')) {
                imagepng($image);
            }
            $result = ob_get_clean();

            imagedestroy($image);

            return $result ?: $binary;
        } catch (\Throwable) {
            return $binary;
        }
    }

    protected function audit($user, string $action, DriverAttendance $attendance): void
    {
        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'module' => 'DriverAttendance',
            'record_type' => 'DriverAttendance',
            'record_id' => $attendance->id,
            'new_value' => json_encode([
                'driver_id' => $attendance->driver_id,
                'date' => $attendance->date?->toDateString(),
                'clock_in_at' => $attendance->clock_in_at?->toDateTimeString(),
                'clock_out_at' => $attendance->clock_out_at?->toDateTimeString(),
            ]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Hapus file foto absensi (dipanggil saat record dihapus).
     */
    public function deletePhoto(?string $path): void
    {
        if ($path && Storage::disk(DriverAttendance::DISK)->exists($path)) {
            Storage::disk(DriverAttendance::DISK)->delete($path);
        }
    }
}
