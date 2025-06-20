<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nik',
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
        'agama',
        'status_pernikahan',
        'pendidikan_terakhir',
        'pekerjaan',
        'foto_profil',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getAgeAttribute()
    {
        return $this->tanggal_lahir->age;
    }

    public function getFullAddressAttribute()
    {
        return $this->alamat . ', ' . $this->kelurahan . ', ' . $this->kecamatan . ', ' . $this->kota . ', ' . $this->provinsi . ' ' . $this->kode_pos;
    }
}