<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id', 'from_status', 'to_status', 'reason',
        'ip_address', 'user_agent',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
