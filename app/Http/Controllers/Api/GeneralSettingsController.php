<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class GeneralSettingsController extends Controller
{
    /**
     * Get general settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = GeneralSetting::getSettings();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Settings retrieved successfully',
                'data' => $settings->getFormattedSettings()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update general settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            // Only validate fields that should be validated, ignore computed/readonly fields
            $validationData = $request->only([
                'site_name', 'site_description', 'site_url', 'admin_email',
                'contact_phone', 'address', 'organization', 'timezone', 
                'language', 'logo', 'social_media', 'additional_settings'
            ]);

            $validator = Validator::make($validationData, [
                'site_name' => 'required|string|max:255',
                'site_description' => 'nullable|string|max:1000',
                'site_url' => 'nullable|url|max:255',
                'admin_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'organization' => 'nullable|string|max:255',
                'timezone' => 'required|string|in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura',
                'language' => 'required|string|in:id,en',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // 2MB max
                'social_media' => 'nullable|array',
                'additional_settings' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                \Log::error('General Settings Validation Failed', [
                    'errors' => $validator->errors(),
                    'received_data' => $validationData,
                    'all_request_data' => $request->all()
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'debug_info' => [
                        'received_fields' => array_keys($validationData),
                        'required_fields' => ['site_name', 'timezone', 'language']
                    ]
                ], 422);
            }

            $settings = GeneralSetting::getSettings();
            // Only use validated data, exclude computed fields and logos
            $updateData = $request->only([
                'site_name', 'site_description', 'site_url', 'admin_email',
                'contact_phone', 'address', 'organization', 'timezone', 
                'language', 'social_media', 'additional_settings'
            ]);

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoFile = $request->file('logo');
                
                // Validate logo file
                if (!$logoFile->isValid()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid logo file'
                    ], 422);
                }

                // Update logo and get new path
                $logoPath = $settings->updateLogo($logoFile);
                $updateData['logo_path'] = $logoPath;
            }

            // Update settings
            $settings->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Settings updated successfully',
                'data' => $settings->fresh()->getFormattedSettings()
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error in general settings update', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Database error. Please ensure the general_settings table exists and run migration.',
                'error' => 'Database connection failed'
            ], 500);
        } catch (\Exception $e) {
            \Log::error('General error in general settings update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload logo only
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // 2MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $settings = GeneralSetting::getSettings();
            $logoFile = $request->file('logo');

            if (!$logoFile->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid logo file'
                ], 422);
            }

            // Update logo
            $logoPath = $settings->updateLogo($logoFile);

            return response()->json([
                'status' => 'success',
                'message' => 'Logo uploaded successfully',
                'data' => [
                    'logo_path' => $logoPath,
                    'logo_url' => $settings->fresh()->logo_url
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload logo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete logo
     */
    public function deleteLogo(): JsonResponse
    {
        try {
            $settings = GeneralSetting::getSettings();

            if ($settings->logo_path && Storage::disk('public')->exists($settings->logo_path)) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $settings->update(['logo_path' => null]);

            return response()->json([
                'status' => 'success',
                'message' => 'Logo deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete logo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug endpoint to see what data is received
     */
    public function debugRequest(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'debug',
            'message' => 'Debug data received',
            'data' => [
                'all_request_data' => $request->all(),
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_files' => $request->hasFile('logo'),
                'files' => $request->allFiles(),
                'only_allowed_fields' => $request->only([
                    'site_name', 'site_description', 'site_url', 'admin_email',
                    'contact_phone', 'address', 'organization', 'timezone', 
                    'language', 'logo', 'social_media', 'additional_settings'
                ]),
            ]
        ]);
    }

    /**
     * Debug storage and logo paths
     */
    public function debugStorage(): JsonResponse
    {
        try {
            $settings = GeneralSetting::getSettings();
            
            return response()->json([
                'status' => 'debug',
                'message' => 'Storage debug info',
                'data' => [
                    'storage_app_path' => storage_path('app'),
                    'storage_public_path' => storage_path('app/public'),
                    'public_path' => public_path(),
                    'storage_url' => Storage::disk('public')->url(''),
                    'logos_directory_exists' => Storage::disk('public')->exists('logos'),
                    'current_logo_path' => $settings->logo_path,
                    'current_logo_url' => $settings->logo_url,
                    'logo_file_exists' => $settings->logo_path ? Storage::disk('public')->exists($settings->logo_path) : false,
                    'storage_config' => config('filesystems.disks.public'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Storage debug failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available options for dropdowns
     */
    public function getOptions(): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'message' => 'Options retrieved successfully',
                'data' => [
                    'timezones' => GeneralSetting::getAvailableTimezones(),
                    'languages' => GeneralSetting::getAvailableLanguages()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve options',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}