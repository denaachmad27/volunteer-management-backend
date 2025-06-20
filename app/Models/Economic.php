<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Economic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'penghasilan_bulanan',
        'pengeluaran_bulanan',
        'status_rumah',
        'jenis_rumah',
        'punya_kendaraan',
        'jenis_kendaraan',
        'punya_tabungan',
        'jumlah_tabungan',
        'punya_hutang',
        'jumlah_hutang',
        'sumber_penghasilan_lain',
    ];

    protected $casts = [
        'penghasilan_bulanan' => 'decimal:2',
        'pengeluaran_bulanan' => 'decimal:2',
        'jumlah_tabungan' => 'decimal:2',
        'jumlah_hutang' => 'decimal:2',
        'punya_kendaraan' => 'boolean',
        'punya_tabungan' => 'boolean',
        'punya_hutang' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getSisaPenghasilanAttribute()
    {
        return $this->penghasilan_bulanan - $this->pengeluaran_bulanan;
    }

    public function getStatusEkonomiAttribute()
    {
        $sisa = $this->getSisaPenghasilanAttribute();
        if ($sisa > 0) return 'Surplus';
        if ($sisa == 0) return 'Seimbang';
        return 'Defisit';
    }
}