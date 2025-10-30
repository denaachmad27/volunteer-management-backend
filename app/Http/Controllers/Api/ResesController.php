<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Reses::with(['anggotaLegislatif', 'creator']);

        // Filter berdasarkan role dan aleg user
        $user = $request->user();

        // Super admin bisa lihat semua data
        if ($user && $user->isAdmin()) {
            // Tidak ada filter untuk super admin
        }
        // Admin aleg filter berdasarkan aleg mereka
        elseif ($user && $user->isAdminAleg() && $user->anggota_legislatif_id) {
            $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->status($request->status);
        }

        // Filter by anggota legislatif (manual filter dari request parameter)
        if ($request->has('anggota_legislatif_id') && $request->anggota_legislatif_id != '') {
            $query->byAnggotaLegislatif($request->anggota_legislatif_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Order by tanggal_mulai descending
        $query->orderBy('tanggal_mulai', 'desc');

        $reses = $query->get();

        // Transform data to include legislative member name
        $reses = $reses->map(function ($item) {
            return [
                'id' => $item->id,
                'judul' => $item->judul,
                'deskripsi' => $item->deskripsi,
                'lokasi' => $item->lokasi,
                'tanggal_mulai' => $item->tanggal_mulai,
                'tanggal_selesai' => $item->tanggal_selesai,
                'status' => $item->status,
                'foto_kegiatan' => $item->foto_kegiatan,
                'legislative_member_id' => $item->anggota_legislatif_id,
                'legislative_member_name' => $item->anggotaLegislatif ? $item->anggotaLegislatif->nama_lengkap : null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Reses list retrieved successfully',
            'data' => $reses,
            'total' => $reses->count(),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'lokasi' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
            'foto_kegiatan' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'anggota_legislatif_id' => 'nullable|exists:anggota_legislatifs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = auth()->id();

        // Handle photo upload
        if ($request->hasFile('foto_kegiatan')) {
            $path = $request->file('foto_kegiatan')->store('reses', 'public');
            $data['foto_kegiatan'] = $path;
        }

        $reses = Reses::create($data);
        $reses->load(['anggotaLegislatif', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Reses created successfully',
            'data' => $reses,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = request()->user();

        // Build query berdasarkan role
        $query = Reses::with(['anggotaLegislatif', 'creator']);

        // Super admin bisa lihat semua data
        if ($user && $user->isAdmin()) {
            // Tidak ada filter untuk super admin
        }
        // Admin aleg filter berdasarkan aleg mereka
        elseif ($user && $user->isAdminAleg() && $user->anggota_legislatif_id) {
            $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
        }

        $reses = $query->find($id);

        if (!$reses) {
            return response()->json([
                'success' => false,
                'message' => 'Reses not found',
            ], 404);
        }

        $data = [
            'id' => $reses->id,
            'judul' => $reses->judul,
            'deskripsi' => $reses->deskripsi,
            'lokasi' => $reses->lokasi,
            'tanggal_mulai' => $reses->tanggal_mulai,
            'tanggal_selesai' => $reses->tanggal_selesai,
            'status' => $reses->status,
            'foto_kegiatan' => $reses->foto_kegiatan,
            'legislative_member_id' => $reses->anggota_legislatif_id,
            'legislative_member_name' => $reses->anggotaLegislatif ? $reses->anggotaLegislatif->nama_lengkap : null,
            'created_at' => $reses->created_at,
            'updated_at' => $reses->updated_at,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Reses retrieved successfully',
            'data' => $data,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();

        // Build query berdasarkan role
        $query = Reses::query();

        // Super admin bisa lihat semua data
        if ($user && $user->isAdmin()) {
            // Tidak ada filter untuk super admin
        }
        // Admin aleg filter berdasarkan aleg mereka
        elseif ($user && $user->isAdminAleg() && $user->anggota_legislatif_id) {
            $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
        }

        $reses = $query->find($id);

        if (!$reses) {
            return response()->json([
                'success' => false,
                'message' => 'Reses not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|required|string|max:255',
            'deskripsi' => 'sometimes|required|string',
            'lokasi' => 'sometimes|required|string|max:255',
            'tanggal_mulai' => 'sometimes|required|date',
            'tanggal_selesai' => 'sometimes|required|date|after_or_equal:tanggal_mulai',
            'status' => 'sometimes|required|in:scheduled,ongoing,completed,cancelled',
            'foto_kegiatan' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'anggota_legislatif_id' => 'nullable|exists:anggota_legislatifs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle photo upload
        if ($request->hasFile('foto_kegiatan')) {
            // Delete old photo if exists
            if ($reses->foto_kegiatan) {
                Storage::disk('public')->delete($reses->foto_kegiatan);
            }

            $path = $request->file('foto_kegiatan')->store('reses', 'public');
            $data['foto_kegiatan'] = $path;
        }

        $reses->update($data);
        $reses->load(['anggotaLegislatif', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Reses updated successfully',
            'data' => $reses,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = request()->user();

        // Build query berdasarkan role
        $query = Reses::query();

        // Super admin bisa lihat semua data
        if ($user && $user->isAdmin()) {
            // Tidak ada filter untuk super admin
        }
        // Admin aleg filter berdasarkan aleg mereka
        elseif ($user && $user->isAdminAleg() && $user->anggota_legislatif_id) {
            $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
        }

        $reses = $query->find($id);

        if (!$reses) {
            return response()->json([
                'success' => false,
                'message' => 'Reses not found',
            ], 404);
        }

        // Delete photo if exists
        if ($reses->foto_kegiatan) {
            Storage::disk('public')->delete($reses->foto_kegiatan);
        }

        $reses->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reses deleted successfully',
        ], 200);
    }
}
