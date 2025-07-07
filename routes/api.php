<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\EconomicController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\BantuanSosialController;
use App\Http\Controllers\Api\PendaftaranController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes (tidak perlu login)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Public News Routes (bisa diakses tanpa login)
Route::prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index']);
    Route::get('{slug}', [NewsController::class, 'show']);
});

// Public Bantuan Sosial Routes (untuk melihat bantuan yang tersedia)
Route::prefix('bantuan-sosial')->group(function () {
    Route::get('/', [BantuanSosialController::class, 'index']);
    Route::get('{id}', [BantuanSosialController::class, 'show']);
});

// Protected Routes (perlu login)
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // Profile Routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::post('/', [ProfileController::class, 'store']);
        Route::post('photo', [ProfileController::class, 'updatePhoto']);
    });

    // Family Routes
    Route::prefix('family')->group(function () {
        Route::get('/', [FamilyController::class, 'index']);
        Route::post('/', [FamilyController::class, 'store']);
        Route::get('statistics', [FamilyController::class, 'statistics']);
        Route::get('{id}', [FamilyController::class, 'show']);
        Route::put('{id}', [FamilyController::class, 'update']);
        Route::delete('{id}', [FamilyController::class, 'destroy']);
    });

    // Economic Routes
    Route::prefix('economic')->group(function () {
        Route::get('/', [EconomicController::class, 'show']);
        Route::post('/', [EconomicController::class, 'store']);
        Route::get('analysis', [EconomicController::class, 'analysis']);
    });

    // Social Routes
    Route::prefix('social')->group(function () {
        Route::get('/', [SocialController::class, 'show']);
        Route::post('/', [SocialController::class, 'store']);
        Route::get('profile', [SocialController::class, 'profile']);
    });

    // Pendaftaran Routes (untuk user)
    Route::prefix('pendaftaran')->group(function () {
        Route::get('/', [PendaftaranController::class, 'index']);
        Route::post('/', [PendaftaranController::class, 'store']);
        Route::post('{id}/resubmit', [PendaftaranController::class, 'resubmit']);
        Route::get('statistics', [PendaftaranController::class, 'statistics']);
        Route::get('{id}', [PendaftaranController::class, 'show']);
        Route::get('{id}/dokumen/{index}', [PendaftaranController::class, 'downloadDokumen']);
    });

    // Complaint Routes (untuk user)
    Route::prefix('complaint')->group(function () {
        Route::get('/', [ComplaintController::class, 'index']);
        Route::post('/', [ComplaintController::class, 'store']);
        Route::get('dashboard', [ComplaintController::class, 'userDashboard']);
        Route::get('{id}', [ComplaintController::class, 'show']);
        Route::put('{id}', [ComplaintController::class, 'update']);
        Route::post('{id}/feedback', [ComplaintController::class, 'giveFeedback']);
    });

    // Admin Only Routes
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        
        // Admin Bantuan Sosial Routes
        Route::prefix('bantuan-sosial')->group(function () {
            Route::get('/', [BantuanSosialController::class, 'adminIndex']);
            Route::post('/', [BantuanSosialController::class, 'store']);
            Route::get('{id}', [BantuanSosialController::class, 'show']);
            Route::put('{id}', [BantuanSosialController::class, 'update']);
            Route::delete('{id}', [BantuanSosialController::class, 'destroy']);
        });

        // Admin Pendaftaran Routes
        Route::prefix('pendaftaran')->group(function () {
            Route::get('/', [PendaftaranController::class, 'adminIndex']);
            Route::get('{id}', [PendaftaranController::class, 'adminShow']);
            Route::put('{id}/status', [PendaftaranController::class, 'updateStatus']);
        });

        // Admin News Routes
        Route::prefix('news')->group(function () {
            Route::get('/', [NewsController::class, 'adminIndex']);
            Route::post('/', [NewsController::class, 'store']);
            Route::put('{id}', [NewsController::class, 'update']);
            Route::post('{id}', [NewsController::class, 'update']); // Method spoofing support
            Route::delete('{id}', [NewsController::class, 'destroy']);
            Route::patch('{id}/toggle-publish', [NewsController::class, 'togglePublish']);
        });

        // Admin Complaint Routes
        Route::prefix('complaint')->group(function () {
            Route::get('/', [ComplaintController::class, 'adminIndex']);
            Route::get('statistics', [ComplaintController::class, 'statistics']);
            Route::put('{id}/status', [ComplaintController::class, 'updateStatus']);
        });

        // Admin User Management Routes
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::get('statistics', [UserController::class, 'statistics']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('{id}', [UserController::class, 'show']);
            Route::put('{id}', [UserController::class, 'update']);
            Route::delete('{id}', [UserController::class, 'destroy']);
            Route::patch('{id}/status', [UserController::class, 'updateStatus']);
            Route::post('bulk-action', [UserController::class, 'bulkAction']);
        });

        // Admin Dashboard Routes (untuk statistik umum)
        Route::prefix('dashboard')->group(function () {
            Route::get('statistics', [DashboardController::class, 'adminStatistics']);
        });
    });
});

// Test Route
Route::get('test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working!',
        'timestamp' => now(),
    ]);
});

// Debug Route for authenticated user
Route::middleware(['auth:sanctum'])->get('debug/user', function () {
    return response()->json([
        'status' => 'success',
        'user' => request()->user(),
        'token_valid' => true,
    ]);
});

// Debug Route for admin user  
Route::middleware(['auth:sanctum', 'admin'])->get('debug/admin', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Admin access working!',
        'user' => request()->user(),
    ]);
});

// Route fallback untuk API tidak ditemukan
Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'API endpoint not found',
    ], 404);
});