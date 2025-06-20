<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'slug',
        'konten',
        'gambar_utama',
        'kategori',
        'is_published',
        'published_at',
        'views',
        'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Auto generate slug
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = Str::slug($news->judul);
            }
        });

        static::updating(function ($news) {
            if ($news->isDirty('judul') && empty($news->slug)) {
                $news->slug = Str::slug($news->judul);
            }
        });
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('kategori', $category);
    }

    // Helper methods
    public function incrementViews()
    {
        $this->increment('views');
    }

    public function getExcerptAttribute($length = 150)
    {
        return Str::limit(strip_tags($this->konten), $length);
    }

    public function getReadingTimeAttribute()
    {
        $words = str_word_count(strip_tags($this->konten));
        return ceil($words / 200) . ' menit baca';
    }
}