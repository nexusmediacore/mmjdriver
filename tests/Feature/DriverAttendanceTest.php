<?php

namespace Tests\Feature;

use App\Http\Requests\DriverAttendanceRequest;
use App\Models\Driver;
use App\Models\DriverAttendance;
use App\Models\User;
use App\Services\AttendanceRecapService;
use App\Services\DriverAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Feature 2 — Absensi supir menggunakan foto selfie (Web & API Mobile).
 */
class DriverAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake(DriverAttendance::DISK);

        Carbon::setTestNow(Carbon::parse('2026-11-16 08:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function driver(): Driver
    {
        return Driver::where('license_number', 'LIC-001')->firstOrFail()->load('user');
    }

    /**
     * Foto selfie asli (PNG 1x1) — tidak bergantung pada ekstensi GD.
     */
    protected function selfie(string $name = 'selfie.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    public function test_driver_can_clock_in_with_selfie_and_store_private_photo(): void
    {
        $driver = $this->driver();

        // Absen di titik kantor (0.5345679, 101.4258138)
        $response = $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $this->selfie(),
            'lat' => 0.5345679,
            'lng' => 101.4258138,
            'notes' => 'Absen masuk pagi',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $attendance = DriverAttendance::where('driver_id', $driver->id)->firstOrFail();

        $this->assertNotNull($attendance->clock_in_at);
        $this->assertSame('2026-11-16', $attendance->date->toDateString());
        $this->assertNotNull($attendance->clock_in_photo_path);
        $this->assertNotNull($attendance->clock_in_photo_timestamp);
        $this->assertSame('2026-11-16 08:00:00', $attendance->clock_in_photo_timestamp->toDateTimeString());
        $this->assertStringStartsWith("driver-attendances/{$driver->id}/", $attendance->clock_in_photo_path);
        $this->assertStringNotContainsString('selfie.jpg', $attendance->clock_in_photo_path, 'Nama file asli tidak boleh dipakai.');
        $this->assertEquals(0.5345679, $attendance->clock_in_lat);
        $this->assertEquals(101.4258138, $attendance->clock_in_lng);
        $this->assertSame('Absen masuk pagi', $attendance->notes);
        $this->assertNotNull($attendance->clock_in_distance_meters);
        $this->assertLessThanOrEqual(5.0, $attendance->clock_in_distance_meters);

        Storage::disk(DriverAttendance::DISK)->assertExists($attendance->clock_in_photo_path);
        $this->assertStringEndsWith('.png', $attendance->clock_in_photo_path);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CLOCK_IN_ATTENDANCE',
            'record_id' => $attendance->id,
        ]);
    }

    public function test_rejected_mime_type_is_blocked(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors(['photo']);

        $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors(['photo']);

        $this->assertSame(0, DriverAttendance::count());
    }

    public function test_rejected_oversized_photo(): void
    {
        // Batas `upload_max_filesize`/`post_max_size` PHP bervariasi antar lingkungan
        // (mesin dev: 2M), sehingga file > 5 MB sering kali ditolak PHP sebelum
        // Laravel (413) dan error validasi tidak pernah dibuat. Aturan ukuran
        // Laravel sendiri diuji langsung lewat Validator agar deterministik.
        $this->driver();

        $photo = UploadedFile::fake()->createWithContent('big.png', str_repeat('0', DriverAttendanceService::MAX_PHOTO_BYTES + 1024));

        $validator = Validator::make(
            ['photo' => $photo],
            (new DriverAttendanceRequest)->rules(),
            (new DriverAttendanceRequest)->messages()
        );

        $this->assertTrue($validator->fails(), json_encode($validator->errors()->toArray()));
        $this->assertArrayHasKey('photo', $validator->errors()->toArray());
        $this->assertSame(0, DriverAttendance::count());
    }

    public function test_driver_can_clock_out_after_clock_in_via_api(): void
    {
        $driver = $this->driver();
        $token = $driver->user->createToken('test')->plainTextToken;

        $clockIn = $this->withToken($token)
            ->postJson('/api/driver/attendance/clock-in', [
                'photo' => $this->selfie('in.png'),
                'lat' => 0.5345679,
                'lng' => 101.4258138,
            ]);
        $clockIn->assertOk()
            ->assertJsonPath('attendance.date', '2026-11-16');
        $this->assertNotNull($clockIn->json('attendance.clock_in_at'));

        Carbon::setTestNow(Carbon::parse('2026-11-16 17:30:00'));

        $clockOut = $this->withToken($token)
            ->postJson('/api/driver/attendance/clock-out', [
                'photo' => $this->selfie('out.png'),
                'lat' => 0.5345679,
                'lng' => 101.4258138,
            ]);

        $clockOut->assertOk();
        $this->assertSame(570, $clockOut->json('attendance.work_duration_minutes'));
        $this->assertNotNull($clockOut->json('attendance.clock_in_photo_timestamp'));
        $this->assertNotNull($clockOut->json('attendance.clock_out_photo_timestamp'));

        $attendance = DriverAttendance::where('driver_id', $driver->id)->firstOrFail();

        $this->assertNotNull($attendance->clock_out_photo_path);
        $this->assertNotNull($attendance->clock_out_photo_timestamp);
        $this->assertSame('2026-11-16 17:30:00', $attendance->clock_out_photo_timestamp->toDateTimeString());
        Storage::disk(DriverAttendance::DISK)->assertExists($attendance->clock_out_photo_path);
    }

    public function test_driver_cannot_clock_in_twice_per_day(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $this->selfie(),
            'lat' => 0.5345679,
            'lng' => 101.4258138,
        ]);
        $second = $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $this->selfie(),
            'lat' => 0.5345679,
            'lng' => 101.4258138,
        ]);

        $this->assertTrue($second->isRedirect());
        $this->assertNotNull(session('error'));
        $this->assertSame(1, DriverAttendance::count());
    }

    public function test_driver_cannot_clock_out_without_clock_in(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver->user)
            ->post(route('driver.attendance.clock-out'), [
                'photo' => $this->selfie(),
                'lat' => 0.5345679,
                'lng' => 101.4258138,
            ]);

        $this->assertNotNull(session('error'));
        $this->assertSame(0, DriverAttendance::count());
    }

    public function test_photo_is_accessible_only_to_owner_and_management(): void
    {
        $driver = $this->driver();
        $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $this->selfie(),
            'lat' => 0.5345679,
            'lng' => 101.4258138,
        ]);

        $attendance = DriverAttendance::where('driver_id', $driver->id)->firstOrFail();

        // Pemilik
        $this->actingAs($driver->user)
            ->get(route('driver.attendance.photo', ['attendance' => $attendance->id, 'type' => 'in']))
            ->assertOk();

        // Driver lain tidak boleh
        $otherDriver = User::where('email', 'driver3@example.com')->firstOrFail();
        $this->actingAs($otherDriver)
            ->get(route('driver.attendance.photo', ['attendance' => $attendance->id, 'type' => 'in']))
            ->assertForbidden();

        // Admin boleh
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.driver-attendances.photo', $attendance->id))
            ->assertOk();

        // Employee tidak boleh
        $employee = User::where('email', 'employee1@example.com')->firstOrFail();
        $this->actingAs($employee)
            ->get(route('admin.driver-attendances.photo', $attendance->id))
            ->assertForbidden();
    }

    public function test_admin_recap_page_lists_attendance_for_selected_date(): void
    {
        $driver = $this->driver();
        $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $this->selfie(),
            'lat' => 0.5345679,
            'lng' => 101.4258138,
        ]);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.driver-attendances', ['anchor' => '2026-11-16']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/DriverAttendances')
                ->where('mode', 'daily')
                ->where('anchor', '2026-11-16')
                ->where('summary.present', 1)
                ->has('rows'));

        // Baris rekap driver bersangkutan memuat 1 record kehadiran
        $row = collect(app(AttendanceRecapService::class)->rows('daily', '2026-11-16'))
            ->firstWhere('driver_id', $driver->id);
        $this->assertNotNull($row);
        $this->assertCount(1, $row['records']);
        $this->assertSame(1, $row['summary']['present']);
    }

    public function test_employee_cannot_access_attendance_recap(): void
    {
        $employee = User::where('email', 'employee1@example.com')->firstOrFail();

        $this->actingAs($employee)
            ->get(route('admin.driver-attendances'))
            ->assertForbidden();
    }

    public function test_api_attendance_history_returns_today_and_history(): void
    {
        $driver = $this->driver();
        $token = $driver->user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/driver/attendance/clock-in', [
            'photo' => $this->selfie(),
            'lat' => 0.5345679,
            'lng' => 101.4258138,
        ])->assertOk();

        $response = $this->withToken($token)
            ->getJson('/api/driver/attendance')
            ->assertOk()
            ->assertJsonPath('today.date', '2026-11-16')
            ->assertJsonCount(1, 'history');

        $this->assertNotNull($response->json('today.clock_in_photo_timestamp'));
    }

    public function test_photo_binary_is_watermarked_with_timestamp_when_gd_available(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('Ekstensi GD tidak tersedia.');
        }

        $driver = $this->driver();

        // Buat gambar PNG 200x200 (format yang didukung GD di lingkungan ini)
        $im = imagecreatetruecolor(200, 200);
        $bg = imagecolorallocate($im, 100, 150, 200);
        imagefilledrectangle($im, 0, 0, 200, 200, $bg);
        ob_start();
        imagepng($im);
        $pngData = ob_get_clean();
        imagedestroy($im);

        $file = UploadedFile::fake()->createWithContent('selfie-real.png', $pngData);

        $response = $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $file,
            'lat' => 0.5345679,
            'lng' => 101.4258138,
        ]);
        $response->assertRedirect();

        $attendance = DriverAttendance::where('driver_id', $driver->id)->firstOrFail();
        $stored = Storage::disk(DriverAttendance::DISK)->get($attendance->clock_in_photo_path);

        $this->assertNotEmpty($stored);
        // Biner tersimpan berbeda dari biner asli karena telah dibubuhi timestamp watermark oleh GD
        $this->assertNotSame($pngData, $stored, 'Foto yang disimpan harus telah dibubuhi watermark timestamp.');

        // Pastikan foto tersimpan masih valid dan dapat dibaca oleh GD
        $verified = @imagecreatefromstring($stored);
        $this->assertNotFalse($verified, 'Foto tersimpan harus berupa gambar yang valid.');
        imagedestroy($verified);
    }

    public function test_clock_in_after_07_45_wib_is_marked_as_late(): void
    {
        $driver = $this->driver();

        // 08:15:00 WIB (lewat dari 07:45 WIB -> 30 menit terlambat)
        Carbon::setTestNow(Carbon::parse('2026-11-16 08:15:00', 'Asia/Jakarta'));

        $response = $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $this->selfie(),
            'lat' => 0.5345679,
            'lng' => 101.4258138,
            'notes' => 'Masuk agak siang karena macet',
        ]);

        $response->assertRedirect();
        $attendance = DriverAttendance::where('driver_id', $driver->id)->firstOrFail();

        $this->assertTrue($attendance->is_late);
        $this->assertSame(30, $attendance->lateness_minutes);
    }

    public function test_clock_in_at_or_before_07_45_wib_is_on_time(): void
    {
        $driver = $this->driver();

        // 07:25:00 WIB (sebelum 07:45 WIB -> tepat waktu)
        Carbon::setTestNow(Carbon::parse('2026-11-16 07:25:00', 'Asia/Jakarta'));

        $response = $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $this->selfie(),
            'lat' => 0.5345679,
            'lng' => 101.4258138,
        ]);

        $response->assertRedirect();
        $attendance = DriverAttendance::where('driver_id', $driver->id)->firstOrFail();

        $this->assertFalse($attendance->is_late);
        $this->assertSame(0, $attendance->lateness_minutes);
    }

    public function test_clock_in_outside_100_meters_radius_is_rejected(): void
    {
        $driver = $this->driver();

        // Lokasi di luar radius (> 100m, misal selisih 0.005 derajat ~ 550 meter)
        $response = $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $this->selfie(),
            'lat' => 0.5400000,
            'lng' => 101.4258138,
        ]);

        $response->assertSessionHasErrors(['location']);
        $this->assertSame(0, DriverAttendance::count());
    }

    public function test_clock_in_without_gps_coordinates_is_rejected(): void
    {
        $driver = $this->driver();

        $response = $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $this->selfie(),
        ]);

        $response->assertSessionHasErrors(['location']);
        $this->assertSame(0, DriverAttendance::count());
    }

    public function test_driver_on_trip_status_helper_and_recap_display(): void
    {
        $driver = $this->driver();
        $driver->update(['status' => Driver::STATUS_ON_TRIP]);

        $this->assertTrue($driver->isOnTrip());

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $response = $this->actingAs($admin)->get(route('admin.driver-attendances'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/DriverAttendances')
                ->where('summary.on_trip', fn ($val) => $val >= 1));
    }

    public function test_clock_out_photo_is_served_when_type_out_is_requested(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver->user)->post(route('driver.attendance.clock-in'), [
            'photo' => $this->selfie('in.png'),
            'lat' => 0.5345679,
            'lng' => 101.4258138,
        ])->assertRedirect();

        Carbon::setTestNow(Carbon::parse('2026-11-16 17:30:00'));

        $this->actingAs($driver->user)->post(route('driver.attendance.clock-out'), [
            'photo' => $this->selfie('out.png'),
            'lat' => 0.5345679,
            'lng' => 101.4258138,
        ])->assertRedirect();

        $attendance = DriverAttendance::where('driver_id', $driver->id)->firstOrFail();
        $this->assertNotSame($attendance->clock_in_photo_path, $attendance->clock_out_photo_path);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $inPath = $attendance->clock_in_photo_path;

        // Kedua foto dummy identik secara biner; kebenaran tipe diverifikasi
        // dengan menghapus file foto masuk: ?type=in harus 404, ?type=out
        // tetap OK (membuktikan ?type=out membaca kolom foto pulang).
        $this->actingAs($admin)
            ->get(route('admin.driver-attendances.photo', ['attendance' => $attendance->id]).'?type=in')
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.driver-attendances.photo', ['attendance' => $attendance->id]).'?type=out')
            ->assertOk();

        Storage::disk(DriverAttendance::DISK)->delete($inPath);

        $this->actingAs($admin)
            ->get(route('admin.driver-attendances.photo', ['attendance' => $attendance->id]).'?type=in')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.driver-attendances.photo', ['attendance' => $attendance->id]).'?type=out')
            ->assertOk();

        // Tanpa parameter -> default foto masuk (404 karena sudah dihapus).
        $this->actingAs($admin)
            ->get(route('admin.driver-attendances.photo', $attendance->id))
            ->assertNotFound();
    }
}
