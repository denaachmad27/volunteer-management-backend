<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Economic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EconomicController extends Controller
{
    /**
     * Tampilkan data ekonomi user
     */
    public function show(Request $request)
    {
        $economic = $request->user()->economic;

        if (!$economic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Economic data not found'
            ], 404);
        }

        // Tambahkan calculated fields
        $economic->sisa_penghasilan = $economic->getSisaPenghasilanAttribute();
        $economic->status_ekonomi = $economic->getStatusEkonomiAttribute();

        return response()->json([
            'status' => 'success',
            'data' => $economic
        ]);
    }

    /**
     * Simpan atau update data ekonomi
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'penghasilan_bulanan' => 'required|numeric|min:0',
            'pengeluaran_bulanan' => 'required|numeric|min:0',
            'status_rumah' => 'required|in:Milik Sendiri,Sewa,Kontrak,Menumpang,Dinas',
            'jenis_rumah' => 'required|string|max:255',
            'punya_kendaraan' => 'required|boolean',
            'jenis_kendaraan' => 'nullable|string|max:255',
            'punya_tabungan' => 'required|boolean',
            'jumlah_tabungan' => 'nullable|numeric|min:0',
            'punya_hutang' => 'required|boolean',
            'jumlah_hutang' => 'nullable|numeric|min:0',
            'sumber_penghasilan_lain' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validasi conditional
        if ($request->punya_kendaraan && !$request->jenis_kendaraan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jenis kendaraan wajib diisi jika punya kendaraan'
            ], 422);
        }

        if ($request->punya_tabungan && !$request->jumlah_tabungan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jumlah tabungan wajib diisi jika punya tabungan'
            ], 422);
        }

        if ($request->punya_hutang && !$request->jumlah_hutang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jumlah hutang wajib diisi jika punya hutang'
            ], 422);
        }

        $data = $request->all();
        $data['user_id'] = $request->user()->id;

        $economic = Economic::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        // Tambahkan calculated fields
        $economic->sisa_penghasilan = $economic->getSisaPenghasilanAttribute();
        $economic->status_ekonomi = $economic->getStatusEkonomiAttribute();

        return response()->json([
            'status' => 'success',
            'message' => 'Economic data saved successfully',
            'data' => $economic
        ]);
    }

    /**
     * Analisis ekonomi sederhana
     */
    public function analysis(Request $request)
    {
        $economic = $request->user()->economic;

        if (!$economic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Economic data not found'
            ], 404);
        }

        $analysis = [
            'sisa_penghasilan' => $economic->getSisaPenghasilanAttribute(),
            'status_ekonomi' => $economic->getStatusEkonomiAttribute(),
            'persentase_pengeluaran' => ($economic->pengeluaran_bulanan / $economic->penghasilan_bulanan) * 100,
            'rekomendasi' => $this->getRecommendation($economic),
            'kategori_kemampuan' => $this->getKemampuanCategory($economic),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $analysis
        ]);
    }

    private function getRecommendation($economic)
    {
        $sisa = $economic->getSisaPenghasilanAttribute();
        $recommendations = [];

        if ($sisa < 0) {
            $recommendations[] = 'Pengeluaran melebihi penghasilan, perlu pengurangan biaya atau tambahan penghasilan';
        } elseif ($sisa < 500000) {
            $recommendations[] = 'Sisa penghasilan sangat kecil, disarankan untuk mulai menabung';
        } else {
            $recommendations[] = 'Kondisi keuangan cukup baik, pertahankan pola ini';
        }

        if (!$economic->punya_tabungan) {
            $recommendations[] = 'Disarankan untuk memulai menabung minimal 10% dari penghasilan';
        }

        if ($economic->punya_hutang && $economic->jumlah_hutang > $economic->penghasilan_bulanan) {
            $recommendations[] = 'Hutang cukup besar, prioritaskan pelunasan hutang';
        }

        return $recommendations;
    }

    private function getKemampuanCategory($economic)
    {
        $penghasilan = $economic->penghasilan_bulanan;

        if ($penghasilan < 2000000) return 'Kurang Mampu';
        if ($penghasilan < 5000000) return 'Sedang';
        if ($penghasilan < 10000000) return 'Mampu';
        return 'Sangat Mampu';
    }
}