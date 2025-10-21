<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reses extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'judul',
        'deskripsi',
        'lokasi',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'foto_kegiatan',
        'anggota_legislatif_id',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Get the anggota legislatif that owns the reses.
     */
    public function anggotaLegislatif()
    {
        return $this->belongsTo(AnggotaLegislatif::class, 'anggota_legislatif_id');
    }

    /**
     * Get the user who created the reses.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include reses with specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by anggota legislatif.
     */
    public function scopeByAnggotaLegislatif($query, $anggotaLegislatifId)
    {
        return $query->where('anggota_legislatif_id', $anggotaLegislatifId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_mulai', [$startDate, $endDate]);
    }
}
