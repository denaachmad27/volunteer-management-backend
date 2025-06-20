<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama_anggota',
        'hubungan',
        'jenis_kelamin',
        'tanggal_lahir',
        'pekerjaan',
        'pendidikan',
        'penghasilan',
        'tanggungan',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'penghasilan' => 'decimal:2',
        'tanggungan' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getAgeAttribute()
    {
        return $this->tanggal_lahir->age;
    }
}