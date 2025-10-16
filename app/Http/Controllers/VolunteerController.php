<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profile;
use App\Models\Family;
use App\Models\Economic;
use App\Models\Social;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VolunteerController extends Controller
{
    /**
     * Display a listing of volunteers with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['profile', 'families', 'economic', 'social'])
                         // Support both legacy 'user' and new 'relawan' roles
                         ->whereIn('role', ['user', 'relawan']); // Only volunteers, not admins

            // Scope by aleg for admin_aleg
            $authUser = $request->user();
            if (method_exists($authUser, 'isAdminAleg') && $authUser->isAdminAleg() && $authUser->anggota_legislatif_id) {
                $query->where('anggota_legislatif_id', $authUser->anggota_legislatif_id);
            }

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhereHas('profile', function ($profileQuery) use ($search) {
                          $profileQuery->where('nama_lengkap', 'like', "%{$search}%")
                                      ->orWhere('nik', 'like', "%{$search}%")
                                      ->orWhere('alamat', 'like', "%{$search}%");
                      });
                });
            }

            // Apply filters
            if ($request->has('status') && !empty($request->status)) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            if ($request->has('profile_complete') && !empty($request->profile_complete)) {
                if ($request->profile_complete === 'complete') {
                    $query->whereHas('profile')
                          ->whereHas('economic')
                          ->whereHas('social');
                } elseif ($request->profile_complete === 'incomplete') {
                    $query->where(function ($q) {
                        $q->whereDoesntHave('profile')
                          ->orWhereDoesntHave('economic')
                          ->orWhereDoesntHave('social');
                    });
                }
            }

            if ($request->has('city') && !empty($request->city)) {
                $query->whereHas('profile', function ($profileQuery) use ($request) {
                    $profileQuery->where('kota', 'like', "%{$request->city}%");
                });
            }

            // Get pagination parameters
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);

            // Execute query with pagination
            $volunteers = $query->orderBy('created_at', 'desc')
                               ->paginate($perPage, ['*'], 'page', $page);

            // Add completion status to each volunteer
            $volunteers->getCollection()->transform(function ($volunteer) {
                $volunteer->profile_completion = $this->calculateProfileCompletion($volunteer);
                return $volunteer;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Volunteers retrieved successfully',
                'data' => [
                    'data' => $volunteers->items(),
                    'current_page' => $volunteers->currentPage(),
                    'per_page' => $volunteers->perPage(),
                    'total' => $volunteers->total(),
                    'last_page' => $volunteers->lastPage(),
                    'from' => $volunteers->firstItem(),
                    'to' => $volunteers->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve volunteers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get volunteer statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $baseQuery = User::where('role', 'user');
            // Scope by aleg for admin_aleg
            $authUser = $request->user();
            if (method_exists($authUser, 'isAdminAleg') && $authUser->isAdminAleg() && $authUser->anggota_legislatif_id) {
                $baseQuery->where('anggota_legislatif_id', $authUser->anggota_legislatif_id);
            }

            $totalVolunteers = (clone $baseQuery)->count();
            $activeVolunteers = (clone $baseQuery)->where('is_active', true)->count();
            $inactiveVolunteers = (clone $baseQuery)->where('is_active', false)->count();

            // Profile completion statistics
            $completeProfilesQuery = User::where('role', 'user')
                                      ->whereHas('profile')
                                      ->whereHas('economic')
                                      ->whereHas('social');
            if (isset($authUser) && method_exists($authUser, 'isAdminAleg') && $authUser->isAdminAleg() && $authUser->anggota_legislatif_id) {
                $completeProfilesQuery->where('anggota_legislatif_id', $authUser->anggota_legislatif_id);
            }
            $completeProfiles = $completeProfilesQuery->count();

            $incompleteProfiles = $totalVolunteers - $completeProfiles;

            // Gender distribution
            $genderStats = Profile::select('jenis_kelamin', DB::raw('count(*) as count'))
                                 ->whereHas('user', function ($q) use ($authUser) {
                                     $q->where('role', 'user');
                                     if (isset($authUser) && method_exists($authUser, 'isAdminAleg') && $authUser->isAdminAleg() && $authUser->anggota_legislatif_id) {
                                         $q->where('anggota_legislatif_id', $authUser->anggota_legislatif_id);
                                     }
                                 })
                                 ->groupBy('jenis_kelamin')
                                 ->pluck('count', 'jenis_kelamin')
                                 ->toArray();

            // Age distribution
            $ageStats = Profile::selectRaw('
                                CASE 
                                    WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) < 25 THEN "< 25"
                                    WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 25 AND 35 THEN "25-35"
                                    WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 36 AND 45 THEN "36-45"
                                    WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 46 AND 55 THEN "46-55"
                                    ELSE "> 55"
                                END as age_group,
                                count(*) as count
                            ')
                           ->whereHas('user', function ($q) use ($authUser) {
                               $q->where('role', 'user');
                               if (isset($authUser) && method_exists($authUser, 'isAdminAleg') && $authUser->isAdminAleg() && $authUser->anggota_legislatif_id) {
                                   $q->where('anggota_legislatif_id', $authUser->anggota_legislatif_id);
                               }
                           })
                           ->groupBy(DB::raw('age_group'))
                           ->pluck('count', 'age_group')
                           ->toArray();

            // City distribution (top 10)
            $cityStats = Profile::select('kota', DB::raw('count(*) as count'))
                               ->whereHas('user', function ($q) use ($authUser) {
                                   $q->where('role', 'user');
                                   if (isset($authUser) && method_exists($authUser, 'isAdminAleg') && $authUser->isAdminAleg() && $authUser->anggota_legislatif_id) {
                                       $q->where('anggota_legislatif_id', $authUser->anggota_legislatif_id);
                                   }
                               })
                               ->groupBy('kota')
                               ->orderBy('count', 'desc')
                               ->limit(10)
                               ->pluck('count', 'kota')
                               ->toArray();

            // Economic status
            $economicStats = Economic::selectRaw('
                                        CASE 
                                            WHEN (penghasilan_bulanan - pengeluaran_bulanan) > 0 THEN "surplus"
                                            WHEN (penghasilan_bulanan - pengeluaran_bulanan) = 0 THEN "seimbang"
                                            ELSE "defisit"
                                        END as status,
                                        count(*) as count
                                    ')
                                  ->whereHas('user', function ($q) use ($authUser) {
                                      $q->where('role', 'user');
                                      if (isset($authUser) && method_exists($authUser, 'isAdminAleg') && $authUser->isAdminAleg() && $authUser->anggota_legislatif_id) {
                                          $q->where('anggota_legislatif_id', $authUser->anggota_legislatif_id);
                                      }
                                  })
                                  ->groupBy(DB::raw('status'))
                                  ->pluck('count', 'status')
                                  ->toArray();

            $stats = [
                'total_volunteers' => $totalVolunteers,
                'active_volunteers' => $activeVolunteers,
                'inactive_volunteers' => $inactiveVolunteers,
                'complete_profiles' => $completeProfiles,
                'incomplete_profiles' => $incompleteProfiles,
                'completion_percentage' => $totalVolunteers > 0 ? round(($completeProfiles / $totalVolunteers) * 100, 2) : 0,
                'gender_distribution' => $genderStats,
                'age_distribution' => $ageStats,
                'city_distribution' => $cityStats,
                'economic_status' => $economicStats,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Volunteer statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve volunteer statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified volunteer with complete profile
     */
    public function show($id): JsonResponse
    {
        try {
            $volunteer = User::with([
                'profile',
                'families' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'economic',
                'social'
            ])->where('role', 'user')->findOrFail($id);

            // Add profile completion data
            $volunteer->profile_completion = $this->calculateProfileCompletion($volunteer);

            return response()->json([
                'status' => 'success',
                'message' => 'Volunteer retrieved successfully',
                'data' => $volunteer
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Volunteer not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update volunteer status (active/inactive)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $volunteer = User::whereIn('role', ['user', 'relawan'])->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $volunteer->update(['is_active' => $request->is_active]);

            return response()->json([
                'status' => 'success',
                'message' => 'Volunteer status updated successfully',
                'data' => $volunteer
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update volunteer status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete volunteer (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $volunteer = User::whereIn('role', ['user', 'relawan'])->findOrFail($id);
            
            // Delete related data
            DB::transaction(function () use ($volunteer) {
                $volunteer->profile()->delete();
                $volunteer->families()->delete();
                $volunteer->economic()->delete();
                $volunteer->social()->delete();
                $volunteer->delete();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Volunteer deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete volunteer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get volunteers with incomplete profiles
     */
    public function incompleteProfiles(Request $request): JsonResponse
    {
        try {
            $query = User::with(['profile', 'economic', 'social'])
                             ->whereIn('role', ['user', 'relawan'])
                             ->where(function ($q) {
                                 $q->whereDoesntHave('profile')
                                   ->orWhereDoesntHave('economic')
                                   ->orWhereDoesntHave('social');
                             })
                             ->orderBy('created_at', 'desc');

            // Scope by aleg for admin_aleg
            $authUser = $request->user();
            if (method_exists($authUser, 'isAdminAleg') && $authUser->isAdminAleg() && $authUser->anggota_legislatif_id) {
                $query->where('anggota_legislatif_id', $authUser->anggota_legislatif_id);
            }

            $volunteers = $query->get();

            // Add completion details
            $volunteers->transform(function ($volunteer) {
                $volunteer->missing_sections = [];
                if (!$volunteer->profile) $volunteer->missing_sections[] = 'profile';
                if (!$volunteer->economic) $volunteer->missing_sections[] = 'economic';
                if (!$volunteer->social) $volunteer->missing_sections[] = 'social';
                return $volunteer;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Volunteers with incomplete profiles retrieved successfully',
                'data' => $volunteers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve volunteers with incomplete profiles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion($volunteer): array
    {
        $sections = [
            'profile' => $volunteer->profile ? true : false,
            'family' => $volunteer->families->count() > 0,
            'economic' => $volunteer->economic ? true : false,
            'social' => $volunteer->social ? true : false,
        ];

        $completedSections = array_filter($sections);
        $totalSections = count($sections);
        $completedCount = count($completedSections);
        $percentage = $totalSections > 0 ? round(($completedCount / $totalSections) * 100, 2) : 0;

        return [
            'sections' => $sections,
            'completed_count' => $completedCount,
            'total_count' => $totalSections,
            'percentage' => $percentage,
            'is_complete' => $percentage === 100.0
        ];
    }
}
