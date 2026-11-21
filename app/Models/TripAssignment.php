<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripAssignment extends Model
{
    use HasFactory;

    const RESPONSE_PENDING = 'PENDING';

    const RESPONSE_ACCEPTED = 'ACCEPTED';

    const RESPONSE_REJECTED = 'REJECTED';

    /** Driver utama — membawa kendaraan trip. */
    const ROLE_MAIN = 'MAIN';

    /** Driver pendukung — membantu loading barang (Purchasing). */
    const ROLE_LOADING_SUPPORT = 'LOADING_SUPPORT';

    protected $fillable = [
        'trip_request_id', 'driver_id', 'vehicle_id', 'role', 'assigned_by',
        'assigned_at', 'driver_accepted_at', 'driver_rejection_reason', 'driver_response',
    ];

    public function isMain(): bool
    {
        return $this->role === self::ROLE_MAIN;
    }

    protected $casts = [
        'assigned_at' => 'datetime',
        'driver_accepted_at' => 'datetime',
    ];

    public function tripRequest()
    {
        return $this->belongsTo(TripRequest::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
