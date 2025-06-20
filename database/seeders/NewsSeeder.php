<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\News;
use App\Models\User;
use Illuminate\Support\Str;

class NewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        
        $newsData = [
            [
                'judul' => 'Pembukaan Program Bantuan Sembako Ramadan 2025',
                'slug' => 'pembukaan-program-bantuan-sembako-ramadan-2025',
                'konten' => '<p>Alhamdulillah, kami dengan bangga mengumumkan pembukaan program Bantuan Sembako Ramadan 2025 yang akan dilaksanakan mulai tanggal 1 Februari 2025.</p>

<p>Program ini merupakan bentuk kepedulian kita bersama untuk membantu saudara-saudara yang kurang mampu dalam menyambut bulan suci Ramadan. Setiap paket bantuan berisi:</p>
<ul>
<li>Beras 10 kg</li>
<li>Minyak goreng 2 liter</li>
<li>Gula pasir 1 kg</li>
<li>Tepung terigu 1 kg</li>
<li>Mie instan 1 dus</li>
<li>Susu kental manis 3 kaleng</li>
</ul>

<p>Kuota tersedia untuk 100 keluarga dengan persyaratan yang mudah. Mari bersama-sama berbagi kebahagiaan di bulan yang penuh berkah ini.</p>

<p>Pendaftaran dapat dilakukan melalui aplikasi atau datang langsung ke kantor sekretariat.</p>',
                'kategori' => 'Pengumuman',
                'is_published' => true,
                'published_at' => now()->subDays(5),
                'views' => 156,
                'created_by' => $admin->id,
            ],
            [
                'judul' => 'Kegiatan Gotong Royong Membersihkan Lingkungan',
                'slug' => 'kegiatan-gotong-royong-membersihkan-lingkungan',
                'konten' => '<p>Pada hari Minggu, 15 Juni 2025, telah dilaksanakan kegiatan gotong royong membersihkan lingkungan di wilayah RT 05/RW 02. Kegiatan ini diikuti oleh 45 relawan dari berbagai usia.</p>

<p>Kegiatan dimulai pukul 06.00 WIB dengan pembagian area kerja. Tim dibagi menjadi beberapa kelompok:</p>
<ul>
<li>Kelompok 1: Membersihkan saluran air</li>
<li>Kelompok 2: Mengumpulkan sampah plastik</li>
<li>Kelompok 3: Menanam pohon di taman</li>
<li>Kelompok 4: Mengecat pagar dan fasilitas umum</li>
</ul>

<p>Hasil yang dicapai sangat memuaskan. Sebanyak 2 ton sampah berhasil dikumpulkan, 15 pohon baru ditanam, dan area sepanjang 500 meter berhasil dibersihkan.</p>

<p>Terima kasih kepada semua relawan yang telah berpartisipasi. Mari kita jaga kebersihan lingkungan kita bersama-sama!</p>',
                'kategori' => 'Kegiatan',
                'is_published' => true,
                'published_at' => now()->subDays(3),
                'views' => 89,
                'created_by' => $admin->id,
            ],
            [
                'judul' => 'Pelatihan Keterampilan Digital untuk UMKM',
                'slug' => 'pelatihan-keterampilan-digital-untuk-umkm',
                'konten' => '<p>Dalam era digital saat ini, kemampuan memasarkan produk secara online menjadi sangat penting bagi pelaku UMKM. Oleh karena itu, kami mengadakan pelatihan keterampilan digital khusus untuk UMKM.</p>

<p><strong>Detail Pelatihan:</strong></p>
<ul>
<li>Tanggal: 25-27 Juni 2025</li>
<li>Waktu: 09.00 - 16.00 WIB</li>
<li>Tempat: Balai Desa Sukamaju</li>
<li>Peserta: Maksimal 30 orang</li>
</ul>

<p><strong>Materi yang akan dipelajari:</strong></p>
<ul>
<li>Cara membuat akun media sosial bisnis</li>
<li>Teknik fotografi produk dengan smartphone</li>
<li>Strategi pemasaran digital</li>
<li>Penggunaan marketplace</li>
<li>Pengelolaan keuangan digital</li>
</ul>

<p>Pelatihan ini GRATIS dan akan mendapat sertifikat. Daftar sekarang juga karena kuota terbatas!</p>',
                'kategori' => 'Kegiatan',
                'is_published' => true,
                'published_at' => now()->subDays(2),
                'views' => 234,
                'created_by' => $admin->id,
            ],
            [
                'judul' => 'Laporan Penyaluran Bantuan Bulan Mei 2025',
                'slug' => 'laporan-penyaluran-bantuan-bulan-mei-2025',
                'konten' => '<p>Berikut ini adalah laporan penyaluran bantuan sosial yang telah dilaksanakan pada bulan Mei 2025:</p>

<h3>Bantuan Sembako</h3>
<ul>
<li>Jumlah penerima: 85 keluarga</li>
<li>Total nilai bantuan: Rp 21.250.000</li>
<li>Lokasi: 5 RT di Kelurahan Sukamaju</li>
</ul>

<h3>Bantuan Pendidikan</h3>
<ul>
<li>Jumlah penerima: 28 anak</li>
<li>Total nilai bantuan: Rp 42.000.000</li>
<li>Jenjang: SD (12 anak), SMP (10 anak), SMA (6 anak)</li>
</ul>

<h3>Bantuan Kesehatan</h3>
<ul>
<li>Jumlah penerima: 45 lansia</li>
<li>Total nilai bantuan: Rp 22.500.000</li>
<li>Jenis bantuan: Pemeriksaan kesehatan dan obat-obatan</li>
</ul>

<p>Total bantuan yang disalurkan pada bulan Mei 2025 adalah sebesar <strong>Rp 85.750.000</strong> untuk 158 penerima manfaat.</p>

<p>Terima kasih kepada semua donatur dan relawan yang telah membantu kelancaran program ini.</p>',
                'kategori' => 'Bantuan',
                'is_published' => true,
                'published_at' => now()->subDays(1),
                'views' => 67,
                'created_by' => $admin->id,
            ],
            [
                'judul' => 'Tips Menjaga Kesehatan di Musim Hujan',
                'slug' => 'tips-menjaga-kesehatan-di-musim-hujan',
                'konten' => '<p>Musim hujan telah tiba dan kita perlu extra waspada untuk menjaga kesehatan. Berikut beberapa tips yang bisa diterapkan:</p>

<h3>1. Jaga Kebersihan</h3>
<ul>
<li>Cuci tangan dengan sabun secara teratur</li>
<li>Mandi dengan air hangat setelah kehujanan</li>
<li>Ganti pakaian basah segera</li>
</ul>

<h3>2. Konsumsi Makanan Bergizi</h3>
<ul>
<li>Perbanyak makan buah dan sayuran</li>
<li>Minum air putih yang cukup</li>
<li>Konsumsi vitamin C untuk meningkatkan imunitas</li>
</ul>

<h3>3. Hindari Genangan Air</h3>
<ul>
<li>Jangan bermain di genangan air</li>
<li>Gunakan alas kaki yang tepat</li>
<li>Bersihkan lingkungan dari genangan air</li>
</ul>

<h3>4. Istirahat Cukup</h3>
<ul>
<li>Tidur minimal 7-8 jam per hari</li>
<li>Hindari begadang</li>
<li>Kelola stress dengan baik</li>
</ul>

<p>Mari kita jaga kesehatan bersama-sama agar tetap fit dan produktif di musim hujan ini!</p>',
                'kategori' => 'Umum',
                'is_published' => true,
                'published_at' => now(),
                'views' => 12,
                'created_by' => $admin->id,
            ],
        ];

        foreach ($newsData as $news) {
            News::create($news);
        }

        $this->command->info('News data created successfully!');
    }
}