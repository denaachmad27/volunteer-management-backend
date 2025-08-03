<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnggotaLegislatif;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AnggotaLegislatifController extends Controller
{
    /**
     * Display a listing of anggota legislatif with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AnggotaLegislatif::withCount('volunteers');

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('kode_aleg', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('jabatan_saat_ini', 'like', "%{$search}%")
                      ->orWhere('partai_politik', 'like', "%{$search}%")
                      ->orWhere('daerah_pemilihan', 'like', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->has('status') && !empty($request->status)) {
                if ($request->status === 'aktif') {
                    $query->where('status', 'Aktif');
                } elseif ($request->status === 'tidak_aktif') {
                    $query->where('status', 'Tidak Aktif');
                }
            }

            // Apply gender filter
            if ($request->has('jenis_kelamin') && !empty($request->jenis_kelamin) && $request->jenis_kelamin !== 'all') {
                $query->where('jenis_kelamin', $request->jenis_kelamin);
            }

            // Apply city filter
            if ($request->has('kota') && !empty($request->kota)) {
                $query->where('kota', 'like', "%{$request->kota}%");
            }

            // Apply party filter
            if ($request->has('partai') && !empty($request->partai)) {
                $query->where('partai_politik', 'like', "%{$request->partai}%");
            }

            // Get pagination parameters
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);

            // Execute query with pagination
            $anggotaLegislatif = $query->orderBy('created_at', 'desc')
                                      ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'message' => 'Anggota Legislatif retrieved successfully',
                'data' => [
                    'data' => $anggotaLegislatif->items(),
                    'current_page' => $anggotaLegislatif->currentPage(),
                    'per_page' => $anggotaLegislatif->perPage(),
                    'total' => $anggotaLegislatif->total(),
                    'last_page' => $anggotaLegislatif->lastPage(),
                    'from' => $anggotaLegislatif->firstItem(),
                    'to' => $anggotaLegislatif->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve Anggota Legislatif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get anggota legislatif statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $totalAleg = AnggotaLegislatif::count();
            $activeAleg = AnggotaLegislatif::where('status', 'Aktif')->count();
            $inactiveAleg = AnggotaLegislatif::where('status', 'Tidak Aktif')->count();

            // Total volunteers under all aleg
            $totalVolunteers = AnggotaLegislatif::withCount('volunteers')->get()->sum('volunteers_count');

            // Gender distribution
            $genderStats = AnggotaLegislatif::select('jenis_kelamin', DB::raw('count(*) as count'))
                                          ->groupBy('jenis_kelamin')
                                          ->pluck('count', 'jenis_kelamin')
                                          ->toArray();

            // Age distribution
            $ageStats = AnggotaLegislatif::selectRaw('
                                CASE 
                                    WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) < 35 THEN "< 35"
                                    WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 35 AND 45 THEN "35-45"
                                    WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 46 AND 55 THEN "46-55"
                                    WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 56 AND 65 THEN "56-65"
                                    ELSE "> 65"
                                END as age_group,
                                count(*) as count
                            ')
                           ->groupBy(DB::raw('age_group'))
                           ->pluck('count', 'age_group')
                           ->toArray();

            // Party distribution
            $partyStats = AnggotaLegislatif::select('partai_politik', DB::raw('count(*) as count'))
                                         ->groupBy('partai_politik')
                                         ->orderBy('count', 'desc')
                                         ->pluck('count', 'partai_politik')
                                         ->toArray();

            // City distribution (top 10)
            $cityStats = AnggotaLegislatif::select('kota', DB::raw('count(*) as count'))
                                        ->groupBy('kota')
                                        ->orderBy('count', 'desc')
                                        ->limit(10)
                                        ->pluck('count', 'kota')
                                        ->toArray();

            // Top aleg by volunteer count
            $topAlegByVolunteers = AnggotaLegislatif::withCount('volunteers')
                                                  ->orderBy('volunteers_count', 'desc')
                                                  ->limit(10)
                                                  ->get(['id', 'nama_lengkap', 'kode_aleg', 'volunteers_count']);

            $stats = [
                'total_aleg' => $totalAleg,
                'active_aleg' => $activeAleg,
                'inactive_aleg' => $inactiveAleg,
                'total_volunteers' => $totalVolunteers,
                'gender_distribution' => $genderStats,
                'age_distribution' => $ageStats,
                'party_distribution' => $partyStats,
                'city_distribution' => $cityStats,
                'top_aleg_by_volunteers' => $topAlegByVolunteers,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Anggota Legislatif statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve Anggota Legislatif statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created anggota legislatif
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'kode_aleg' => 'required|string|max:20|unique:anggota_legislatifs',
                'nama_lengkap' => 'required|string|max:255',
                'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
                'tempat_lahir' => 'required|string|max:255',
                'tanggal_lahir' => 'required|date|before:today',
                'alamat' => 'required|string',
                'kelurahan' => 'required|string|max:255',
                'kecamatan' => 'required|string|max:255',
                'kota' => 'required|string|max:255',
                'provinsi' => 'required|string|max:255',
                'kode_pos' => 'required|string|max:10',
                'no_telepon' => 'required|string|max:20',
                'email' => 'required|email|unique:anggota_legislatifs',
                'jabatan_saat_ini' => 'required|string|max:255',
                'partai_politik' => 'required|string|max:255',
                'daerah_pemilihan' => 'required|string|max:255',
                'riwayat_jabatan' => 'nullable|string',
                'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'status' => 'required|in:Aktif,Tidak Aktif',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();

            // Handle file upload
            if ($request->hasFile('foto_profil')) {
                $file = $request->file('foto_profil');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('anggota_legislatif', $filename, 'public');
                $data['foto_profil'] = $path;
            }

            $anggotaLegislatif = AnggotaLegislatif::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Anggota Legislatif created successfully',
                'data' => $anggotaLegislatif
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Anggota Legislatif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified anggota legislatif
     */
    public function show($id): JsonResponse
    {
        try {
            // Check if this is an admin request (has authorization header)
            $isAdmin = request()->hasHeader('Authorization');
            
            if ($isAdmin) {
                // Admin can see volunteers data
                $anggotaLegislatif = AnggotaLegislatif::with(['volunteers' => function ($query) {
                    $query->with('profile')->orderBy('created_at', 'desc');
                }])->findOrFail($id);
            } else {
                // Public access - no volunteers data
                $anggotaLegislatif = AnggotaLegislatif::findOrFail($id);
                // Add volunteer count without showing sensitive data
                $anggotaLegislatif->volunteers_count = $anggotaLegislatif->volunteers()->count();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Anggota Legislatif retrieved successfully',
                'data' => $anggotaLegislatif
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anggota Legislatif not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified anggota legislatif
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $anggotaLegislatif = AnggotaLegislatif::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'kode_aleg' => 'required|string|max:20|unique:anggota_legislatifs,kode_aleg,' . $id,
                'nama_lengkap' => 'required|string|max:255',
                'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
                'tempat_lahir' => 'required|string|max:255',
                'tanggal_lahir' => 'required|date|before:today',
                'alamat' => 'required|string',
                'kelurahan' => 'required|string|max:255',
                'kecamatan' => 'required|string|max:255',
                'kota' => 'required|string|max:255',
                'provinsi' => 'required|string|max:255',
                'kode_pos' => 'required|string|max:10',
                'no_telepon' => 'required|string|max:20',
                'email' => 'required|email|unique:anggota_legislatifs,email,' . $id,
                'jabatan_saat_ini' => 'required|string|max:255',
                'partai_politik' => 'required|string|max:255',
                'daerah_pemilihan' => 'required|string|max:255',
                'riwayat_jabatan' => 'nullable|string',
                'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'status' => 'required|in:Aktif,Tidak Aktif',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();

            // Handle file upload
            if ($request->hasFile('foto_profil')) {
                // Delete old file if exists
                if ($anggotaLegislatif->foto_profil && Storage::disk('public')->exists($anggotaLegislatif->foto_profil)) {
                    Storage::disk('public')->delete($anggotaLegislatif->foto_profil);
                }

                $file = $request->file('foto_profil');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('anggota_legislatif', $filename, 'public');
                $data['foto_profil'] = $path;
            }

            $anggotaLegislatif->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Anggota Legislatif updated successfully',
                'data' => $anggotaLegislatif
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Anggota Legislatif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified anggota legislatif
     */
    public function destroy($id): JsonResponse
    {
        try {
            $anggotaLegislatif = AnggotaLegislatif::findOrFail($id);

            // Check if there are volunteers associated
            if ($anggotaLegislatif->volunteers()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete Anggota Legislatif with associated volunteers. Please reassign volunteers first.'
                ], 422);
            }

            // Delete photo file if exists
            if ($anggotaLegislatif->foto_profil && Storage::disk('public')->exists($anggotaLegislatif->foto_profil)) {
                Storage::disk('public')->delete($anggotaLegislatif->foto_profil);
            }

            $anggotaLegislatif->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Anggota Legislatif deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Anggota Legislatif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all anggota legislatif for dropdown/select options
     */
    public function options(): JsonResponse
    {
        try {
            $options = AnggotaLegislatif::where('status', 'Aktif')
                                     ->select('id', 'kode_aleg', 'nama_lengkap', 'jabatan_saat_ini', 'foto_profil')
                                     ->orderBy('nama_lengkap')
                                     ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Anggota Legislatif options retrieved successfully',
                'data' => $options
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve Anggota Legislatif options',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}