<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BantuanSosial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BantuanSosialController extends Controller
{
    /**
     * Tampilkan semua bantuan sosial (untuk user)
     */
    public function index(Request $request)
    {
        $query = BantuanSosial::query();

        // Filter berdasarkan status dan tersedia
        $query->where('status', 'Aktif')
              ->where('tanggal_mulai', '<=', now())
              ->where('tanggal_selesai', '>=', now());

        // Filter berdasarkan aleg user (untuk user/volunteer)
        $user = $request->user();
        if ($user && $user->anggota_legislatif_id) {
            $query->where(function ($q) use ($user) {
                $q->where('anggota_legislatif_id', $user->anggota_legislatif_id)
                  ->orWhereNull('anggota_legislatif_id'); // Bantuan umum
            });
        } else {
            // Jika user tidak punya aleg, hanya tampilkan bantuan umum
            $query->whereNull('anggota_legislatif_id');
        }

        // Filter berdasarkan jenis bantuan
        if ($request->has('jenis_bantuan')) {
            $query->where('jenis_bantuan', $request->jenis_bantuan);
        }

        // Filter berdasarkan ketersediaan kuota
        if ($request->has('tersedia') && $request->tersedia == 'true') {
            $query->whereColumn('kuota_terpakai', '<', 'kuota');
        }

        $bantuanSosial = $query->orderBy('created_at', 'desc')->get();

        // Tambahkan calculated fields
        $bantuanSosial->each(function ($bantuan) {
            $bantuan->kuota_sisa = $bantuan->getKuotaSisaAttribute();
            $bantuan->is_tersedia = $bantuan->isTersediaAttribute();
            $bantuan->persentase_kuota = $bantuan->getPersentaseKuotaAttribute();
        });

        return response()->json([
            'status' => 'success',
            'data' => $bantuanSosial
        ]);
    }

    /**
     * Tampilkan detail bantuan sosial
     */
    public function show(Request $request, $id)
    {
        $query = BantuanSosial::where('id', $id);

        // Filter berdasarkan aleg user
        $user = $request->user();
        if ($user && $user->anggota_legislatif_id) {
            $query->where(function ($q) use ($user) {
                $q->where('anggota_legislatif_id', $user->anggota_legislatif_id)
                  ->orWhereNull('anggota_legislatif_id');
            });
        } else {
            $query->whereNull('anggota_legislatif_id');
        }

        $bantuanSosial = $query->first();

        if (!$bantuanSosial) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bantuan sosial not found'
            ], 404);
        }

        // Tambahkan calculated fields
        $bantuanSosial->kuota_sisa = $bantuanSosial->getKuotaSisaAttribute();
        $bantuanSosial->is_tersedia = $bantuanSosial->isTersediaAttribute();
        $bantuanSosial->persentase_kuota = $bantuanSosial->getPersentaseKuotaAttribute();

        // Load statistik pendaftaran
        $bantuanSosial->statistik_pendaftaran = [
            'total_pendaftar' => $bantuanSosial->pendaftarans()->count(),
            'pending' => $bantuanSosial->pendaftarans()->where('status', 'Pending')->count(),
            'disetujui' => $bantuanSosial->pendaftarans()->where('status', 'Disetujui')->count(),
            'ditolak' => $bantuanSosial->pendaftarans()->where('status', 'Ditolak')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $bantuanSosial
        ]);
    }

    /**
     * CRUD untuk Admin - Tampilkan semua bantuan
     */
    public function adminIndex(Request $request)
    {
        $query = BantuanSosial::query();

        // Filter berdasarkan role admin
        $user = $request->user();
        if ($user->isAdminAleg()) {
            // Admin aleg hanya melihat bantuan untuk aleg mereka
            $query->byAnggotaLegislatif($user->anggota_legislatif_id);
        }
        // Super admin bisa melihat semua

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan jenis bantuan
        if ($request->has('jenis_bantuan')) {
            $query->where('jenis_bantuan', $request->jenis_bantuan);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_bantuan', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $bantuanSosial = $query->orderBy('created_at', 'desc')->paginate(10);

        // Tambahkan calculated fields untuk setiap item
        $bantuanSosial->getCollection()->each(function ($bantuan) {
            $bantuan->kuota_sisa = $bantuan->getKuotaSisaAttribute();
            $bantuan->is_tersedia = $bantuan->isTersediaAttribute();
            $bantuan->persentase_kuota = $bantuan->getPersentaseKuotaAttribute();
        });

        return response()->json([
            'status' => 'success',
            'data' => $bantuanSosial
        ]);
    }

    /**
     * Buat bantuan sosial baru (Admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_bantuan' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'jenis_bantuan' => 'required|in:Uang Tunai,Sembako,Peralatan,Pelatihan,Kesehatan,Pendidikan',
            'nominal' => 'nullable|numeric|min:0',
            'kuota' => 'required|integer|min:1',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'syarat_bantuan' => 'required|string',
            'dokumen_diperlukan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        // Set anggota_legislatif_id untuk admin aleg
        $user = $request->user();
        if ($user->isAdminAleg()) {
            $data['anggota_legislatif_id'] = $user->anggota_legislatif_id;
        }

        $bantuanSosial = BantuanSosial::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Bantuan sosial created successfully',
            'data' => $bantuanSosial
        ], 201);
    }

    /**
     * Update bantuan sosial (Admin only)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        // Build query berdasarkan role
        $query = BantuanSosial::where('id', $id);
        if ($user->isAdminAleg()) {
            $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
        }
        
        $bantuanSosial = $query->first();

        if (!$bantuanSosial) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bantuan sosial not found or access denied'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_bantuan' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'jenis_bantuan' => 'required|in:Uang Tunai,Sembako,Peralatan,Pelatihan,Kesehatan,Pendidikan',
            'nominal' => 'nullable|numeric|min:0',
            'kuota' => 'required|integer|min:1',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'status' => 'required|in:Aktif,Tidak Aktif,Selesai',
            'syarat_bantuan' => 'required|string',
            'dokumen_diperlukan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $bantuanSosial->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Bantuan sosial updated successfully',
            'data' => $bantuanSosial
        ]);
    }

    /**
     * Hapus bantuan sosial (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        // Build query berdasarkan role
        $query = BantuanSosial::where('id', $id);
        if ($user->isAdminAleg()) {
            $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
        }
        
        $bantuanSosial = $query->first();

        if (!$bantuanSosial) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bantuan sosial not found or access denied'
            ], 404);
        }

        // Cek apakah ada pendaftaran
        if ($bantuanSosial->pendaftarans()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete bantuan sosial with existing registrations'
            ], 422);
        }

        $bantuanSosial->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Bantuan sosial deleted successfully'
        ]);
    }
}