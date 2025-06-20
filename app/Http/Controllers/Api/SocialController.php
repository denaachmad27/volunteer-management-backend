<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Social;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SocialController extends Controller
{
    /**
     * Tampilkan data sosial user
     */
    public function show(Request $request)
    {
        $social = $request->user()->social;

        if (!$social) {
            return response()->json([
                'status' => 'error',
                'message' => 'Social data not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $social
        ]);
    }

    /**
     * Simpan atau update data sosial
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'organisasi' => 'nullable|string|max:255',
            'jabatan_organisasi' => 'nullable|string|max:255',
            'aktif_kegiatan_sosial' => 'required|boolean',
            'jenis_kegiatan_sosial' => 'nullable|string',
            'pernah_dapat_bantuan' => 'required|boolean',
            'jenis_bantuan_diterima' => 'nullable|string',
            'tanggal_bantuan_terakhir' => 'nullable|date',
            'keahlian_khusus' => 'nullable|string',
            'minat_kegiatan' => 'nullable|string',
            'ketersediaan_waktu' => 'required|in:Weekday,Weekend,Fleksibel,Terbatas',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validasi conditional
        if ($request->aktif_kegiatan_sosial && !$request->jenis_kegiatan_sosial) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jenis kegiatan sosial wajib diisi jika aktif dalam kegiatan sosial'
            ], 422);
        }

        if ($request->pernah_dapat_bantuan && !$request->jenis_bantuan_diterima) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jenis bantuan wajib diisi jika pernah menerima bantuan'
            ], 422);
        }

        $data = $request->all();
        $data['user_id'] = $request->user()->id;

        $social = Social::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Social data saved successfully',
            'data' => $social
        ]);
    }

    /**
     * Profil sosial untuk matching kegiatan
     */
    public function profile(Request $request)
    {
        $social = $request->user()->social;

        if (!$social) {
            return response()->json([
                'status' => 'error',
                'message' => 'Social data not found'
            ], 404);
        }

        $profile = [
            'pengalaman_organisasi' => !empty($social->organisasi),
            'aktif_sosial' => $social->aktif_kegiatan_sosial,
            'pernah_dapat_bantuan' => $social->pernah_dapat_bantuan,
            'ketersediaan' => $social->ketersediaan_waktu,
            'keahlian' => !empty($social->keahlian_khusus) ? explode(',', $social->keahlian_khusus) : [],
            'minat' => !empty($social->minat_kegiatan) ? explode(',', $social->minat_kegiatan) : [],
            'skor_keterlibatan' => $this->calculateInvolvementScore($social),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $profile
        ]);
    }

    private function calculateInvolvementScore($social)
    {
        $score = 0;

        // Poin untuk pengalaman organisasi
        if (!empty($social->organisasi)) $score += 20;
        if (!empty($social->jabatan_organisasi)) $score += 15;

        // Poin untuk aktivitas sosial
        if ($social->aktif_kegiatan_sosial) $score += 25;

        // Poin untuk keahlian
        if (!empty($social->keahlian_khusus)) $score += 20;

        // Poin untuk ketersediaan waktu
        switch ($social->ketersediaan_waktu) {
            case 'Fleksibel': $score += 20; break;
            case 'Weekend': $score += 15; break;
            case 'Weekday': $score += 10; break;
            case 'Terbatas': $score += 5; break;
        }

        return min($score, 100); // Max 100
    }
}