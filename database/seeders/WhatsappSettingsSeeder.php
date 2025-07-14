<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WhatsappSetting;

class WhatsappSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        WhatsappSetting::updateOrCreate(
            ['id' => 1],
            [
                'session_name' => 'admin-session',
                'is_active' => true,
                'default_message_template' => 'Pengaduan baru telah diterima dan akan segera diproses.',
                'department_mappings' => [
                    'Teknis' => [
                        'department_name' => 'Dinas Teknis',
                        'phone_number' => '6281234567890',
                        'contact_person' => 'Budi Teknis'
                    ],
                    'Pelayanan' => [
                        'department_name' => 'Dinas Pelayanan',
                        'phone_number' => '6281234567891', 
                        'contact_person' => 'Siti Pelayanan'
                    ],
                    'Bantuan' => [
                        'department_name' => 'Dinas Bantuan Sosial',
                        'phone_number' => '6281234567892',
                        'contact_person' => 'Andi Bantuan'
                    ],
                    'Saran' => [
                        'department_name' => 'Bagian Saran',
                        'phone_number' => '6281234567893',
                        'contact_person' => 'Joko Saran'
                    ],
                    'Lainnya' => [
                        'department_name' => 'Bagian Umum',
                        'phone_number' => '6281234567894',
                        'contact_person' => 'Rini Umum'
                    ]
                ]
            ]
        );
    }
}