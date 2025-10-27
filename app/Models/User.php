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
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
        'anggota_legislatif_id',
        'google_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function families()
    {
        return $this->hasMany(Family::class);
    }

    public function economic()
    {
        return $this->hasOne(Economic::class);
    }

    public function social()
    {
        return $this->hasOne(Social::class);
    }

    public function pendaftarans()
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function createdNews()
    {
        return $this->hasMany(News::class, 'created_by');
    }

    public function anggotaLegislatif()
    {
        return $this->belongsTo(AnggotaLegislatif::class);
    }

    // New relationships for relawan-warga hierarchy
    // For Relawan: list of warga under this relawan
    public function warga()
    {
        return $this->hasMany(User::class, 'relawan_id')->where('role', 'warga');
    }

    // For Warga: the relawan who maintains this warga
    public function relawan()
    {
        return $this->belongsTo(User::class, 'relawan_id');
    }

    // Relationship with WargaBinaan
    public function wargaBinaan()
    {
        return $this->hasMany(WargaBinaan::class, 'relawan_id');
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isAdminAleg()
    {
        return $this->role === 'admin_aleg';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function isSuperAdmin()
    {
        return $this->role === 'admin';
    }

    public function canManageContent()
    {
        return in_array($this->role, ['admin', 'admin_aleg']);
    }

    // New helper roles
    public function isRelawan()
    {
        // Backward-compatible: treat existing 'user' as relawan
        return in_array($this->role, ['relawan', 'user']);
    }

    public function isWarga()
    {
        return $this->role === 'warga';
    }

    public function isAleg()
    {
        return $this->role === 'aleg';
    }
}
