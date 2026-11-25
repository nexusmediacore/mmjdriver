<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BeritaAcaraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, '__invoke'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Trip Routes
    Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
    Route::get('/trips/create', [TripController::class, 'create'])->name('trips.create');
    Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
    Route::get('/trips/{trip}/edit', [TripController::class, 'edit'])->name('trips.edit');
    Route::put('/trips/{trip}', [TripController::class, 'update'])->name('trips.update');
    Route::get('/trips/{trip}', [TripController::class, 'show'])->name('trips.show');
    Route::post('/trips/{trip}/submit', [TripController::class, 'submit'])->name('trips.submit');
    Route::post('/trips/{trip}/approve', [TripController::class, 'approve'])->name('trips.approve');
    Route::post('/trips/{trip}/reject', [TripController::class, 'reject'])->name('trips.reject');
    Route::post('/trips/{trip}/assign-driver', [TripController::class, 'assignDriver'])->name('trips.assign-driver');
    Route::post('/trips/{trip}/driver-response', [TripController::class, 'driverResponse'])->name('trips.driver-response');
    Route::post('/trips/{trip}/start', [TripController::class, 'startTrip'])->name('trips.start');

    // Persetujuan Superadmin untuk driver bantu muat (muat barang)
    Route::post('/trips/{trip}/loading-approval', [TripController::class, 'loadingApproval'])
        ->name('trips.loading-approval');
    // Pengakhiran perjalanan langsung oleh driver / pemohon (tanpa approval)
    Route::post('/trips/{trip}/complete', [TripController::class, 'submitCompletion'])->name('trips.complete');
    Route::delete('/trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');

    // Berita Acara Routes
    Route::post('/trips/{trip}/berita-acara', [BeritaAcaraController::class, 'generate'])->name('trips.berita-acara.generate');
    Route::get('/trips/{trip}/berita-acara/pdf', [BeritaAcaraController::class, 'download'])->name('trips.berita-acara.pdf');

    // Report Routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
});

Route::middleware('auth')->group(function () {
    Route::get('/admin/users', [SectionController::class, 'users'])->name('admin.users');
    Route::post('/admin/users', [SectionController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [SectionController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [SectionController::class, 'destroyUser'])->name('admin.users.destroy');

    // Data akun Driver siap didaftarkan (dipakai form Tambah Driver)
    Route::get('/admin/drivers/available-users', [SectionController::class, 'availableDriverUsers'])
        ->name('admin.drivers.available-users');

    Route::get('/admin/drivers', [SectionController::class, 'drivers'])->name('admin.drivers');
    Route::post('/admin/drivers', [SectionController::class, 'storeDriver'])->name('admin.drivers.store');
    Route::put('/admin/drivers/{driver}', [SectionController::class, 'updateDriver'])->name('admin.drivers.update');
    Route::delete('/admin/drivers/{driver}', [SectionController::class, 'destroyDriver'])->name('admin.drivers.destroy');
    Route::post('/admin/drivers/{driver}/vehicles', [SectionController::class, 'updateDriverVehicles'])->name('admin.drivers.vehicles');
    Route::get('/admin/drivers/{driver}/allowed-vehicles', [SectionController::class, 'driverAllowedVehicles'])->name('admin.drivers.allowed-vehicles');

    Route::get('/admin/vehicles', [SectionController::class, 'vehicles'])->name('admin.vehicles');
    Route::post('/admin/vehicles', [SectionController::class, 'storeVehicle'])->name('admin.vehicles.store');
    Route::put('/admin/vehicles/{vehicle}', [SectionController::class, 'updateVehicle'])->name('admin.vehicles.update');
    Route::delete('/admin/vehicles/{vehicle}', [SectionController::class, 'destroyVehicle'])->name('admin.vehicles.destroy');
    Route::get('/admin/vehicles/{vehicle}/authorized-drivers', [SectionController::class, 'vehicleAuthorizedDrivers'])->name('admin.vehicles.authorized-drivers');
    Route::post('/admin/vehicles/{vehicle}/drivers', [SectionController::class, 'updateVehicleDrivers'])->name('admin.vehicles.drivers');

    Route::get('/admin/departments', [SectionController::class, 'departments'])->name('admin.departments');
    Route::post('/admin/departments', [SectionController::class, 'storeDepartment'])->name('admin.departments.store');
    Route::put('/admin/departments/{department}', [SectionController::class, 'updateDepartment'])->name('admin.departments.update');
    Route::delete('/admin/departments/{department}', [SectionController::class, 'destroyDepartment'])->name('admin.departments.destroy');

    Route::get('/admin/berita-acara', [SectionController::class, 'beritaAcara'])->name('admin.berita-acara');
    Route::delete('/admin/berita-acara/{beritaAcara}', [SectionController::class, 'destroyBeritaAcara'])->name('admin.berita-acara.destroy');
    Route::get('/admin/audit-logs', [SectionController::class, 'auditLogs'])->name('admin.audit-logs');

    // Rekap & monitoring absensi driver (dengan foto selfie)
    Route::get('/admin/driver-attendances', [AttendanceController::class, 'index'])->name('admin.driver-attendances');
    Route::get('/admin/driver-attendances/{attendance}/photo', [AttendanceController::class, 'photo'])->name('admin.driver-attendances.photo');
    Route::get('/dispatcher/driver-attendances', [AttendanceController::class, 'index'])->name('dispatcher.driver-attendances');
    Route::get('/admin/settings', [SectionController::class, 'settings'])->name('admin.settings');
    Route::post('/admin/settings', [SectionController::class, 'updateSettings'])->name('admin.settings.update');

    Route::get('/dispatcher/driver-availability', [SectionController::class, 'driverAvailability'])->name('dispatcher.driver-availability');
    Route::get('/dispatcher/vehicle-availability', [SectionController::class, 'vehicleAvailability'])->name('dispatcher.vehicle-availability');
    Route::get('/dispatcher/active-trips', [SectionController::class, 'activeTrips'])->name('dispatcher.active-trips');
    Route::get('/dispatcher/schedule', [SectionController::class, 'schedule'])->name('dispatcher.schedule');

    Route::get('/approver/pending-approvals', [SectionController::class, 'pendingApprovals'])->name('approver.pending-approvals');
    Route::get('/approver/trip-history', [SectionController::class, 'tripHistory'])->name('approver.trip-history');

    Route::get('/driver/my-tasks', [SectionController::class, 'myTasks'])->name('driver.my-tasks');
    Route::get('/driver/trip-history', [SectionController::class, 'driverTripHistory'])->name('driver.trip-history');

    // Absensi driver (check-in / check-out dengan foto selfie)
    Route::get('/driver/attendance', [AttendanceController::class, 'index'])->name('driver.attendance');
    Route::post('/driver/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('driver.attendance.clock-in');
    Route::post('/driver/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('driver.attendance.clock-out');
    Route::get('/driver/attendance/{attendance}/photo/{type}', [AttendanceController::class, 'photo'])->name('driver.attendance.photo');

    // Notifikasi dalam aplikasi (lonceng di topbar)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

require __DIR__.'/auth.php';
