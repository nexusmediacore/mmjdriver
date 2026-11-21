<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_request_id', 'approver_id', 'decision', 'notes', 'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function tripRequest()
    {
        return $this->belongsTo(TripRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
