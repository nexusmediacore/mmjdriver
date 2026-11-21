<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'from_status', 'to_status', 'reason',
        'ip_address', 'user_agent',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
