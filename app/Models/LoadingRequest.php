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

    protected $fillable = [
        'trip_request_id', 'driver_id', 'status', 'notes',
    ];

    public function tripRequest()
    {
        return $this->belongsTo(TripRequest::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
