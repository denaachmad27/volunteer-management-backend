<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_name',
        'session_data',
        'is_active',
        'is_connected',
        'qr_code',
        'webhook_urls',
        'default_message_template',
        'department_mappings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_connected' => 'boolean',
        'webhook_urls' => 'array',
        'department_mappings' => 'array',
    ];

    /**
     * Get the default WhatsApp settings (single row)
     */
    public static function getSettings()
    {
        return self::first() ?: self::create([
            'session_name' => 'admin-session',
            'default_message_template' => "Halo, kami telah menerima pengaduan Anda:\n\n*Tiket:* {ticket_number}\n*Judul:* {title}\n*Kategori:* {category}\n\nTerima kasih atas laporan Anda. Tim kami akan segera menindaklanjuti.",
            'department_mappings' => [
                'Teknis' => [
                    'department_name' => 'Dinas Teknis',
                    'phone_number' => '',
                    'contact_person' => ''
                ],
                'Pelayanan' => [
                    'department_name' => 'Dinas Pelayanan Publik',
                    'phone_number' => '',
                    'contact_person' => ''
                ],
                'Bantuan' => [
                    'department_name' => 'Dinas Sosial',
                    'phone_number' => '',
                    'contact_person' => ''
                ],
                'Saran' => [
                    'department_name' => 'Sekretariat Daerah',
                    'phone_number' => '',
                    'contact_person' => ''
                ],
                'Lainnya' => [
                    'department_name' => 'Humas & Protokol',
                    'phone_number' => '',
                    'contact_person' => ''
                ]
            ]
        ]);
    }

    /**
     * Get department mapping for specific category
     */
    public function getDepartmentForCategory($category)
    {
        $mappings = $this->department_mappings ?? [];
        return $mappings[$category] ?? null;
    }

    /**
     * Update department mapping
     */
    public function updateDepartmentMapping($category, $departmentData)
    {
        $mappings = $this->department_mappings ?? [];
        $mappings[$category] = $departmentData;
        $this->update(['department_mappings' => $mappings]);
    }
}