<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DriverAttendance extends Model
{
    use HasFactory;

    /** Disk privat tempat foto selfie absensi disimpan. */
    public const DISK = 'local';

    protected $fillable = [
        'driver_id', 'date',
        'clock_in_at', 'clock_in_photo_path', 'clock_in_photo_timestamp', 'clock_in_lat', 'clock_in_lng',
        'is_late', 'lateness_minutes', 'clock_in_distance_meters',
        'clock_out_at', 'clock_out_photo_path', 'clock_out_photo_timestamp', 'clock_out_lat', 'clock_out_lng',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'clock_in_photo_timestamp' => 'datetime',
        'clock_out_photo_timestamp' => 'datetime',
        'clock_in_lat' => 'float',
        'clock_in_lng' => 'float',
        'clock_out_lat' => 'float',
        'clock_out_lng' => 'float',
        'is_late' => 'boolean',
        'lateness_minutes' => 'integer',
        'clock_in_distance_meters' => 'float',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function isClockedIn(): bool
    {
        return $this->clock_in_at !== null;
    }

    public function isClockedOut(): bool
    {
        return $this->clock_out_at !== null;
    }

    /**
     * Durasi kerja (menit) bila check-in & check-out sudah tercatat.
     */
    public function workDurationMinutes(): ?int
    {
        if (! $this->clock_in_at || ! $this->clock_out_at) {
            return null;
        }

        return (int) $this->clock_in_at->diffInMinutes($this->clock_out_at);
    }

    public function clockInPhotoExists(): bool
    {
        return $this->clock_in_photo_path !== null
            && Storage::disk(self::DISK)->exists($this->clock_in_photo_path);
    }

    public function clockOutPhotoExists(): bool
    {
        return $this->clock_out_photo_path !== null
            && Storage::disk(self::DISK)->exists($this->clock_out_photo_path);
    }
}
