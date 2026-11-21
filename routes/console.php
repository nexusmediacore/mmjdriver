<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Feature: aktivasi trip terjadwal pada hari H. Driver yang ditugaskan ke
// trip masa depan tetap STANDBY; saat tanggalnya tiba, tugas diaktifkan dan
// status driver disesuaikan.
Schedule::command('trips:activate-scheduled --refresh-drivers')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
