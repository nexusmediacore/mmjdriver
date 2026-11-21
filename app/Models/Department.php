<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    /**
     * Departemen BOD (Board of Directors).
     * Trip dari departemen ini otomatis disetujui tanpa alur approval.
     */
    public const CODE_BOD = 'BOD';

    protected $fillable = ['code', 'name', 'head_name', 'notes'];

    /**
     * ID departemen BOD, atau null bila belum dibuat.
     */
    public static function bodId(): ?int
    {
        $bod = static::query()->where('code', self::CODE_BOD)->first();

        return $bod?->id;
    }

    /**
     * Cek apakah sebuah department_id merupakan departemen BOD.
     */
    public static function isBod(?int $departmentId): bool
    {
        return $departmentId !== null && $departmentId === self::bodId();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function tripRequests()
    {
        return $this->hasMany(TripRequest::class, 'department_id');
    }
}
