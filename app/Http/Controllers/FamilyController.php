<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FamilyController extends Controller
{
    /**
     * Display a listing of families with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Family::with('user:id,name,email');

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_anggota', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            // Apply user_id filter
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Apply hubungan filter
            if ($request->has('hubungan') && !empty($request->hubungan)) {
                $query->where('hubungan', $request->hubungan);
            }

            // Apply jenis_kelamin filter
            if ($request->has('jenis_kelamin') && !empty($request->jenis_kelamin)) {
                $query->where('jenis_kelamin', $request->jenis_kelamin);
            }

            // Apply status filter (for future use)
            if ($request->has('status') && !empty($request->status)) {
                // Add status filter logic here if needed
            }

            // Get pagination parameters
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);

            // Execute query with pagination
            $families = $query->orderBy('created_at', 'desc')
                             ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'message' => 'Families retrieved successfully',
                'data' => [
                    'data' => $families->items(),
                    'current_page' => $families->currentPage(),
                    'per_page' => $families->perPage(),
                    'total' => $families->total(),
                    'last_page' => $families->lastPage(),
                    'from' => $families->firstItem(),
                    'to' => $families->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve families',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get family statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => Family::count(),
                'laki_laki' => Family::where('jenis_kelamin', 'Laki-laki')->count(),
                'perempuan' => Family::where('jenis_kelamin', 'Perempuan')->count(),
                'tanggungan' => Family::where('tanggungan', true)->count(),
                'total_penghasilan' => Family::sum('penghasilan'),
                'avg_penghasilan' => Family::avg('penghasilan') ?? 0,
            ];

            // Add more detailed statistics
            $stats['by_hubungan'] = Family::select('hubungan', DB::raw('count(*) as count'))
                                         ->groupBy('hubungan')
                                         ->pluck('count', 'hubungan')
                                         ->toArray();

            $stats['by_pendidikan'] = Family::select('pendidikan', DB::raw('count(*) as count'))
                                           ->groupBy('pendidikan')
                                           ->pluck('count', 'pendidikan')
                                           ->toArray();

            return response()->json([
                'status' => 'success',
                'message' => 'Family statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve family statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created family member
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'nama_anggota' => 'required|string|max:255',
                'hubungan' => 'required|in:Suami,Istri,Anak,Orang Tua,Saudara,Lainnya',
                'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
                'tanggal_lahir' => 'required|date|before:today',
                'pekerjaan' => 'required|string|max:255',
                'pendidikan' => 'required|in:Tidak Sekolah,SD,SMP,SMA,D3,S1,S2,S3',
                'penghasilan' => 'required|numeric|min:0',
                'tanggungan' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $family = Family::create($request->all());
            $family->load('user:id,name,email');

            return response()->json([
                'status' => 'success',
                'message' => 'Family member created successfully',
                'data' => $family
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create family member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified family member
     */
    public function show($id): JsonResponse
    {
        try {
            $family = Family::with('user:id,name,email')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Family member retrieved successfully',
                'data' => $family
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Family member not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified family member
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $family = Family::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'user_id' => 'sometimes|required|exists:users,id',
                'nama_anggota' => 'sometimes|required|string|max:255',
                'hubungan' => 'sometimes|required|in:Suami,Istri,Anak,Orang Tua,Saudara,Lainnya',
                'jenis_kelamin' => 'sometimes|required|in:Laki-laki,Perempuan',
                'tanggal_lahir' => 'sometimes|required|date|before:today',
                'pekerjaan' => 'sometimes|required|string|max:255',
                'pendidikan' => 'sometimes|required|in:Tidak Sekolah,SD,SMP,SMA,D3,S1,S2,S3',
                'penghasilan' => 'sometimes|required|numeric|min:0',
                'tanggungan' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $family->update($request->all());
            $family->load('user:id,name,email');

            return response()->json([
                'status' => 'success',
                'message' => 'Family member updated successfully',
                'data' => $family
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update family member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified family member
     */
    public function destroy($id): JsonResponse
    {
        try {
            $family = Family::findOrFail($id);
            $family->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Family member deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete family member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get families by user ID
     */
    public function getFamiliesByUser($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $families = Family::where('user_id', $userId)
                             ->with('user:id,name,email')
                             ->orderBy('created_at', 'desc')
                             ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'User families retrieved successfully',
                'data' => $families
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve user families',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk actions for families
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:delete,export',
                'family_ids' => 'required|array|min:1',
                'family_ids.*' => 'exists:families,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $action = $request->action;
            $familyIds = $request->family_ids;

            switch ($action) {
                case 'delete':
                    $deletedCount = Family::whereIn('id', $familyIds)->delete();
                    return response()->json([
                        'status' => 'success',
                        'message' => "{$deletedCount} family members deleted successfully",
                        'data' => ['deleted_count' => $deletedCount]
                    ]);

                case 'export':
                    // Implement export logic here if needed
                    $families = Family::with('user:id,name,email')
                                     ->whereIn('id', $familyIds)
                                     ->get();
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Family data exported successfully',
                        'data' => $families
                    ]);

                default:
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid action'
                    ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to perform bulk action',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}