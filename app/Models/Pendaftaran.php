<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pendaftaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bantuan_sosial_id',
        'no_pendaftaran',
        'tanggal_daftar',
        'status',
        'alasan_pengajuan',
        'dokumen_upload',
        'catatan_admin',
        'tanggal_persetujuan',
        'tanggal_penyerahan',
    ];

    protected $casts = [
        'tanggal_daftar' => 'date',
        'tanggal_persetujuan' => 'date',
        'tanggal_penyerahan' => 'date',
        'dokumen_upload' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bantuanSosial()
    {
        return $this->belongsTo(BantuanSosial::class);
    }

    // Helper methods
    public function generateNoPendaftaran()
    {
        $year = date('Y');
        $month = date('m');
        $lastNumber = static::whereYear('created_at', $year)
                           ->whereMonth('created_at', $month)
                           ->count() + 1;
        
        return 'REG-' . $year . $month . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
    }

    public function isPendingAttribute()
    {
        return $this->status === 'Pending';
    }

    public function isApprovedAttribute()
    {
        return $this->status === 'Disetujui';
    }

    public function isRejectedAttribute()
    {
        return $this->status === 'Ditolak';
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'Pending' => 'yellow',
            'Diproses' => 'blue',
            'Disetujui' => 'green',
            'Ditolak' => 'red',
            'Selesai' => 'gray',
            default => 'gray'
        };
    }
}