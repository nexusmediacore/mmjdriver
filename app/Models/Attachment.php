<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_type', 'attachable_id', 'file_name', 'path',
        'mime_type', 'file_size', 'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function attachable()
    {
        return $this->morphTo();
    }
}
