<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripRequest extends Model
{
    use HasFactory, SoftDeletes;

    const PRIORITY_NORMAL = 'NORMAL';

    const PRIORITY_URGENT = 'URGENT';

    const PRIORITY_VERY_URGENT = 'VERY_URGENT';

    const TYPE_BUSINESS_TRIP = 'BUSINESS_TRIP';

    const TYPE_GUEST_PICKUP = 'GUEST_PICKUP';

    /** Request muat barang (pengambilan / pemuatan barang). */
    const TYPE_CARGO_PICKUP = 'CARGO_PICKUP';

    const STATUS_DRAFT = 'DRAFT';

    const STATUS_SUBMITTED = 'SUBMITTED';

    const STATUS_WAITING_APPROVAL = 'WAITING_APPROVAL';

    const STATUS_APPROVED = 'APPROVED';

    const STATUS_REJECTED = 'REJECTED';

    const STATUS_WAITING_DRIVER = 'WAITING_DRIVER';

    const STATUS_DRIVER_ASSIGNED = 'DRIVER_ASSIGNED';

    const STATUS_DRIVER_ACCEPTED = 'DRIVER_ACCEPTED';

    const STATUS_DRIVER_REJECTED = 'DRIVER_REJECTED';

    const STATUS_READY_TO_DEPART = 'READY_TO_DEPART';

    const STATUS_ON_TRIP = 'ON_TRIP';

    /**
     * Pengajuan selesai perjalanan sudah dibuat (driver / pemohon) dan
     * menunggu 1x approval Admin / SuperAdmin sebelum menjadi COMPLETED.
     */
    const STATUS_COMPLETION_SUBMITTED = 'COMPLETION_SUBMITTED';

    const STATUS_COMPLETED = 'COMPLETED';

    const STATUS_CANCELLED = 'CANCELLED';

    /**
     * Status trip yang dianggap masih berjalan (belum selesai / batal / ditolak).
     *
     * @return array<int, string>
     */
    public static function activeStatuses(): array
    {
        return [
            self::STATUS_WAITING_DRIVER,
            self::STATUS_DRIVER_ASSIGNED,
            self::STATUS_DRIVER_ACCEPTED,
            self::STATUS_READY_TO_DEPART,
            self::STATUS_ON_TRIP,
            self::STATUS_COMPLETION_SUBMITTED,
        ];
    }

    /**
     * Status trip yang menahan driver (driver tidak boleh ditugaskan lain).
     *
     * @return array<int, string>
     */
    public static function driverBlockingStatuses(): array
    {
        return [
            self::STATUS_DRIVER_ASSIGNED,
            self::STATUS_DRIVER_ACCEPTED,
            self::STATUS_READY_TO_DEPART,
            self::STATUS_ON_TRIP,
            self::STATUS_COMPLETION_SUBMITTED,
        ];
    }

    protected $fillable = [
        'trip_number', 'requestor_id', 'department_id', 'request_type', 'trip_date',
        'departure_time', 'estimated_return_time', 'destination',
        'destination_address', 'guest_pickup_location', 'guest_institution',
        'cargo_pickup_location', 'cargo_description', 'purpose',
        'passenger_count', 'priority', 'requested_priority', 'urgency_notes',
        'notes', 'requires_loading_assistance', 'status', 'bod_link',
        'approved_by', 'priority_set_by', 'approval_notes', 'approved_at', 'rejection_reason',
        'created_by', 'updated_by', 'assigned_driver_id', 'assigned_vehicle_id',
        'started_at', 'started_by', 'completed_at', 'completed_by',
        'completion_notes', 'actual_return_time',
        'completion_submitted_at', 'completion_submitted_by',
        'completion_approved_at', 'completion_approved_by',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'departure_time' => 'datetime',
        'estimated_return_time' => 'datetime',
        'requires_loading_assistance' => 'boolean',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'actual_return_time' => 'datetime',
        'completion_submitted_at' => 'datetime',
        'completion_approved_at' => 'datetime',
    ];

    public function requestor()
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }

    public function passengers()
    {
        return $this->hasMany(TripPassenger::class);
    }

    public function approvals()
    {
        return $this->hasMany(TripApproval::class);
    }

    /**
     * Assignment driver UTAMA (role MAIN) — pembawa kendaraan.
     */
    public function assignment()
    {
        return $this->hasOne(TripAssignment::class)->where('role', TripAssignment::ROLE_MAIN);
    }

    /**
     * Seluruh assignment driver pada trip ini (utama + pendukung loading).
     */
    public function assignments()
    {
        return $this->hasMany(TripAssignment::class);
    }

    /**
     * Driver pendukung loading (multi-driver assignment).
     */
    public function loadingSupportAssignments()
    {
        return $this->hasMany(TripAssignment::class)->where('role', TripAssignment::ROLE_LOADING_SUPPORT);
    }

    public function completionSubmittedBy()
    {
        return $this->belongsTo(User::class, 'completion_submitted_by');
    }

    public function completionApprovedBy()
    {
        return $this->belongsTo(User::class, 'completion_approved_by');
    }

    /**
     * Apakah user tertentu termasuk driver yang ditugaskan pada trip ini
     * (driver utama maupun pendukung loading).
     */
    public function hasDriver(User $user): bool
    {
        $driverId = $user->driver?->id;

        if (! $driverId) {
            return false;
        }

        if ((int) $this->assigned_driver_id === (int) $driverId) {
            return true;
        }

        return $this->relationLoaded('assignments')
            ? $this->assignments->contains('driver_id', $driverId)
            : $this->assignments()->where('driver_id', $driverId)->exists();
    }

    /**
     * Apakah trip ini berasal dari departemen BOD.
     */
    public function isBodTrip(): bool
    {
        return Department::isBod($this->department_id !== null ? (int) $this->department_id : null);
    }

    /**
     * Apakah link referensi BOD aman ditampilkan (hanya http/https).
     */
    public function getBodLinkAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $value : null;
    }

    /**
     * Apakah trip memiliki driver pendukung loading (multi-driver assignment).
     */
    public function hasLoadingSupportDrivers(): bool
    {
        return $this->relationLoaded('assignments')
            ? $this->assignments->contains('role', TripAssignment::ROLE_LOADING_SUPPORT)
            : $this->loadingSupportAssignments()->exists();
    }

    public function statusLogs()
    {
        return $this->hasMany(TripStatusLog::class);
    }

    public function loadingRequest()
    {
        return $this->hasOne(LoadingRequest::class);
    }

    public function loadingRequests()
    {
        return $this->hasMany(LoadingRequest::class);
    }

    public function beritaAcara()
    {
        return $this->hasOne(BeritaAcara::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
