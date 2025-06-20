<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pendaftaran;
use App\Models\BantuanSosial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PendaftaranController extends Controller
{
    /**
     * Tampilkan semua pendaftaran user
     */
    public function index(Request $request)
    {
        $pendaftarans = $request->user()->pendaftarans()
            ->with('bantuanSosial')
            ->orderBy('created_at', 'desc')
            ->get();

        // Tambahkan calculated fields
        $pendaftarans->each(function ($pendaftaran) {
            $pendaftaran->status_color = $pendaftaran->getStatusColorAttribute();
            $pendaftaran->is_pending = $pendaftaran->isPendingAttribute();
            $pendaftaran->is_approved = $pendaftaran->isApprovedAttribute();
            $pendaftaran->is_rejected = $pendaftaran->isRejectedAttribute();
        });

        return response()->json([
            'status' => 'success',
            'data' => $pendaftarans
        ]);
    }

    /**
     * Daftar bantuan sosial baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bantuan_sosial_id' => 'required|exists:bantuan_sosials,id',
            'alasan_pengajuan' => 'required|string',
            'dokumen_upload' => 'nullable|array',
            'dokumen_upload.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Cek apakah bantuan sosial tersedia
        $bantuanSosial = BantuanSosial::find($request->bantuan_sosial_id);
        
        if (!$bantuanSosial->isTersediaAttribute()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bantuan sosial tidak tersedia atau kuota sudah penuh'
            ], 422);
        }

        // Cek apakah user sudah mendaftar untuk bantuan ini
        $existingPendaftaran = Pendaftaran::where('user_id', $request->user()->id)
            ->where('bantuan_sosial_id', $request->bantuan_sosial_id)
            ->whereIn('status', ['Pending', 'Diproses', 'Disetujui'])
            ->first();

        if ($existingPendaftaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah mendaftar untuk bantuan ini'
            ], 422);
        }

        // Handle upload dokumen
        $dokumenPaths = [];
        if ($request->hasFile('dokumen_upload')) {
            foreach ($request->file('dokumen_upload') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('pendaftaran_dokumen', $filename, 'public');
                $dokumenPaths[] = $path;
            }
        }

        // Buat pendaftaran
        $pendaftaran = Pendaftaran::create([
            'user_id' => $request->user()->id,
            'bantuan_sosial_id' => $request->bantuan_sosial_id,
            'no_pendaftaran' => (new Pendaftaran())->generateNoPendaftaran(),
            'tanggal_daftar' => now()->toDateString(),
            'alasan_pengajuan' => $request->alasan_pengajuan,
            'dokumen_upload' => $dokumenPaths,
            'status' => 'Pending',
        ]);

        // Update kuota terpakai
        $bantuanSosial->increment('kuota_terpakai');

        $pendaftaran->load('bantuanSosial');

        return response()->json([
            'status' => 'success',
            'message' => 'Pendaftaran berhasil dibuat',
            'data' => $pendaftaran
        ], 201);
    }

    /**
     * Tampilkan detail pendaftaran
     */
    public function show(Request $request, $id)
    {
        $pendaftaran = $request->user()->pendaftarans()
            ->with('bantuanSosial')
            ->find($id);

        if (!$pendaftaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pendaftaran not found'
            ], 404);
        }

        // Tambahkan calculated fields
        $pendaftaran->status_color = $pendaftaran->getStatusColorAttribute();
        $pendaftaran->is_pending = $pendaftaran->isPendingAttribute();
        $pendaftaran->is_approved = $pendaftaran->isApprovedAttribute();
        $pendaftaran->is_rejected = $pendaftaran->isRejectedAttribute();

        return response()->json([
            'status' => 'success',
            'data' => $pendaftaran
        ]);
    }

    /**
     * Admin: Tampilkan semua pendaftaran
     */
    public function adminIndex(Request $request)
    {
        $query = Pendaftaran::with(['user.profile', 'bantuanSosial']);

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan bantuan sosial
        if ($request->has('bantuan_sosial_id')) {
            $query->where('bantuan_sosial_id', $request->bantuan_sosial_id);
        }

        // Search berdasarkan nama user atau nomor pendaftaran
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_pendaftaran', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $pendaftarans = $query->orderBy('created_at', 'desc')->paginate(15);

        // Tambahkan calculated fields
        $pendaftarans->getCollection()->each(function ($pendaftaran) {
            $pendaftaran->status_color = $pendaftaran->getStatusColorAttribute();
        });

        return response()->json([
            'status' => 'success',
            'data' => $pendaftarans
        ]);
    }

    /**
     * Admin: Update status pendaftaran
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Pending,Diproses,Disetujui,Ditolak,Selesai',
            'catatan_admin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $pendaftaran = Pendaftaran::find($id);

        if (!$pendaftaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pendaftaran not found'
            ], 404);
        }

        $oldStatus = $pendaftaran->status;
        $data = [
            'status' => $request->status,
            'catatan_admin' => $request->catatan_admin,
        ];

        // Set tanggal persetujuan jika disetujui
        if ($request->status === 'Disetujui' && $oldStatus !== 'Disetujui') {
            $data['tanggal_persetujuan'] = now()->toDateString();
        }

        // Set tanggal penyerahan jika selesai
        if ($request->status === 'Selesai' && $oldStatus !== 'Selesai') {
            $data['tanggal_penyerahan'] = now()->toDateString();
        }

        // Jika ditolak, kurangi kuota terpakai
        if ($request->status === 'Ditolak' && $oldStatus !== 'Ditolak') {
            $pendaftaran->bantuanSosial->decrement('kuota_terpakai');
        }

        // Jika dari ditolak ke status lain, tambah kuota terpakai
        if ($oldStatus === 'Ditolak' && $request->status !== 'Ditolak') {
            $pendaftaran->bantuanSosial->increment('kuota_terpakai');
        }

        $pendaftaran->update($data);
        $pendaftaran->load('bantuanSosial', 'user.profile');

        return response()->json([
            'status' => 'success',
            'message' => 'Status pendaftaran berhasil diupdate',
            'data' => $pendaftaran
        ]);
    }

    /**
     * Download dokumen pendaftaran
     */
    public function downloadDokumen($id, $index)
    {
        $pendaftaran = Pendaftaran::find($id);

        if (!$pendaftaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pendaftaran not found'
            ], 404);
        }

        $dokumenUpload = $pendaftaran->dokumen_upload;
        if (!isset($dokumenUpload[$index])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found'
            ], 404);
        }

        $filePath = storage_path('app/public/' . $dokumenUpload[$index]);
        
        if (!file_exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File not found'
            ], 404);
        }

        return response()->download($filePath);
    }

    /**
     * Statistik pendaftaran
     */
    public function statistics(Request $request)
    {
        $stats = [
            'total_pendaftaran' => Pendaftaran::count(),
            'pending' => Pendaftaran::where('status', 'Pending')->count(),
            'diproses' => Pendaftaran::where('status', 'Diproses')->count(),
            'disetujui' => Pendaftaran::where('status', 'Disetujui')->count(),
            'ditolak' => Pendaftaran::where('status', 'Ditolak')->count(),
            'selesai' => Pendaftaran::where('status', 'Selesai')->count(),
        ];

        // Statistik per bulan (6 bulan terakhir)
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyStats[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->format('F Y'),
                'total' => Pendaftaran::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'overview' => $stats,
                'monthly' => $monthlyStats,
            ]
        ]);
    }
}