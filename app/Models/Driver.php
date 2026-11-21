<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    // Status aktif (driver dapat bertugas)
    const STATUS_STANDBY = 'STANDBY';

    const STATUS_ASSIGNED = 'ASSIGNED';

    const STATUS_ACCEPTED = 'ACCEPTED';

    const STATUS_ON_TRIP = 'ON_TRIP';

    const STATUS_RETURNING = 'RETURNING';

    const STATUS_COMPLETED = 'COMPLETED';

    // Status tidak bekerja (diatur oleh SuperAdmin)
    const STATUS_SICK = 'SICK';

    const STATUS_OFF_DUTY = 'OFF_DUTY';

    const STATUS_LEAVE = 'LEAVE';

    const STATUS_UNAVAILABLE = 'UNAVAILABLE';

    protected $fillable = [
        'user_id', 'license_number', 'vehicle_plate', 'vehicle_type',
        'phone', 'status', 'willing_to_load', 'notes', 'last_status_change_at',
    ];

    protected $casts = [
        'willing_to_load' => 'boolean',
        'last_status_change_at' => 'datetime',
    ];

    /**
     * Status yang berarti driver sedang TIDAK BEKERJA.
     * Status ini diatur manual oleh SuperAdmin dan tidak boleh ditimpa
     * oleh alur perjalanan otomatis.
     *
     * @return array<int, string>
     */
    public static function inactiveStatuses(): array
    {
        return [
            self::STATUS_SICK,
            self::STATUS_OFF_DUTY,
            self::STATUS_LEAVE,
            self::STATUS_UNAVAILABLE,
        ];
    }

    /**
     * Apakah driver sedang tidak bekerja (sakit / off / cuti / tidak tersedia)?
     */
    public function isInactive(): bool
    {
        return in_array($this->status, self::inactiveStatuses(), true);
    }

    /**
     * Label berbahasa Indonesia untuk setiap status driver.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_STANDBY => 'Standby',
            self::STATUS_ASSIGNED => 'Ditugaskan',
            self::STATUS_ACCEPTED => 'Menerima Tugas',
            self::STATUS_ON_TRIP => 'Sedang Bertugas',
            self::STATUS_RETURNING => 'Kembali ke Base',
            self::STATUS_COMPLETED => 'Selesai Bertugas',
            self::STATUS_SICK => 'Sakit',
            self::STATUS_OFF_DUTY => 'Off (Tidak Bekerja)',
            self::STATUS_LEAVE => 'Cuti',
            self::STATUS_UNAVAILABLE => 'Tidak Tersedia',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(DriverStatusLog::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'primary_driver_id');
    }

    public function allowedVehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'driver_vehicle');
    }

    public function tripRequests()
    {
        return $this->hasMany(TripRequest::class, 'assigned_driver_id');
    }

    public function tripAssignments()
    {
        return $this->hasMany(TripAssignment::class);
    }

    public function loadingRequests()
    {
        return $this->hasMany(LoadingRequest::class);
    }

    public function attendances()
    {
        return $this->hasMany(DriverAttendance::class);
    }

    /**
     * Apakah driver sedang dalam perjalanan atau sedang mengerjakan tugas (on trip / returning).
     */
    public function isOnTrip(): bool
    {
        return in_array($this->status, [self::STATUS_ON_TRIP, self::STATUS_RETURNING], true);
    }

    /**
     * Ambil trip aktif yang sedang dijalankan oleh driver (jika ada).
     */
    public function activeTrip(): ?TripRequest
    {
        return TripRequest::where(function ($query) {
            $query->where('assigned_driver_id', $this->id)
                ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $this->id));
        })
            ->whereIn('status', [
                TripRequest::STATUS_ON_TRIP,
                TripRequest::STATUS_COMPLETION_SUBMITTED,
            ])
            ->latest('started_at')
            ->first();
    }

    /**
     * Trip tempat driver ini ditugaskan, baik sebagai driver utama maupun
     * driver pendukung loading (multi-driver assignment).
     */
    public function assignedTrips()
    {
        return TripRequest::where('assigned_driver_id', $this->id)
            ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $this->id));
    }
}
