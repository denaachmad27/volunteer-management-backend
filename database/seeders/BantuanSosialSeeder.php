<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BantuanSosial;

class BantuanSosialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bantuanSosial = [
            [
                'nama_bantuan' => 'Bantuan Sembako Ramadan 2025',
                'deskripsi' => 'Program bantuan sembako untuk keluarga kurang mampu dalam menyambut bulan Ramadan. Setiap paket berisi beras 10kg, minyak goreng 2L, gula 1kg, dan kebutuhan pokok lainnya.',
                'jenis_bantuan' => 'Sembako',
                'nominal' => 250000,
                'kuota' => 100,
                'kuota_terpakai' => 25,
                'tanggal_mulai' => '2025-02-01',
                'tanggal_selesai' => '2025-03-15',
                'status' => 'Aktif',
                'syarat_bantuan' => 'KTP, KK, Surat keterangan tidak mampu dari kelurahan, Foto rumah',
                'dokumen_diperlukan' => 'KTP, Kartu Keluarga, SKTM, Foto Rumah',
            ],
            [
                'nama_bantuan' => 'Bantuan Pendidikan Anak Sekolah',
                'deskripsi' => 'Bantuan biaya pendidikan untuk anak-anak dari keluarga kurang mampu tingkat SD hingga SMA. Bantuan meliputi uang sekolah, seragam, dan alat tulis.',
                'jenis_bantuan' => 'Pendidikan',
                'nominal' => 1500000,
                'kuota' => 50,
                'kuota_terpakai' => 12,
                'tanggal_mulai' => '2025-01-15',
                'tanggal_selesai' => '2025-06-30',
                'status' => 'Aktif',
                'syarat_bantuan' => 'KTP orangtua, KK, Surat keterangan sekolah, Rapor terakhir, SKTM',
                'dokumen_diperlukan' => 'KTP, Kartu Keluarga, Surat Sekolah, Rapor, SKTM',
            ],
            [
                'nama_bantuan' => 'Bantuan Modal Usaha Mikro',
                'deskripsi' => 'Program bantuan modal untuk memulai atau mengembangkan usaha mikro bagi keluarga kurang mampu. Bantuan berupa uang tunai dengan sistem bergulir.',
                'jenis_bantuan' => 'Uang Tunai',
                'nominal' => 3000000,
                'kuota' => 30,
                'kuota_terpakai' => 8,
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'status' => 'Aktif',
                'syarat_bantuan' => 'KTP, KK, Proposal usaha, SKTM, Surat jaminan',
                'dokumen_diperlukan' => 'KTP, Kartu Keluarga, Proposal Usaha, SKTM, Surat Jaminan',
            ],
            [
                'nama_bantuan' => 'Pelatihan Keterampilan Menjahit',
                'deskripsi' => 'Program pelatihan keterampilan menjahit gratis untuk ibu-ibu rumah tangga. Pelatihan selama 3 bulan dengan sertifikat dan bantuan alat jahit.',
                'jenis_bantuan' => 'Pelatihan',
                'nominal' => 0,
                'kuota' => 25,
                'kuota_terpakai' => 15,
                'tanggal_mulai' => '2025-03-01',
                'tanggal_selesai' => '2025-05-31',
                'status' => 'Aktif',
                'syarat_bantuan' => 'KTP, KK, Surat motivasi, Usia 18-50 tahun',
                'dokumen_diperlukan' => 'KTP, Kartu Keluarga, Surat Motivasi',
            ],
            [
                'nama_bantuan' => 'Bantuan Kesehatan Lansia',
                'deskripsi' => 'Program bantuan kesehatan untuk lansia meliputi pemeriksaan gratis, obat-obatan, dan alat kesehatan. Kerjasama dengan puskesmas setempat.',
                'jenis_bantuan' => 'Kesehatan',
                'nominal' => 500000,
                'kuota' => 75,
                'kuota_terpakai' => 32,
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'status' => 'Aktif',
                'syarat_bantuan' => 'KTP, KK, Usia minimal 60 tahun, Surat keterangan sehat',
                'dokumen_diperlukan' => 'KTP, Kartu Keluarga, Surat Keterangan Sehat',
            ],
            [
                'nama_bantuan' => 'Bantuan Peralatan Pertanian',
                'deskripsi' => 'Bantuan peralatan pertanian untuk petani kecil berupa cangkul, sabit, sprayer, dan bibit unggul. Program untuk meningkatkan produktivitas pertanian.',
                'jenis_bantuan' => 'Peralatan',
                'nominal' => 750000,
                'kuota' => 40,
                'kuota_terpakai' => 5,
                'tanggal_mulai' => '2025-02-15',
                'tanggal_selesai' => '2025-04-15',
                'status' => 'Aktif',
                'syarat_bantuan' => 'KTP, KK, Surat kepemilikan lahan, SKTM',
                'dokumen_diperlukan' => 'KTP, Kartu Keluarga, Surat Tanah, SKTM',
            ],
        ];

        foreach ($bantuanSosial as $bantuan) {
            BantuanSosial::create($bantuan);
        }

        $this->command->info('Bantuan sosial data created successfully!');
    }
}