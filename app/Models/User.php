<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'department_id',
        'phone', 'avatar', 'gender', 'birth_date', 'nik', 'npwp',
        'is_active', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    /**
     * Trip yang dibuat oleh user ini (sebagai requestor).
     */
    public function tripRequests()
    {
        return $this->hasMany(TripRequest::class, 'requestor_id');
    }

    /**
     * Approval trip yang dilakukan oleh user ini.
     */
    public function approvals()
    {
        return $this->hasMany(TripApproval::class, 'approver_id');
    }

    public function hasRole($role)
    {
        return $this->role && $this->role->name === $role;
    }

    public function hasAnyRole($roles)
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->role && in_array($this->role->name, $roles);
    }

    public function hasPermission($permission)
    {
        return $this->role && $this->role->hasPermission($permission);
    }

    /**
     * Nomor WhatsApp untuk notifikasi.
     * Utamakan nomor pada akun, bila kosong pakai nomor driver.
     */
    public function routeNotificationForWhatsApp(): ?string
    {
        return $this->phone ?: $this->driver?->phone;
    }
}
