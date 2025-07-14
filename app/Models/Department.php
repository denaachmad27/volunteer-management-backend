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
        'is_active'
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
     * Get formatted WhatsApp number
     */
    public function getFormattedWhatsappAttribute()
    {
        $number = preg_replace('/\D/', '', $this->whatsapp);
        
        if (strpos($number, '62') === 0) {
            return $number;
        }
        
        if (strpos($number, '0') === 0) {
            return '62' . substr($number, 1);
        }
        
        return '62' . $number;
    }

    /**
     * Get categories as string
     */
    public function getCategoriesStringAttribute()
    {
        return is_array($this->categories) 
            ? implode(', ', $this->categories) 
            : '';
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
                    'address' => $department->address,
                    'email' => $department->email,
                    'whatsapp' => $department->whatsapp,
                    'formatted_whatsapp' => $department->formatted_whatsapp,
                    'contact_person' => $department->contact_person,
                    'categories' => $department->categories ?? [],
                    'categories_string' => $department->categories_string,
                    'is_active' => $department->is_active,
                    'description' => $department->description,
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