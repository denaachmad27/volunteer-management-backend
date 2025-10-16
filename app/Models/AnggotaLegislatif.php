<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaLegislatif extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_aleg',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'kelurahan',
        'kecamatan',
        'kota',
        'provinsi',
        'kode_pos',
        'no_telepon',
        'email',
        'jabatan_saat_ini',
        'partai_politik',
        'daerah_pemilihan',
        'riwayat_jabatan',
        'foto_profil',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'status' => 'string',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class, 'anggota_legislatif_id');
    }

    public function volunteers()
    {
        // Support both legacy 'user' and new 'relawan' roles
        return $this->hasMany(User::class, 'anggota_legislatif_id')
                    ->whereIn('role', ['user', 'relawan']);
    }

    public function voters()
    {
        return $this->hasMany(User::class, 'anggota_legislatif_id')
                    ->where('role', 'warga');
    }

    // Helper methods
    public function getFullAddressAttribute()
    {
        return $this->alamat . ', ' . $this->kelurahan . ', ' . $this->kecamatan . ', ' . $this->kota . ', ' . $this->provinsi . ' ' . $this->kode_pos;
    }

    public function getVolunteerCountAttribute()
    {
        return $this->volunteers()->count();
    }

    public function isActive()
    {
        return $this->status === 'Aktif';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'Tidak Aktif');
    }
}
