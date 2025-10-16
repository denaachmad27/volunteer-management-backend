<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class RelawanController extends Controller
{
    /**
     * List warga under the authenticated relawan with optional search + pagination
     */
    public function listWarga(Request $request)
    {
        $authUser = $request->user();

        if (!method_exists($authUser, 'isRelawan') || !$authUser->isRelawan()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya relawan yang boleh mengakses daftar warga.'
            ], 403);
        }

        $perPage = (int) ($request->get('per_page', 15));
        $page = (int) ($request->get('page', 1));
        $search = trim((string) $request->get('search', ''));

        $query = User::with(['profile'])
            ->where('role', 'warga')
            ->where('relawan_id', $authUser->id);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($p) use ($search) {
                      $p->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%");
                  });
            });
        }

        $warga = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar warga berhasil diambil',
            'data' => [
                'data' => $warga->items(),
                'current_page' => $warga->currentPage(),
                'per_page' => $warga->perPage(),
                'total' => $warga->total(),
                'last_page' => $warga->lastPage(),
                'from' => $warga->firstItem(),
                'to' => $warga->lastItem(),
            ],
        ]);
    }

    /**
     * Assign one or more warga to the authenticated relawan.
     * - Only relawan can assign.
     * - 1 warga hanya boleh 1 relawan (skip jika sudah punya relawan).
     * - anggota_legislatif_id warga diset mengikuti relawan.
     */
    public function assignWarga(Request $request)
    {
        $authUser = $request->user();

        if (!method_exists($authUser, 'isRelawan') || !$authUser->isRelawan()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya relawan yang boleh melakukan assign warga.'
            ], 403);
        }

        if (empty($authUser->anggota_legislatif_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Relawan belum terhubung dengan anggota legislatif.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'warga_ids' => 'required_without:warga_id|array',
            'warga_ids.*' => 'integer|exists:users,id',
            'warga_id' => 'required_without:warga_ids|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $ids = [];
        if ($request->filled('warga_ids')) {
            $ids = array_map('intval', $request->warga_ids);
        }
        if ($request->filled('warga_id')) {
            $ids[] = (int) $request->warga_id;
        }
        $ids = array_values(array_unique($ids));

        // Ambil hanya user dengan role 'warga'
        $wargas = User::whereIn('id', $ids)->where('role', 'warga')->get();

        $validIds = $wargas->pluck('id')->all();
        $invalidIds = array_values(array_diff($ids, $validIds));

        $alreadyAssigned = $wargas->filter(fn($u) => !is_null($u->relawan_id))->pluck('id')->all();
        $toAssign = $wargas->filter(fn($u) => is_null($u->relawan_id))->pluck('id')->all();

        $assignedCount = 0;
        if (!empty($toAssign)) {
            DB::transaction(function () use ($toAssign, $authUser, &$assignedCount) {
                $assignedCount = User::whereIn('id', $toAssign)
                    ->update([
                        'relawan_id' => $authUser->id,
                        'anggota_legislatif_id' => $authUser->anggota_legislatif_id,
                    ]);
            });
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Assign warga selesai',
            'data' => [
                'requested' => $ids,
                'assigned_count' => $assignedCount,
                'skipped_already_assigned' => $alreadyAssigned,
                'invalid_user_ids' => $invalidIds,
            ],
        ]);
    }

    /**
     * Create a new warga under the authenticated relawan with minimal required data
     * Fields: nama_lengkap, nik, alamat, ktp_foto (optional image)
     */
    public function createWarga(Request $request)
    {
        $authUser = $request->user();

        if (!method_exists($authUser, 'isRelawan') || !$authUser->isRelawan()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya relawan yang boleh menambahkan warga.'
            ], 403);
        }

        if (empty($authUser->anggota_legislatif_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Relawan belum terhubung dengan anggota legislatif.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'nik' => 'required|string|max:32|unique:profiles,nik',
            'alamat' => 'required|string',
            'ktp_foto' => 'nullable|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = null;
            DB::transaction(function () use ($request, $authUser, &$user) {
                // Generate placeholder email from NIK to satisfy unique constraint
                $nik = $request->nik;
                $email = strtolower($nik.'@warga.local');
                // Ensure uniqueness fallback
                $suffix = 1;
                while (User::where('email', $email)->exists()) {
                    $email = strtolower($nik.'+'.($suffix++).'@warga.local');
                }

                $user = User::create([
                    'name' => $request->nama_lengkap,
                    'email' => $email,
                    'password' => Str::password(12),
                    'role' => 'warga',
                    'is_active' => true,
                    'anggota_legislatif_id' => $authUser->anggota_legislatif_id,
                    'relawan_id' => $authUser->id,
                ]);

                // Handle KTP image upload if present
                $ktpPath = null;
                if ($request->hasFile('ktp_foto')) {
                    $ktpPath = $request->file('ktp_foto')->store('ktp_fotos', 'public');
                }

                // Create minimal profile with defaults for required fields
                $user->profile()->create([
                    'nik' => $request->nik,
                    'nama_lengkap' => $request->nama_lengkap,
                    'jenis_kelamin' => 'Laki-laki',
                    'tempat_lahir' => '-',
                    'tanggal_lahir' => '1970-01-01',
                    'alamat' => $request->alamat,
                    'kelurahan' => '-',
                    'kecamatan' => '-',
                    'kota' => '-',
                    'provinsi' => '-',
                    'kode_pos' => '-',
                    'agama' => 'Islam',
                    'status_pernikahan' => 'Belum Menikah',
                    'pendidikan_terakhir' => 'SMA',
                    'pekerjaan' => '-',
                    'ktp_foto' => $ktpPath,
                ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Warga berhasil ditambahkan',
                'data' => [
                    'user_id' => $user->id,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan warga',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
