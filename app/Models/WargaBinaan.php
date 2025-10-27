<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WargaBinaan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'warga_binaan';

    protected $fillable = [
        'relawan_id',
        'no_kta',
        'nama',
        'tanggal_lahir',
        'usia',
        'jenis_kelamin',
        'alamat',
        'kecamatan',
        'kelurahan',
        'rt',
        'rw',
        'no_hp',
        'status_kta',
        'hasil_verifikasi',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'usia' => 'integer',
    ];

    // Relationship dengan relawan
    public function relawan()
    {
        return $this->belongsTo(User::class, 'relawan_id');
    }

    // Accessor untuk format tanggal
    public function getTanggalLahirFormattedAttribute()
    {
        return $this->tanggal_lahir ? $this->tanggal_lahir->format('d/m/Y') : null;
    }

    // Scope untuk filter berdasarkan relawan
    public function scopeByRelawan($query, $relawanId)
    {
        return $query->where('relawan_id', $relawanId);
    }

    // Scope untuk search
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%")
                ->orWhere('no_kta', 'like', "%{$search}%")
                ->orWhere('no_hp', 'like', "%{$search}%")
                ->orWhere('alamat', 'like', "%{$search}%");
        });
    }
}
