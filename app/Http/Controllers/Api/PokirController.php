<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pokir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PokirController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pokir::with(['anggotaLegislatif', 'creator']);

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->status($request->status);
        }

        // Filter by kategori
        if ($request->has('kategori') && $request->kategori != '') {
            $query->kategori($request->kategori);
        }

        // Filter by prioritas
        if ($request->has('prioritas') && $request->prioritas != '') {
            $query->prioritas($request->prioritas);
        }

        // Filter by anggota legislatif
        if ($request->has('anggota_legislatif_id') && $request->anggota_legislatif_id != '') {
            $query->byAnggotaLegislatif($request->anggota_legislatif_id);
        }

        // Order by created_at descending
        $query->orderBy('created_at', 'desc');

        $pokirs = $query->get();

        // Transform data to include legislative member name
        $pokirs = $pokirs->map(function ($item) {
            return [
                'id' => $item->id,
                'judul' => $item->judul,
                'deskripsi' => $item->deskripsi,
                'kategori' => $item->kategori,
                'prioritas' => $item->prioritas,
                'status' => $item->status,
                'lokasi_pelaksanaan' => $item->lokasi_pelaksanaan,
                'target_pelaksanaan' => $item->target_pelaksanaan,
                'legislative_member_id' => $item->anggota_legislatif_id,
                'legislative_member_name' => $item->anggotaLegislatif ? $item->anggotaLegislatif->nama_lengkap : null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Pokir list retrieved successfully',
            'data' => $pokirs,
            'total' => $pokirs->count(),
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
            'kategori' => 'required|string|max:255',
            'prioritas' => 'required|in:high,medium,low',
            'status' => 'required|in:proposed,approved,in_progress,completed,rejected',
            'lokasi_pelaksanaan' => 'nullable|string|max:255',
            'target_pelaksanaan' => 'nullable|date',
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

        $pokir = Pokir::create($data);
        $pokir->load(['anggotaLegislatif', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Pokir created successfully',
            'data' => $pokir,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pokir = Pokir::with(['anggotaLegislatif', 'creator'])->find($id);

        if (!$pokir) {
            return response()->json([
                'success' => false,
                'message' => 'Pokir not found',
            ], 404);
        }

        $data = [
            'id' => $pokir->id,
            'judul' => $pokir->judul,
            'deskripsi' => $pokir->deskripsi,
            'kategori' => $pokir->kategori,
            'prioritas' => $pokir->prioritas,
            'status' => $pokir->status,
            'lokasi_pelaksanaan' => $pokir->lokasi_pelaksanaan,
            'target_pelaksanaan' => $pokir->target_pelaksanaan,
            'legislative_member_id' => $pokir->anggota_legislatif_id,
            'legislative_member_name' => $pokir->anggotaLegislatif ? $pokir->anggotaLegislatif->nama_lengkap : null,
            'created_at' => $pokir->created_at,
            'updated_at' => $pokir->updated_at,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Pokir retrieved successfully',
            'data' => $data,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pokir = Pokir::find($id);

        if (!$pokir) {
            return response()->json([
                'success' => false,
                'message' => 'Pokir not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|required|string|max:255',
            'deskripsi' => 'sometimes|required|string',
            'kategori' => 'sometimes|required|string|max:255',
            'prioritas' => 'sometimes|required|in:high,medium,low',
            'status' => 'sometimes|required|in:proposed,approved,in_progress,completed,rejected',
            'lokasi_pelaksanaan' => 'nullable|string|max:255',
            'target_pelaksanaan' => 'nullable|date',
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
        $pokir->update($data);
        $pokir->load(['anggotaLegislatif', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Pokir updated successfully',
            'data' => $pokir,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pokir = Pokir::find($id);

        if (!$pokir) {
            return response()->json([
                'success' => false,
                'message' => 'Pokir not found',
            ], 404);
        }

        $pokir->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pokir deleted successfully',
        ], 200);
    }
}
