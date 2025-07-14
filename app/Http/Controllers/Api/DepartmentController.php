<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments
     */
    public function index(Request $request)
    {
        try {
            $query = Department::query();

            // Filter by active status
            if ($request->has('active')) {
                $query->where('is_active', $request->boolean('active'));
            }

            // Filter by category
            if ($request->has('category')) {
                $query->whereJsonContains('categories', $request->category);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('contact_person', 'like', "%{$search}%");
                });
            }

            $departments = $query->orderBy('name')->get();

            // Add computed attributes
            $departments->each(function ($department) {
                $department->formatted_whatsapp = $department->formatted_whatsapp;
                $department->categories_string = $department->categories_string;
            });

            return response()->json([
                'status' => 'success',
                'data' => $departments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created department
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:departments,email',
            'whatsapp' => 'required|string',
            'categories' => 'required|array|min:1',
            'categories.*' => 'required|string|in:Teknis,Pelayanan,Bantuan,Saran,Lainnya',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $department = Department::create([
                'name' => $request->name,
                'email' => $request->email,
                'whatsapp' => $request->whatsapp,
                'categories' => $request->categories,
                'is_active' => $request->boolean('is_active', true)
            ]);

            // Add computed attributes
            $department->formatted_whatsapp = $department->formatted_whatsapp;
            $department->categories_string = $department->categories_string;

            return response()->json([
                'status' => 'success',
                'message' => 'Department created successfully',
                'data' => $department
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified department
     */
    public function show($id)
    {
        try {
            $department = Department::findOrFail($id);
            
            // Add computed attributes
            $department->formatted_whatsapp = $department->formatted_whatsapp;
            $department->categories_string = $department->categories_string;

            return response()->json([
                'status' => 'success',
                'data' => $department
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified department
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:departments,email,' . $id,
            'whatsapp' => 'sometimes|required|string',
            'categories' => 'sometimes|required|array|min:1',
            'categories.*' => 'required|string|in:Teknis,Pelayanan,Bantuan,Saran,Lainnya',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $department = Department::findOrFail($id);
            
            $department->update($request->only([
                'name', 'email', 'whatsapp', 'categories', 'is_active'
            ]));

            // Add computed attributes
            $department->formatted_whatsapp = $department->formatted_whatsapp;
            $department->categories_string = $department->categories_string;

            return response()->json([
                'status' => 'success',
                'message' => 'Department updated successfully',
                'data' => $department
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified department
     */
    public function destroy($id)
    {
        try {
            $department = Department::findOrFail($id);
            $department->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Department deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department by category
     */
    public function getByCategory($category)
    {
        try {
            $department = Department::getByCategory($category);

            if (!$department) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No department found for category: {$category}"
                ], 404);
            }

            // Add computed attributes
            $department->formatted_whatsapp = $department->formatted_whatsapp;
            $department->categories_string = $department->categories_string;

            return response()->json([
                'status' => 'success',
                'data' => $department
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active departments with categories
     */
    public function getActiveWithCategories()
    {
        try {
            $departments = Department::getActiveWithCategories();

            return response()->json([
                'status' => 'success',
                'data' => $departments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle department status
     */
    public function toggleStatus($id)
    {
        try {
            $department = Department::findOrFail($id);
            $department->update([
                'is_active' => !$department->is_active
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Department status updated successfully',
                'data' => [
                    'id' => $department->id,
                    'is_active' => $department->is_active
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update department status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}