<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FamilyController extends Controller
{
    /**
     * Tampilkan semua data keluarga
     */
    public function index(Request $request)
    {
        $families = $request->user()->families()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $families
        ]);
    }

    /**
     * Simpan data keluarga baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_anggota' => 'required|string|max:255',
            'hubungan' => 'required|in:Suami,Istri,Anak,Orang Tua,Saudara,Lainnya',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tanggal_lahir' => 'required|date|before:today',
            'pekerjaan' => 'required|string|max:255',
            'pendidikan' => 'required|in:Tidak Sekolah,SD,SMP,SMA,D3,S1,S2,S3',
            'penghasilan' => 'nullable|numeric|min:0',
            'tanggungan' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['user_id'] = $request->user()->id;

        $family = Family::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Family member added successfully',
            'data' => $family
        ], 201);
    }

    /**
     * Tampilkan detail anggota keluarga
     */
    public function show(Request $request, $id)
    {
        $family = $request->user()->families()->find($id);

        if (!$family) {
            return response()->json([
                'status' => 'error',
                'message' => 'Family member not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $family
        ]);
    }

    /**
     * Update data anggota keluarga
     */
    public function update(Request $request, $id)
    {
        $family = $request->user()->families()->find($id);

        if (!$family) {
            return response()->json([
                'status' => 'error',
                'message' => 'Family member not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_anggota' => 'required|string|max:255',
            'hubungan' => 'required|in:Suami,Istri,Anak,Orang Tua,Saudara,Lainnya',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tanggal_lahir' => 'required|date|before:today',
            'pekerjaan' => 'required|string|max:255',
            'pendidikan' => 'required|in:Tidak Sekolah,SD,SMP,SMA,D3,S1,S2,S3',
            'penghasilan' => 'nullable|numeric|min:0',
            'tanggungan' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $family->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Family member updated successfully',
            'data' => $family
        ]);
    }

    /**
     * Hapus anggota keluarga
     */
    public function destroy(Request $request, $id)
    {
        $family = $request->user()->families()->find($id);

        if (!$family) {
            return response()->json([
                'status' => 'error',
                'message' => 'Family member not found'
            ], 404);
        }

        $family->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Family member deleted successfully'
        ]);
    }

    /**
     * Statistik keluarga
     */
    public function statistics(Request $request)
    {
        $families = $request->user()->families;

        $stats = [
            'total_anggota' => $families->count(),
            'total_tanggungan' => $families->where('tanggungan', true)->count(),
            'total_penghasilan_keluarga' => $families->sum('penghasilan'),
            'rata_rata_umur' => $families->avg(function($family) {
                return $family->age;
            }),
            'distribusi_pendidikan' => $families->groupBy('pendidikan')->map->count(),
            'distribusi_hubungan' => $families->groupBy('hubungan')->map->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}