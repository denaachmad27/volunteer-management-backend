<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ExportVolunteers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:volunteers {--format=csv : Export format (csv|json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export volunteer data with personal, family, economic, and social information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');
        $timestamp = date('Y-m-d_H-i-s');
        
        $this->info('🔄 Mengekspor data relawan...');
        
        // Get volunteer data
        $volunteers = User::where('role', 'user')
            ->with(['profile', 'families', 'economic', 'social', 'anggotaLegislatif'])
            ->get();
        
        if ($format === 'csv') {
            $this->exportToCsv($volunteers, $timestamp);
        } else {
            $this->exportToJson($volunteers, $timestamp);
        }
        
        $this->info('✅ Export berhasil!');
        $this->info("📊 Total relawan: {$volunteers->count()}");
    }
    
    private function exportToCsv($volunteers, $timestamp)
    {
        $filename = "Data_Relawan_Lengkap_{$timestamp}.csv";
        $file = fopen($filename, 'w');
        
        // CSV Headers
        $headers = [
            'ID', 'Nama', 'Email', 'Telepon', 'Status', 'Tanggal Daftar',
            'NIK', 'Nama Lengkap', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir',
            'Alamat', 'Kelurahan', 'Kecamatan', 'Kota', 'Provinsi', 'Kode Pos',
            'Agama', 'Status Pernikahan', 'Pendidikan', 'Pekerjaan',
            'Penghasilan Bulanan', 'Pengeluaran Bulanan', 'Status Rumah', 'Jenis Rumah',
            'Punya Kendaraan', 'Jenis Kendaraan', 'Punya Tabungan', 'Jumlah Tabungan',
            'Punya Hutang', 'Jumlah Hutang', 'Sumber Penghasilan Lain',
            'Organisasi', 'Jabatan Organisasi', 'Aktif Kegiatan Sosial', 'Jenis Kegiatan Sosial',
            'Pernah Dapat Bantuan', 'Jenis Bantuan Diterima', 'Tanggal Bantuan Terakhir',
            'Keahlian Khusus', 'Minat Kegiatan', 'Ketersediaan Waktu',
            'Anggota Legislatif', 'Partai Politik', 'Jumlah Anggota Keluarga'
        ];
        
        // Write BOM for UTF-8
        fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, $headers);
        
        foreach ($volunteers as $volunteer) {
            $profile = $volunteer->profile;
            $economic = $volunteer->economic;
            $social = $volunteer->social;
            $legislativeMember = $volunteer->anggotaLegislatif;
            $familyCount = $volunteer->families->count();
            
            $row = [
                $volunteer->id,
                $volunteer->name,
                $volunteer->email,
                $volunteer->phone,
                $volunteer->is_active ? 'Aktif' : 'Tidak Aktif',
                $volunteer->created_at ? $volunteer->created_at->format('d/m/Y') : '',
                
                // Profile
                $profile ? $profile->nik : '',
                $profile ? $profile->nama_lengkap : '',
                $profile ? $profile->jenis_kelamin : '',
                $profile ? $profile->tempat_lahir : '',
                $profile ? ($profile->tanggal_lahir ? date('d/m/Y', strtotime($profile->tanggal_lahir)) : '') : '',
                $profile ? $profile->alamat : '',
                $profile ? $profile->kelurahan : '',
                $profile ? $profile->kecamatan : '',
                $profile ? $profile->kota : '',
                $profile ? $profile->provinsi : '',
                $profile ? $profile->kode_pos : '',
                $profile ? $profile->agama : '',
                $profile ? $profile->status_pernikahan : '',
                $profile ? $profile->pendidikan_terakhir : '',
                $profile ? $profile->pekerjaan : '',
                
                // Economic
                $economic ? $economic->penghasilan_bulanan : '',
                $economic ? $economic->pengeluaran_bulanan : '',
                $economic ? $economic->status_rumah : '',
                $economic ? $economic->jenis_rumah : '',
                $economic ? ($economic->punya_kendaraan ? 'Ya' : 'Tidak') : '',
                $economic ? $economic->jenis_kendaraan : '',
                $economic ? ($economic->punya_tabungan ? 'Ya' : 'Tidak') : '',
                $economic ? $economic->jumlah_tabungan : '',
                $economic ? ($economic->punya_hutang ? 'Ya' : 'Tidak') : '',
                $economic ? $economic->jumlah_hutang : '',
                $economic ? $economic->sumber_penghasilan_lain : '',
                
                // Social
                $social ? $social->organisasi : '',
                $social ? $social->jabatan_organisasi : '',
                $social ? ($social->aktif_kegiatan_sosial ? 'Ya' : 'Tidak') : '',
                $social ? $social->jenis_kegiatan_sosial : '',
                $social ? ($social->pernah_dapat_bantuan ? 'Ya' : 'Tidak') : '',
                $social ? $social->jenis_bantuan_diterima : '',
                $social ? ($social->tanggal_bantuan_terakhir ? date('d/m/Y', strtotime($social->tanggal_bantuan_terakhir)) : '') : '',
                $social ? $social->keahlian_khusus : '',
                $social ? $social->minat_kegiatan : '',
                $social ? $social->ketersediaan_waktu : '',
                
                // Legislative Member
                $legislativeMember ? $legislativeMember->nama_lengkap : '',
                $legislativeMember ? $legislativeMember->partai_politik : '',
                $familyCount
            ];
            
            fputcsv($file, $row);
        }
        
        fclose($file);
        $this->info("📁 File CSV dibuat: " . realpath($filename));
        
        // Create family details CSV
        $this->createFamilyCsv($volunteers, $timestamp);
    }
    
    private function createFamilyCsv($volunteers, $timestamp)
    {
        $filename = "Detail_Keluarga_Relawan_{$timestamp}.csv";
        $file = fopen($filename, 'w');
        
        $headers = [
            'User ID', 'Nama Relawan', 'Nama Anggota Keluarga', 'Hubungan',
            'Jenis Kelamin', 'Tanggal Lahir', 'Pekerjaan', 'Pendidikan',
            'Penghasilan', 'Status Tanggungan'
        ];
        
        // Write BOM for UTF-8
        fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, $headers);
        
        foreach ($volunteers as $volunteer) {
            if ($volunteer->families->count() > 0) {
                foreach ($volunteer->families as $family) {
                    $row = [
                        $volunteer->id,
                        $volunteer->name,
                        $family->nama_anggota,
                        $family->hubungan,
                        $family->jenis_kelamin,
                        $family->tanggal_lahir ? date('d/m/Y', strtotime($family->tanggal_lahir)) : '',
                        $family->pekerjaan,
                        $family->pendidikan,
                        $family->penghasilan,
                        $family->tanggungan ? 'Ya' : 'Tidak'
                    ];
                    fputcsv($file, $row);
                }
            }
        }
        
        fclose($file);
        $this->info("📁 File detail keluarga dibuat: " . realpath($filename));
    }
    
    private function exportToJson($volunteers, $timestamp)
    {
        $filename = "Data_Relawan_Lengkap_{$timestamp}.json";
        
        $data = $volunteers->map(function ($volunteer) {
            return [
                'basic_info' => [
                    'id' => $volunteer->id,
                    'name' => $volunteer->name,
                    'email' => $volunteer->email,
                    'phone' => $volunteer->phone,
                    'is_active' => $volunteer->is_active,
                    'created_at' => $volunteer->created_at
                ],
                'profile' => $volunteer->profile,
                'economic' => $volunteer->economic,
                'social' => $volunteer->social,
                'legislative_member' => $volunteer->anggotaLegislatif,
                'families' => $volunteer->families,
                'family_count' => $volunteer->families->count()
            ];
        });
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("📁 File JSON dibuat: " . realpath($filename));
    }
}
