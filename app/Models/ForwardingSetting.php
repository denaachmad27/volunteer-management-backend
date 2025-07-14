<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForwardingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_forwarding',
        'whatsapp_forwarding',
        'forwarding_mode',
        'admin_email',
        'admin_whatsapp',
    ];

    protected $casts = [
        'email_forwarding' => 'boolean',
        'whatsapp_forwarding' => 'boolean',
    ];

    /**
     * Get the singleton settings record (there should be only one)
     */
    public static function getSettings()
    {
        return self::first() ?? self::create([
            'email_forwarding' => true,
            'whatsapp_forwarding' => false,
            'forwarding_mode' => 'auto',
            'admin_email' => 'admin@bantuan-sosial.gov.id',
            'admin_whatsapp' => '+62 812 9999 9999',
        ]);
    }

    /**
     * Update settings (singleton pattern)
     */
    public static function updateSettings($data)
    {
        $settings = self::getSettings();
        $settings->update($data);
        return $settings;
    }
}