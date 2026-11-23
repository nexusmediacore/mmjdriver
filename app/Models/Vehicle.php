<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number', 'brand', 'model', 'type', 'year',
        'passenger_capacity', 'status', 'primary_driver_id', 'notes',
        'last_service_date', 'next_service_date',
    ];

    protected $casts = [
        'year' => 'integer',
        'passenger_capacity' => 'integer',
        'last_service_date' => 'date',
        'next_service_date' => 'date',
    ];

    public function primaryDriver()
    {
        return $this->belongsTo(Driver::class, 'primary_driver_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(VehicleStatusLog::class);
    }

    public function tripRequests()
    {
        return $this->hasMany(TripRequest::class, 'assigned_vehicle_id');
    }

    public function authorizedDrivers()
    {
        return $this->belongsToMany(Driver::class, 'driver_vehicle');
    }

    public function tripAssignments()
    {
        return $this->hasMany(TripAssignment::class);
    }
}
