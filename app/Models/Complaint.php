<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'anggota_legislatif_id',
        'no_tiket',
        'judul',
        'kategori',
        'deskripsi',
        'image_path',
        'prioritas',
        'status',
        'respon_admin',
        'tanggal_respon',
        'rating',
        'feedback',
    ];

    protected $casts = [
        'tanggal_respon' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function anggotaLegislatif()
    {
        return $this->belongsTo(AnggotaLegislatif::class);
    }

    // Scopes
    public function scopeByAnggotaLegislatif($query, $anggotaLegislatifId)
    {
        return $query->where('anggota_legislatif_id', $anggotaLegislatifId);
    }

    // Auto generate no tiket
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($complaint) {
            if (empty($complaint->no_tiket)) {
                $complaint->no_tiket = $complaint->generateUniqueNoTiket();
            }
        });
    }

    // Helper methods
    public function generateUniqueNoTiket()
    {
        $maxRetries = 10;
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            $noTiket = $this->generateNoTiket();
            
            // Check if this number already exists
            $exists = static::where('no_tiket', $noTiket)->exists();
            
            if (!$exists) {
                return $noTiket;
            }
            
            $retryCount++;
            // Add small delay to prevent tight loop
            usleep(1000); // 1ms delay
        }
        
        // Fallback: use timestamp if all else fails
        return 'TKT-' . date('Ymd') . '-' . time();
    }
    
    public function generateNoTiket()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = 'TKT-' . $year . $month . '-';
        
        // Get the last ticket number for current year-month
        $lastTicket = static::where('no_tiket', 'like', $prefix . '%')
                           ->orderBy('no_tiket', 'desc')
                           ->first();
        
        if ($lastTicket) {
            // Extract number from last ticket (e.g., "TKT-202507-0005" -> "0005")
            $lastNumber = (int) substr($lastTicket->no_tiket, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'Baru' => 'red',
            'Diproses' => 'yellow',
            'Selesai' => 'green',
            'Ditutup' => 'gray',
            default => 'gray'
        };
    }

    public function getPrioritasColorAttribute()
    {
        return match($this->prioritas) {
            'Rendah' => 'green',
            'Sedang' => 'yellow',
            'Tinggi' => 'orange',
            'Urgent' => 'red',
            default => 'gray'
        };
    }

    public function isOpenAttribute()
    {
        return in_array($this->status, ['Baru', 'Diproses']);
    }

    public function isClosedAttribute()
    {
        return in_array($this->status, ['Selesai', 'Ditutup']);
    }
}