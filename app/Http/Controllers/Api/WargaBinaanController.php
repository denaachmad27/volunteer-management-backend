<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WargaBinaan;
use App\Models\User;
use App\Http\Requests\StoreWargaBinaanRequest;
use App\Http\Requests\UpdateWargaBinaanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WargaBinaanController extends Controller
{
    /**
     * Display a listing of warga binaan (for admin).
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $relawanId = $request->get('relawan_id');

            $query = WargaBinaan::with('relawan:id,name,email');

            // Filter by relawan
            if ($relawanId) {
                $query->where('relawan_id', $relawanId);
            }

            // Search
            if ($search) {
                $query->search($search);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $wargaBinaan = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $wargaBinaan,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching warga binaan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data warga binaan',
            ], 500);
        }
    }

    /**
     * Store a newly created warga binaan.
     */
    public function store(StoreWargaBinaanRequest $request)
    {
        try {
            $wargaBinaan = WargaBinaan::create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Warga binaan berhasil ditambahkan',
                'data' => $wargaBinaan->load('relawan:id,name,email'),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating warga binaan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan warga binaan',
            ], 500);
        }
    }

    /**
     * Display the specified warga binaan.
     */
    public function show($id)
    {
        try {
            $wargaBinaan = WargaBinaan::with('relawan:id,name,email,phone')
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $wargaBinaan,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Warga binaan tidak ditemukan',
            ], 404);
        }
    }

    /**
     * Update the specified warga binaan.
     */
    public function update(UpdateWargaBinaanRequest $request, $id)
    {
        try {
            $wargaBinaan = WargaBinaan::findOrFail($id);
            $wargaBinaan->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Warga binaan berhasil diperbarui',
                'data' => $wargaBinaan->load('relawan:id,name,email'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating warga binaan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui warga binaan',
            ], 500);
        }
    }

    /**
     * Remove the specified warga binaan.
     */
    public function destroy($id)
    {
        try {
            $wargaBinaan = WargaBinaan::findOrFail($id);
            $wargaBinaan->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Warga binaan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting warga binaan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus warga binaan',
            ], 500);
        }
    }

    /**
     * Bulk delete warga binaan.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:warga_binaan,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $ids = $request->ids;
            $deletedCount = WargaBinaan::whereIn('id', $ids)->delete();

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil menghapus {$deletedCount} warga binaan",
                'data' => [
                    'deleted_count' => $deletedCount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting warga binaan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus warga binaan',
            ], 500);
        }
    }

    /**
     * Mass upload warga binaan from Excel/CSV file.
     */
    public function massUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // Max 5MB
            'relawan_id' => 'required|exists:users,id',
            'skip_first_row' => 'nullable|boolean',
            'start_row' => 'nullable|integer|min:1|max:1000',
            'start_column' => 'nullable|integer|min:0|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $relawanId = $request->relawan_id;
            $skipFirstRow = $request->get('skip_first_row', true); // Default true
            $startRow = $request->get('start_row', 2); // Default row 2 (skip header)
            $startColumn = $request->get('start_column', 0); // Default column 0 (first column)

            // Read file content
            $extension = $file->getClientOriginalExtension();

            if ($extension === 'csv') {
                $data = $this->readCsvFile($file, $skipFirstRow, $startRow);
            } else {
                // For Excel files, we'll use a simple reader
                $data = $this->readExcelFile($file, $skipFirstRow, $startRow);
            }

            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File kosong atau format tidak valid',
                ], 422);
            }

            $successCount = 0;
            $failedCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Helper function to clean and default values
                    $cleanString = function($value, $default = 'Kosong') {
                        if ($value === null) {
                            return $default;
                        }

                        $cleaned = trim((string)$value);
                        return $cleaned === '' ? $default : $cleaned;
                    };

                    $cleanInteger = function($value, $default = 0) {
                        if ($value === null || $value === '') {
                            return $default;
                        }
                        $cleaned = trim($value);
                        return ($cleaned === '' || !is_numeric($cleaned)) ? $default : (int)$cleaned;
                    };

                    // Use start_column parameter as offset
                    $offset = $startColumn;

                    // Parse tanggal lahir
                    $tanggalLahir = $this->parseDate($row[$offset + 2] ?? null);

                    // Auto calculate usia if tanggal lahir is provided
                    $usia = $cleanInteger($row[$offset + 3] ?? null, 0);
                    if ($tanggalLahir && $usia === 0) {
                        $birthDate = new \DateTime($tanggalLahir);
                        $today = new \DateTime();
                        $usia = $today->diff($birthDate)->y;
                    }

                    // Map row data to warga binaan fields with defaults
                    $wargaBinaanData = [
                        'relawan_id' => $relawanId,
                        'no_kta' => $cleanString($row[$offset + 0] ?? null, null),
                        'nama' => $cleanString($row[$offset + 1] ?? null, 'Tidak ada'),
                        'tanggal_lahir' => $tanggalLahir ?: date('Y-m-d'),
                        'usia' => $usia,
                        'jenis_kelamin' => in_array(trim($row[$offset + 4] ?? ''), ['Laki-laki', 'Perempuan'])
                            ? trim($row[$offset + 4])
                            : 'Laki-laki',
                        'alamat' => $cleanString($row[$offset + 5] ?? null, 'Tidak ada'),
                        'kecamatan' => $cleanString($row[$offset + 6] ?? null, 'Tidak ada'),
                        'kelurahan' => $cleanString($row[$offset + 7] ?? null, 'Tidak ada'),
                        'rt' => $cleanString($row[$offset + 8] ?? null, '0'),
                        'rw' => $cleanString($row[$offset + 9] ?? null, '0'),
                        'no_hp' => $cleanString($row[$offset + 10] ?? null, 'Tidak ada'),
                        'status_kta' => in_array(trim($row[$offset + 11] ?? ''), ['Sudah punya', 'Belum punya'])
                            ? trim($row[$offset + 11])
                            : 'Belum punya',
                        'hasil_verifikasi' => in_array(trim($row[$offset + 12] ?? ''), [
                            'Bersedia ikut UPA 1 kali per bulan',
                            'Bersedia ikut UPA 1 kali per minggu',
                            'Tidak bersedia'
                        ]) ? trim($row[$offset + 12]) : null,
                    ];

                    // Validate each row (lebih lenient)
                    $rowValidator = Validator::make($wargaBinaanData, [
                        'relawan_id' => 'required|exists:users,id',
                        'no_kta' => 'nullable|string|unique:warga_binaan,no_kta',
                        'nama' => 'required|string|max:255',
                        'tanggal_lahir' => 'required|date',
                        'usia' => 'required|integer|min:0|max:150',
                        'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
                        'alamat' => 'required|string',
                        'kecamatan' => 'required|string|max:255',
                        'kelurahan' => 'required|string|max:255',
                        'rt' => 'nullable|string|max:10',
                        'rw' => 'nullable|string|max:10',
                        'no_hp' => 'nullable|string|max:20',
                        'status_kta' => 'required|in:Sudah punya,Belum punya',
                        'hasil_verifikasi' => 'nullable|in:Bersedia ikut UPA 1 kali per bulan,Bersedia ikut UPA 1 kali per minggu,Tidak bersedia',
                    ]);

                    if ($rowValidator->fails()) {
                        $failedCount++;
                        $errors[] = [
                            'row' => $index + 2, // +2 because index starts at 0 and row 1 is header
                            'errors' => $rowValidator->errors()->all(),
                        ];
                        continue;
                    }

                    WargaBinaan::create($wargaBinaanData);
                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'row' => $index + 2,
                        'errors' => [$e->getMessage()],
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Upload selesai',
                'data' => [
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error mass uploading warga binaan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics for warga binaan.
     */
    public function statistics(Request $request)
    {
        try {
            $relawanId = $request->get('relawan_id');

            $query = WargaBinaan::query();

            if ($relawanId) {
                $query->where('relawan_id', $relawanId);
            }

            $totalWarga = $query->count();
            $sudahKta = (clone $query)->where('status_kta', 'Sudah punya')->count();
            $belumKta = (clone $query)->where('status_kta', 'Belum punya')->count();

            $verifikasi = [
                'upa_bulanan' => (clone $query)->where('hasil_verifikasi', 'Bersedia ikut UPA 1 kali per bulan')->count(),
                'upa_mingguan' => (clone $query)->where('hasil_verifikasi', 'Bersedia ikut UPA 1 kali per minggu')->count(),
                'tidak_bersedia' => (clone $query)->where('hasil_verifikasi', 'Tidak bersedia')->count(),
            ];

            $jenisKelamin = [
                'laki_laki' => (clone $query)->where('jenis_kelamin', 'Laki-laki')->count(),
                'perempuan' => (clone $query)->where('jenis_kelamin', 'Perempuan')->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_warga' => $totalWarga,
                    'status_kta' => [
                        'sudah_punya' => $sudahKta,
                        'belum_punya' => $belumKta,
                    ],
                    'hasil_verifikasi' => $verifikasi,
                    'jenis_kelamin' => $jenisKelamin,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching warga binaan statistics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil statistik',
            ], 500);
        }
    }

    /**
     * Get list of relawan for dropdown.
     */
    public function getRelawanOptions()
    {
        try {
            $relawan = User::whereIn('role', ['relawan', 'user']) // Backward compatibility
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $relawan,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching relawan options: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil daftar relawan',
            ], 500);
        }
    }

    /**
     * Download template file for mass upload.
     */
    public function downloadTemplate()
    {
        try {
            $headers = [
                'No KTA',
                'Nama',
                'Tanggal Lahir (YYYY-MM-DD)',
                'Usia',
                'Jenis Kelamin (Laki-laki/Perempuan)',
                'Alamat',
                'Kecamatan',
                'Kelurahan',
                'RT',
                'RW',
                'No HP',
                'KTA (Sudah punya/Belum punya)',
                'Hasil Verifikasi (Bersedia ikut UPA 1 kali per bulan/Bersedia ikut UPA 1 kali per minggu/Tidak bersedia)',
            ];

            $exampleRow = [
                'KTA001',
                'John Doe',
                '1990-01-15',
                '34',
                'Laki-laki',
                'Jl. Contoh No. 123',
                'Kecamatan A',
                'Kelurahan B',
                '001',
                '002',
                '081234567890',
                'Belum punya',
                'Bersedia ikut UPA 1 kali per bulan',
            ];

            // Create CSV content
            $csvContent = implode(',', $headers) . "\n";
            $csvContent .= implode(',', $exampleRow) . "\n";

            return response($csvContent, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="template_warga_binaan.csv"',
            ]);
        } catch (\Exception $e) {
            Log::error('Error downloading template: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengunduh template',
            ], 500);
        }
    }

    /**
     * Preview CSV file before upload.
     */
    public function previewCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'start_row' => 'nullable|integer|min:1|max:1000',
            'start_column' => 'nullable|integer|min:0|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $startRow = $request->get('start_row', 2);
            $startColumn = $request->get('start_column', 0);

            $handle = fopen($file->getRealPath(), 'r');
            $currentRow = 1;
            $previewData = [];
            $headers = [];
            $totalColumns = 0;

            // Read first row as potential header
            if ($firstRow = fgetcsv($handle)) {
                $headers = $firstRow;
                $totalColumns = count($firstRow);
                fseek($handle, 0); // Reset file pointer
            }

            while (($row = fgetcsv($handle)) !== false && count($previewData) < ($startRow - 1 + 3)) {
                if ($currentRow >= $startRow && count($previewData) < 3) {
                    $previewData[] = [
                        'row_number' => $currentRow,
                        'data' => $row,
                    ];
                }
                $currentRow++;
            }

            fclose($handle);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'headers' => $headers,
                    'preview' => $previewData,
                    'start_row' => $startRow,
                    'start_column' => $startColumn,
                    'total_rows' => $currentRow - 1,
                    'total_columns' => $totalColumns,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error previewing CSV: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Read CSV file.
     */
    private function readCsvFile($file, $skipFirstRow = true, $startRow = 2)
    {
        $data = [];
        $handle = fopen($file->getRealPath(), 'r');
        $currentRow = 1;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip rows before startRow
            if ($currentRow < $startRow) {
                $currentRow++;
                continue;
            }

            $data[] = $row;
            $currentRow++;
        }

        fclose($handle);

        return $data;
    }

    /**
     * Read Excel file (basic implementation).
     */
    private function readExcelFile($file, $skipFirstRow = true)
    {
        // For now, we'll return empty array and suggest using CSV
        // In production, you should use a library like PhpSpreadsheet
        throw new \Exception('Silakan gunakan format CSV untuk upload. Anda dapat download template CSV dari menu Download Template.');
    }

    /**
     * Parse date from various formats.
     */
    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            // Try to parse various date formats
            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $dateString);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }

            // If all formats fail, try strtotime
            $timestamp = strtotime($dateString);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
