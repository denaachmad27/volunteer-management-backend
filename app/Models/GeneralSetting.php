<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GeneralSetting extends Model
{
    protected $fillable = [
        'site_name',
        'site_description',
        'site_url',
        'admin_email',
        'contact_phone',
        'address',
        'organization',
        'logo_path',
        'timezone',
        'language',
        'social_media',
        'additional_settings'
    ];

    protected $casts = [
        'social_media' => 'array',
        'additional_settings' => 'array',
    ];

    /**
     * Get the singleton instance of general settings
     * Create default settings if none exist
     */
    public static function getSettings()
    {
        $settings = self::first();
        
        if (!$settings) {
            $settings = self::create([
                'site_name' => 'Admin Panel Bantuan Sosial',
                'site_description' => 'Sistem administrasi bantuan sosial untuk pengelolaan program bantuan masyarakat',
                'site_url' => 'https://bantuan-sosial.gov.id',
                'admin_email' => 'admin@bantuan-sosial.gov.id',
                'contact_phone' => '+62 21 1234 5678',
                'address' => 'Jl. Raya Bantuan Sosial No. 123, Jakarta Pusat',
                'organization' => 'Dinas Sosial DKI Jakarta',
                'timezone' => 'Asia/Jakarta',
                'language' => 'id',
            ]);
        }

        return $settings;
    }

    /**
     * Update the general settings (singleton pattern)
     */
    public static function updateSettings($data)
    {
        $settings = self::getSettings();
        $settings->update($data);
        return $settings;
    }

    /**
     * Get the logo URL
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo_path) {
            // Check if file exists
            if (Storage::disk('public')->exists($this->logo_path)) {
                $url = Storage::disk('public')->url($this->logo_path);
                \Log::info('Logo URL generated', [
                    'logo_path' => $this->logo_path,
                    'generated_url' => $url,
                    'file_exists' => Storage::disk('public')->exists($this->logo_path)
                ]);
                return $url;
            } else {
                \Log::warning('Logo file not found', [
                    'logo_path' => $this->logo_path,
                    'public_path' => Storage::disk('public')->path($this->logo_path)
                ]);
            }
        }
        return null;
    }

    /**
     * Delete old logo when updating
     */
    public function updateLogo($logoFile)
    {
        // Delete old logo if exists
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            Storage::disk('public')->delete($this->logo_path);
        }

        // Store new logo
        $logoPath = $logoFile->store('logos', 'public');
        $this->update(['logo_path' => $logoPath]);

        return $logoPath;
    }

    /**
     * Get formatted settings for API response
     */
    public function getFormattedSettings()
    {
        return [
            'id' => $this->id,
            'site_name' => $this->site_name,
            'site_description' => $this->site_description,
            'site_url' => $this->site_url,
            'admin_email' => $this->admin_email,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address,
            'organization' => $this->organization,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_url,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'social_media' => $this->social_media,
            'additional_settings' => $this->additional_settings,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get available timezones
     */
    public static function getAvailableTimezones()
    {
        return [
            'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
            'Asia/Makassar' => 'Asia/Makassar (WITA)',
            'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
        ];
    }

    /**
     * Get available languages
     */
    public static function getAvailableLanguages()
    {
        return [
            'id' => 'Bahasa Indonesia',
            'en' => 'English',
        ];
    }
}