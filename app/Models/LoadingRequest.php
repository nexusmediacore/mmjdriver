<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoadingRequest extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'PENDING';

    const STATUS_ACCEPTED = 'ACCEPTED';

    const STATUS_COMPLETED = 'COMPLETED';

    const STATUS_CANCELLED = 'CANCELLED';

    const APPROVAL_PENDING = 'PENDING';

    const APPROVAL_APPROVED = 'APPROVED';

    const APPROVAL_REJECTED = 'REJECTED';

    protected $fillable = [
        'trip_request_id', 'driver_id', 'status', 'notes',
        'approval_status', 'approved_by', 'approved_at', 'approval_note',
    ];

    public function tripRequest()
    {
        return $this->belongsTo(TripRequest::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
