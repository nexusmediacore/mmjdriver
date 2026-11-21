<?php

use App\Http\Controllers\Driver\DriverApi\DriverAttendanceController;
use App\Http\Controllers\Driver\DriverApi\DriverAuthController;
use App\Http\Controllers\Driver\DriverApi\DriverTaskController;
use App\Http\Controllers\Driver\DriverApi\DriverVehicleController;
use App\Http\Controllers\Driver\DriverApi\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver Mobile API Routes (Sanctum)
|--------------------------------------------------------------------------
| Base path: /api/driver
*/

Route::prefix('driver')->group(function () {
    // --- Auth ---
    Route::post('/login', [DriverAuthController::class, 'login'])
        ->name('api.driver.login');

    Route::post('/refresh', [DriverAuthController::class, 'refresh'])
        ->middleware('auth:sanctum')
        ->name('api.driver.refresh');

    // --- Protected ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [DriverAuthController::class, 'me'])
            ->name('api.driver.me');

        Route::post('/logout', [DriverAuthController::class, 'logout'])
            ->name('api.driver.logout');

        Route::post('/device', [DriverAuthController::class, 'registerDevice'])
            ->name('api.driver.device');

        // --- Tasks ---
        Route::get('/tasks', [DriverTaskController::class, 'index'])
            ->name('api.driver.tasks');

        Route::get('/tasks/{trip}', [DriverTaskController::class, 'show'])
            ->name('api.driver.tasks.show');

        Route::post('/tasks/{trip}/accept', [DriverTaskController::class, 'accept'])
            ->name('api.driver.tasks.accept');

        Route::post('/tasks/{trip}/reject', [DriverTaskController::class, 'reject'])
            ->name('api.driver.tasks.reject');

        Route::post('/tasks/{trip}/start', [DriverTaskController::class, 'startTrip'])
            ->name('api.driver.tasks.start');

        Route::post('/tasks/{trip}/complete', [DriverTaskController::class, 'completeTrip'])
            ->name('api.driver.tasks.complete');

        // --- Attendance (selfie) ---
        Route::get('/attendance', [DriverAttendanceController::class, 'index'])
            ->name('api.driver.attendance');

        Route::post('/attendance/clock-in', [DriverAttendanceController::class, 'clockIn'])
            ->name('api.driver.attendance.clock-in');

        Route::post('/attendance/clock-out', [DriverAttendanceController::class, 'clockOut'])
            ->name('api.driver.attendance.clock-out');

        Route::post('/tasks/{trip}/update', [DriverTaskController::class, 'updateLocation'])
            ->name('api.driver.tasks.update');

        // --- Vehicle ---
        Route::get('/vehicle', [DriverVehicleController::class, 'show'])
            ->name('api.driver.vehicle');

        Route::post('/vehicle/{vehicle}/status', [DriverVehicleController::class, 'updateStatus'])
            ->name('api.driver.vehicle.status');

        // --- Notifications ---
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('api.driver.notifications');

        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
            ->name('api.driver.notifications.read');
    });
});
