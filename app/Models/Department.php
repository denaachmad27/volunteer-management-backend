<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'categories',
        'is_active',
    ];

    protected $casts = [
        'categories' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get only active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get department by category
     */
    public static function getByCategory($category)
    {
        return self::active()
            ->whereJsonContains('categories', $category)
            ->first();
    }

    /**
     * Get all active departments with their categories
     */
    public static function getActiveWithCategories()
    {
        return self::active()
            ->orderBy('name')
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'email' => $department->email,
                    'whatsapp' => $department->whatsapp,
                    'categories' => $department->categories ?? [],
                    'is_active' => $department->is_active,
                ];
            });
    }

    /**
     * Check if department handles specific category
     */
    public function handlesCategory($category)
    {
        return in_array($category, $this->categories ?? []);
    }
}