<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BantuanSosial extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_bantuan',
        'deskripsi',
        'jenis_bantuan',
        'nominal',
        'kuota',
        'kuota_terpakai',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'syarat_bantuan',
        'dokumen_diperlukan',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    // Relationships
    public function pendaftarans()
    {
        return $this->hasMany(Pendaftaran::class);
    }

    // Helper methods
    public function getKuotaSisaAttribute()
    {
        return $this->kuota - $this->kuota_terpakai;
    }

    public function isTersediaAttribute()
    {
        return $this->status === 'Aktif' && 
               $this->getKuotaSisaAttribute() > 0 && 
               now()->between($this->tanggal_mulai, $this->tanggal_selesai);
    }

    public function getPersentaseKuotaAttribute()
    {
        if ($this->kuota == 0) return 0;
        return ($this->kuota_terpakai / $this->kuota) * 100;
    }
}