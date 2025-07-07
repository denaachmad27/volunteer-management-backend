<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    // Auto generate no tiket
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($complaint) {
            if (empty($complaint->no_tiket)) {
                $complaint->no_tiket = $complaint->generateNoTiket();
            }
        });
    }

    // Helper methods
    public function generateNoTiket()
    {
        $year = date('Y');
        $month = date('m');
        $lastNumber = static::whereYear('created_at', $year)
                           ->whereMonth('created_at', $month)
                           ->count() + 1;
        
        return 'TKT-' . $year . $month . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
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