<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AnggotaLegislatif;
use Illuminate\Support\Facades\Hash;

class AdminAlegSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@volunteer.com'],
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@volunteer.com',
                'password' => Hash::make('SuperAdmin123!'),
                'phone' => '08123456789',
                'role' => 'admin',
                'is_active' => true,
                'anggota_legislatif_id' => null, // Super admin tidak terikat dengan aleg tertentu
            ]
        );

        // Ambil data anggota legislatif
        $anggotaLegislatifs = AnggotaLegislatif::all();

        if ($anggotaLegislatifs->count() < 2) {
            // Jika belum ada anggota legislatif, buat dulu
            $aleg1 = AnggotaLegislatif::updateOrCreate(
                ['nama' => 'Andri Rusmana'],
                [
                    'nama' => 'Andri Rusmana',
                    'partai' => 'Partai Demokrasi Indonesia Perjuangan',
                    'dapil' => 'Dapil I Bandung',
                    'periode' => '2024-2029',
                    'foto' => null,
                    'deskripsi' => 'Anggota Legislatif yang berfokus pada pembangunan infrastruktur dan kesejahteraan masyarakat.',
                    'is_active' => true,
                ]
            );

            $aleg2 = AnggotaLegislatif::updateOrCreate(
                ['nama' => 'Asep Mulyadi'],
                [
                    'nama' => 'Asep Mulyadi',
                    'partai' => 'Partai Golongan Karya',
                    'dapil' => 'Dapil II Bandung',
                    'periode' => '2024-2029',
                    'foto' => null,
                    'deskripsi' => 'Anggota Legislatif yang mengutamakan pendidikan dan ekonomi kerakyatan.',
                    'is_active' => true,
                ]
            );
        } else {
            $aleg1 = $anggotaLegislatifs->first();
            $aleg2 = $anggotaLegislatifs->skip(1)->first() ?: $anggotaLegislatifs->first();
        }

        // Buat Admin untuk Andri Rusmana
        $adminAndri = User::updateOrCreate(
            ['email' => 'admin.andri@volunteer.com'],
            [
                'name' => 'Admin Andri Rusmana',
                'email' => 'admin.andri@volunteer.com',
                'password' => Hash::make('AdminAndri123!'),
                'phone' => '08234567890',
                'role' => 'admin_aleg',
                'is_active' => true,
                'anggota_legislatif_id' => $aleg1->id,
            ]
        );

        // Buat Admin untuk Asep Mulyadi
        $adminAsep = User::updateOrCreate(
            ['email' => 'admin.asep@volunteer.com'],
            [
                'name' => 'Admin Asep Mulyadi',
                'email' => 'admin.asep@volunteer.com',
                'password' => Hash::make('AdminAsep123!'),
                'phone' => '08345678901',
                'role' => 'admin_aleg',
                'is_active' => true,
                'anggota_legislatif_id' => $aleg2->id,
            ]
        );

        $this->command->info('✅ Users berhasil dibuat:');
        $this->command->line('');
        $this->command->info('🔐 SUPER ADMIN:');
        $this->command->line('Email: superadmin@volunteer.com');
        $this->command->line('Password: SuperAdmin123!');
        $this->command->line('Role: Super Administrator (akses penuh)');
        $this->command->line('');
        $this->command->info('👤 ADMIN ALEG - Andri Rusmana:');
        $this->command->line('Email: admin.andri@volunteer.com');
        $this->command->line('Password: AdminAndri123!');
        $this->command->line('Role: Admin Aleg (hanya konten Andri Rusmana)');
        $this->command->line('');
        $this->command->info('👤 ADMIN ALEG - Asep Mulyadi:');
        $this->command->line('Email: admin.asep@volunteer.com');
        $this->command->line('Password: AdminAsep123!');
        $this->command->line('Role: Admin Aleg (hanya konten Asep Mulyadi)');
        $this->command->line('');
        $this->command->warn('⚠️  PENTING: Simpan kredensial ini dengan aman!');
    }
}
