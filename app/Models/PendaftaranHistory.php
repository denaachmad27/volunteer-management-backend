<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendaftaranHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'pendaftaran_id',
        'status_from',
        'status_to',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper method untuk mendapatkan status label yang user-friendly
    public function getStatusLabel($status)
    {
        return match($status) {
            'Pending' => 'Menunggu Review',
            'Diproses' => 'Sedang Diproses',
            'Disetujui' => 'Disetujui',
            'Ditolak' => 'Ditolak',
            'Selesai' => 'Selesai',
            'Perlu Dilengkapi' => 'Perlu Dilengkapi',
            default => $status
        };
    }

    // Helper method untuk mendapatkan icon status
    public function getStatusIcon($status)
    {
        return match($status) {
            'Pending' => '⏳',
            'Diproses' => '🔄',
            'Disetujui' => '✅',
            'Ditolak' => '❌',
            'Selesai' => '🎉',
            'Perlu Dilengkapi' => '⚠️',
            default => '📋'
        };
    }
}