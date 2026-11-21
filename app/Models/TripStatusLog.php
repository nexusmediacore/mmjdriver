<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_request_id', 'from_status', 'to_status', 'changed_by',
        'notes', 'ip_address', 'user_agent',
    ];

    public function tripRequest()
    {
        return $this->belongsTo(TripRequest::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
