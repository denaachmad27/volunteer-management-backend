<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Register user baru
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:15',
            'anggota_legislatif_id' => 'required|exists:anggota_legislatifs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'anggota_legislatif_id' => $request->anggota_legislatif_id,
            'role' => 'user', // Default role untuk user biasa
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is not active'
            ], 401);
        }

        // Hapus token lama
        $user->tokens()->delete();

        // Buat token baru
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load anggota legislatif data jika ada
        $user->load('anggotaLegislatif');

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Web SPA login (Sanctum session cookie)
     */
    public function webLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Attempt authenticate via web guard (session based)
        if (!Auth::guard('web')->attempt($request->only('email', 'password'), false)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        $user = Auth::guard('web')->user();
        if (!$user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json([
                'status' => 'error',
                'message' => 'Account is not active'
            ], 401);
        }

        // Include related member if any
        $user->load('anggotaLegislatif');

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
            ]
        ]);
    }

    /**
     * Web SPA logout (session)
     */
    public function webLogout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get user profile
     */
    public function me(Request $request)
    {
        $user = $request->user()->load(['profile', 'economic', 'social']);

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // Hapus token lama
        $user->currentAccessToken()->delete();
        
        // Buat token baru
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Google Sign-In authentication
     * Creates or authenticates user from Google account
     */
    public function googleSignIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'google_id' => 'required|string',
            'anggota_legislatif_id' => 'nullable|exists:anggota_legislatifs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find user by email or google_id
        $user = User::where('email', $request->email)
            ->orWhere('google_id', $request->google_id)
            ->first();

        if (!$user) {
            // New user - check if anggota_legislatif_id is provided
            if (!$request->has('anggota_legislatif_id')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'data' => [
                        'is_new_user' => true,
                        'email' => $request->email,
                        'name' => $request->name,
                        'google_id' => $request->google_id,
                    ]
                ], 404);
            }

            // Create new user with selected aleg
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(uniqid()), // Random password for Google users
                'role' => 'user',
                'google_id' => $request->google_id,
                'anggota_legislatif_id' => $request->anggota_legislatif_id,
            ]);
        } else {
            // Existing user - update google_id if not set
            if (!$user->google_id) {
                $user->update(['google_id' => $request->google_id]);
            }

            // If anggota_legislatif_id provided, update it
            if ($request->has('anggota_legislatif_id') && $request->anggota_legislatif_id) {
                $user->update(['anggota_legislatif_id' => $request->anggota_legislatif_id]);
            }
        }

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is not active'
            ], 401);
        }

        // Delete old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load anggota legislatif data if exists
        $user->load('anggotaLegislatif');

        return response()->json([
            'status' => 'success',
            'message' => 'Google sign-in successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
                'is_new_user' => false,
            ]
        ]);
    }

    /**
     * Get user's selected anggota legislatif
     */
    public function getUserLegislativeMember(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->anggota_legislatif_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User has no associated legislative member'
                ], 404);
            }

            $anggotaLegislatif = $user->anggotaLegislatif;

            if (!$anggotaLegislatif) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Legislative member not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User legislative member retrieved successfully',
                'data' => $anggotaLegislatif
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve user legislative member',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
