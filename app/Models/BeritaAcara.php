<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeritaAcara extends Model
{
    use HasFactory;

    protected $fillable = [
        'ba_number', 'trip_request_id', 'driver_id', 'vehicle_id',
        'requestor_name', 'department_name', 'destination', 'purpose',
        'departure_time', 'return_time', 'notes', 'signed_by',
        'signed_position', 'signed_at', 'source_type', 'source_id',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'return_time' => 'datetime',
        'signed_at' => 'datetime',
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
}
