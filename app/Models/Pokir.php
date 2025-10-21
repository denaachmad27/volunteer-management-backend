<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pokir extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'judul',
        'deskripsi',
        'kategori',
        'prioritas',
        'status',
        'lokasi_pelaksanaan',
        'target_pelaksanaan',
        'anggota_legislatif_id',
        'created_by',
    ];

    protected $casts = [
        'target_pelaksanaan' => 'date',
    ];

    /**
     * Get the anggota legislatif that owns the pokir.
     */
    public function anggotaLegislatif()
    {
        return $this->belongsTo(AnggotaLegislatif::class, 'anggota_legislatif_id');
    }

    /**
     * Get the user who created the pokir.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include pokir with specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pokir with specific category.
     */
    public function scopeKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    /**
     * Scope a query to only include pokir with specific priority.
     */
    public function scopePrioritas($query, $prioritas)
    {
        return $query->where('prioritas', $prioritas);
    }

    /**
     * Scope a query to filter by anggota legislatif.
     */
    public function scopeByAnggotaLegislatif($query, $anggotaLegislatifId)
    {
        return $query->where('anggota_legislatif_id', $anggotaLegislatifId);
    }
}
