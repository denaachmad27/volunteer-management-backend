<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Social extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organisasi',
        'jabatan_organisasi',
        'aktif_kegiatan_sosial',
        'jenis_kegiatan_sosial',
        'pernah_dapat_bantuan',
        'jenis_bantuan_diterima',
        'tanggal_bantuan_terakhir',
        'keahlian_khusus',
        'minat_kegiatan',
        'ketersediaan_waktu',
    ];

    protected $casts = [
        'aktif_kegiatan_sosial' => 'boolean',
        'pernah_dapat_bantuan' => 'boolean',
        'tanggal_bantuan_terakhir' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}