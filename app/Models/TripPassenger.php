<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripPassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_request_id', 'name', 'phone', 'department', 'nik', 'position',
    ];

    public function tripRequest()
    {
        return $this->belongsTo(TripRequest::class);
    }
}
