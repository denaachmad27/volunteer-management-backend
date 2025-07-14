<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForwardingSetting;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ForwardingSettingsController extends Controller
{
    /**
     * Get all forwarding settings including departments
     */
    public function getSettings()
    {
        try {
            $settings = ForwardingSetting::getSettings();
            $departments = Department::getActiveWithCategories();

            return response()->json([
                'success' => true,
                'data' => [
                    'emailForwarding' => $settings->email_forwarding,
                    'whatsappForwarding' => $settings->whatsapp_forwarding,
                    'forwardingMode' => $settings->forwarding_mode,
                    'adminEmail' => $settings->admin_email,
                    'adminWhatsapp' => $settings->admin_whatsapp,
                    'departments' => $departments,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update forwarding settings
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emailForwarding' => 'boolean',
            'whatsappForwarding' => 'boolean',
            'forwardingMode' => 'in:auto,manual',
            'adminEmail' => 'nullable|email',
            'adminWhatsapp' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = [];
            if ($request->has('emailForwarding')) {
                $data['email_forwarding'] = $request->emailForwarding;
            }
            if ($request->has('whatsappForwarding')) {
                $data['whatsapp_forwarding'] = $request->whatsappForwarding;
            }
            if ($request->has('forwardingMode')) {
                $data['forwarding_mode'] = $request->forwardingMode;
            }
            if ($request->has('adminEmail')) {
                $data['admin_email'] = $request->adminEmail;
            }
            if ($request->has('adminWhatsapp')) {
                $data['admin_whatsapp'] = $request->adminWhatsapp;
            }

            $settings = ForwardingSetting::updateSettings($data);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all departments
     */
    public function getDepartments()
    {
        try {
            $departments = Department::getActiveWithCategories();

            return response()->json([
                'success' => true,
                'data' => $departments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get departments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new department
     */
    public function createDepartment(Request $request)
    {
        // Log input untuk debugging
        \Log::info('Create department request:', $request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255', // Ubah dari required ke nullable
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'categories' => 'nullable|array',
            'categories.*' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $department = Department::create([
                'name' => $request->name ?: 'Dinas Baru', // Default name jika kosong
                'email' => $request->email,
                'whatsapp' => $request->whatsapp,
                'categories' => $request->categories ?? [],
                'is_active' => true,
            ]);

            \Log::info('Department created successfully:', $department->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Department created successfully',
                'data' => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'email' => $department->email,
                    'whatsapp' => $department->whatsapp,
                    'categories' => $department->categories,
                    'is_active' => $department->is_active,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create department:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create department: ' . $e->getMessage(),
                'debug' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Update department
     */
    public function updateDepartment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'categories' => 'nullable|array',
            'categories.*' => 'string|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $department = Department::findOrFail($id);

            $data = [];
            if ($request->has('name')) {
                $data['name'] = $request->name;
            }
            if ($request->has('email')) {
                $data['email'] = $request->email;
            }
            if ($request->has('whatsapp')) {
                $data['whatsapp'] = $request->whatsapp;
            }
            if ($request->has('categories')) {
                $data['categories'] = $request->categories;
            }
            if ($request->has('is_active')) {
                $data['is_active'] = $request->is_active;
            }

            $department->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Department updated successfully',
                'data' => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'email' => $department->email,
                    'whatsapp' => $department->whatsapp,
                    'categories' => $department->categories,
                    'is_active' => $department->is_active,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update department: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete department
     */
    public function deleteDepartment($id)
    {
        try {
            $department = Department::findOrFail($id);
            $department->delete();

            return response()->json([
                'success' => true,
                'message' => 'Department deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete department: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get department by category
     */
    public function getDepartmentByCategory($category)
    {
        try {
            $department = Department::getByCategory($category);

            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'No department found for category: ' . $category,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'email' => $department->email,
                    'whatsapp' => $department->whatsapp,
                    'categories' => $department->categories,
                    'is_active' => $department->is_active,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get department: ' . $e->getMessage(),
            ], 500);
        }
    }
}