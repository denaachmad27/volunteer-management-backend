<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\User;

// Boot Laravel
$app = require 'bootstrap/app.php';
$app->boot();

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator('Volunteer Management System')
    ->setLastModifiedBy('Admin')
    ->setTitle('Data Relawan Lengkap')
    ->setSubject('Informasi Personal, Keluarga, Ekonomi, dan Sosial Relawan')
    ->setDescription('Export data lengkap relawan termasuk informasi personal, data keluarga, data ekonomi, dan data sosial');

// Get active sheet
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Relawan');

// Define headers
$headers = [
    // Basic Info
    'ID', 'Nama', 'Email', 'Telepon', 'Status Aktif', 'Tanggal Daftar',
    
    // Profile Info
    'NIK', 'Nama Lengkap', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir',
    'Alamat', 'Kelurahan', 'Kecamatan', 'Kota', 'Provinsi', 'Kode Pos',
    'Agama', 'Status Pernikahan', 'Pendidikan Terakhir', 'Pekerjaan',
    
    // Economic Info
    'Penghasilan Bulanan', 'Pengeluaran Bulanan', 'Status Rumah', 'Jenis Rumah',
    'Punya Kendaraan', 'Jenis Kendaraan', 'Punya Tabungan', 'Jumlah Tabungan',
    'Punya Hutang', 'Jumlah Hutang', 'Sumber Penghasilan Lain',
    
    // Social Info
    'Organisasi', 'Jabatan Organisasi', 'Aktif Kegiatan Sosial', 'Jenis Kegiatan Sosial',
    'Pernah Dapat Bantuan', 'Jenis Bantuan Diterima', 'Tanggal Bantuan Terakhir',
    'Keahlian Khusus', 'Minat Kegiatan', 'Ketersediaan Waktu',
    
    // Legislative Member
    'Anggota Legislatif', 'Partai Politik',
    
    // Family Count
    'Jumlah Anggota Keluarga'
];

// Set headers in first row
$column = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($column . '1', $header);
    $column++;
}

// Style header row
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2E8B57']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];

$lastColumn = chr(ord('A') + count($headers) - 1);
$sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerStyle);

// Auto-size columns
foreach (range('A', $lastColumn) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Get volunteer data
$volunteers = User::where('role', 'user')
    ->with(['profile', 'families', 'economic', 'social', 'anggotaLegislatif'])
    ->get();

$row = 2;
foreach ($volunteers as $volunteer) {
    $profile = $volunteer->profile;
    $economic = $volunteer->economic;
    $social = $volunteer->social;
    $legislativeMember = $volunteer->anggotaLegislatif;
    $familyCount = $volunteer->families->count();
    
    $data = [
        // Basic Info
        $volunteer->id,
        $volunteer->name,
        $volunteer->email,
        $volunteer->phone,
        $volunteer->is_active ? 'Aktif' : 'Tidak Aktif',
        $volunteer->created_at ? $volunteer->created_at->format('d/m/Y') : '',
        
        // Profile Info
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
        
        // Economic Info
        $economic ? 'Rp ' . number_format($economic->penghasilan_bulanan, 0, ',', '.') : '',
        $economic ? 'Rp ' . number_format($economic->pengeluaran_bulanan, 0, ',', '.') : '',
        $economic ? $economic->status_rumah : '',
        $economic ? $economic->jenis_rumah : '',
        $economic ? ($economic->punya_kendaraan ? 'Ya' : 'Tidak') : '',
        $economic ? $economic->jenis_kendaraan : '',
        $economic ? ($economic->punya_tabungan ? 'Ya' : 'Tidak') : '',
        $economic ? ($economic->jumlah_tabungan ? 'Rp ' . number_format($economic->jumlah_tabungan, 0, ',', '.') : '') : '',
        $economic ? ($economic->punya_hutang ? 'Ya' : 'Tidak') : '',
        $economic ? ($economic->jumlah_hutang ? 'Rp ' . number_format($economic->jumlah_hutang, 0, ',', '.') : '') : '',
        $economic ? $economic->sumber_penghasilan_lain : '',
        
        // Social Info
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
        
        // Family Count
        $familyCount
    ];
    
    $column = 'A';
    foreach ($data as $value) {
        $sheet->setCellValue($column . $row, $value);
        $column++;
    }
    $row++;
}

// Add zebra striping for better readability
for ($i = 2; $i <= $row - 1; $i++) {
    if ($i % 2 == 0) {
        $sheet->getStyle('A' . $i . ':' . $lastColumn . $i)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(['rgb' => 'F8F9FA']);
    }
}

// Add borders
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC'],
        ],
    ],
];
$sheet->getStyle('A1:' . $lastColumn . ($row - 1))->applyFromArray($styleArray);

// Create second sheet for family details
$familySheet = $spreadsheet->createSheet();
$familySheet->setTitle('Detail Keluarga');

$familyHeaders = [
    'User ID', 'Nama Relawan', 'Nama Anggota Keluarga', 'Hubungan', 
    'Jenis Kelamin', 'Tanggal Lahir', 'Pekerjaan', 'Pendidikan', 
    'Penghasilan', 'Status Tanggungan'
];

$column = 'A';
foreach ($familyHeaders as $header) {
    $familySheet->setCellValue($column . '1', $header);
    $column++;
}

// Apply header style to family sheet
$familyLastColumn = chr(ord('A') + count($familyHeaders) - 1);
$familySheet->getStyle('A1:' . $familyLastColumn . '1')->applyFromArray($headerStyle);

// Auto-size columns for family sheet
foreach (range('A', $familyLastColumn) as $col) {
    $familySheet->getColumnDimension($col)->setAutoSize(true);
}

// Fill family data
$familyRow = 2;
foreach ($volunteers as $volunteer) {
    if ($volunteer->families->count() > 0) {
        foreach ($volunteer->families as $family) {
            $familyData = [
                $volunteer->id,
                $volunteer->name,
                $family->nama_anggota,
                $family->hubungan,
                $family->jenis_kelamin,
                $family->tanggal_lahir ? date('d/m/Y', strtotime($family->tanggal_lahir)) : '',
                $family->pekerjaan,
                $family->pendidikan,
                $family->penghasilan ? 'Rp ' . number_format($family->penghasilan, 0, ',', '.') : '',
                $family->tanggungan ? 'Ya' : 'Tidak'
            ];
            
            $column = 'A';
            foreach ($familyData as $value) {
                $familySheet->setCellValue($column . $familyRow, $value);
                $column++;
            }
            $familyRow++;
        }
    }
}

// Add zebra striping to family sheet
for ($i = 2; $i <= $familyRow - 1; $i++) {
    if ($i % 2 == 0) {
        $familySheet->getStyle('A' . $i . ':' . $familyLastColumn . $i)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(['rgb' => 'F8F9FA']);
    }
}

// Add borders to family sheet
$familySheet->getStyle('A1:' . $familyLastColumn . ($familyRow - 1))->applyFromArray($styleArray);

// Save file
$filename = 'Data_Relawan_Lengkap_' . date('Y-m-d_H-i-s') . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);

echo "✅ File Excel berhasil dibuat: $filename\n";
echo "📊 Total Relawan: " . $volunteers->count() . "\n";
echo "👨‍👩‍👧‍👦 Total Data Keluarga: " . ($familyRow - 2) . "\n";
echo "📁 Lokasi file: " . realpath($filename) . "\n";