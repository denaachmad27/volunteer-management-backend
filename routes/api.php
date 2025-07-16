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
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\ForwardingSettingsController;
use App\Http\Controllers\Api\WhatsappController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\GeneralSettingsController;
use App\Http\Controllers\VolunteerController;

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

        // Admin Family Management Routes
        Route::prefix('families')->group(function () {
            Route::get('/', [\App\Http\Controllers\FamilyController::class, 'index']);
            Route::get('statistics', [\App\Http\Controllers\FamilyController::class, 'statistics']);
            Route::post('/', [\App\Http\Controllers\FamilyController::class, 'store']);
            Route::get('user/{userId}', [\App\Http\Controllers\FamilyController::class, 'getFamiliesByUser']);
            Route::get('{id}', [\App\Http\Controllers\FamilyController::class, 'show']);
            Route::put('{id}', [\App\Http\Controllers\FamilyController::class, 'update']);
            Route::delete('{id}', [\App\Http\Controllers\FamilyController::class, 'destroy']);
            Route::post('bulk-action', [\App\Http\Controllers\FamilyController::class, 'bulkAction']);
        });

        // Admin Volunteer Management Routes
        Route::prefix('volunteers')->group(function () {
            Route::get('/', [VolunteerController::class, 'index']);
            Route::get('statistics', [VolunteerController::class, 'statistics']);
            Route::get('incomplete-profiles', [VolunteerController::class, 'incompleteProfiles']);
            Route::get('{id}', [VolunteerController::class, 'show']);
            Route::patch('{id}/status', [VolunteerController::class, 'updateStatus']);
            Route::delete('{id}', [VolunteerController::class, 'destroy']);
        });

        // Admin Dashboard Routes (untuk statistik umum)
        Route::prefix('dashboard')->group(function () {
            Route::get('statistics', [DashboardController::class, 'adminStatistics']);
        });

        // Admin Email Routes
        Route::prefix('email')->group(function () {
            Route::post('send-complaint', [EmailController::class, 'sendComplaintEmail']);
            Route::post('send-test', [EmailController::class, 'sendTestEmail']);
            Route::get('status', [EmailController::class, 'getEmailStatus']);
        });

        // Admin Forwarding Settings Routes
        Route::prefix('forwarding')->group(function () {
            Route::get('settings', [ForwardingSettingsController::class, 'getSettings']);
            Route::put('settings', [ForwardingSettingsController::class, 'updateSettings']);
            Route::get('departments', [ForwardingSettingsController::class, 'getDepartments']);
            Route::post('departments', [ForwardingSettingsController::class, 'createDepartment']);
            Route::put('departments/{id}', [ForwardingSettingsController::class, 'updateDepartment']);
            Route::delete('departments/{id}', [ForwardingSettingsController::class, 'deleteDepartment']);
            Route::get('departments/category/{category}', [ForwardingSettingsController::class, 'getDepartmentByCategory']);
        });

        // Admin WhatsApp Routes
        Route::prefix('whatsapp')->group(function () {
            Route::get('settings', [WhatsappController::class, 'getSettings']);
            Route::put('settings', [WhatsappController::class, 'updateSettings']);
            Route::get('qr-code', [WhatsappController::class, 'getQRCode']);
            Route::post('initialize', [WhatsappController::class, 'initializeSession']);
            Route::post('disconnect', [WhatsappController::class, 'disconnect']);
            Route::post('test-connection', [WhatsappController::class, 'testConnection']);
            Route::post('send/{complaintId}', [WhatsappController::class, 'sendToDepartment']);
        });

        // Admin Department Routes
        Route::prefix('departments')->group(function () {
            Route::get('/', [DepartmentController::class, 'index']);
            Route::post('/', [DepartmentController::class, 'store']);
            Route::get('active-with-categories', [DepartmentController::class, 'getActiveWithCategories']);
            Route::get('category/{category}', [DepartmentController::class, 'getByCategory']);
            Route::get('{id}', [DepartmentController::class, 'show']);
            Route::put('{id}', [DepartmentController::class, 'update']);
            Route::delete('{id}', [DepartmentController::class, 'destroy']);
            Route::patch('{id}/toggle-status', [DepartmentController::class, 'toggleStatus']);
        });

        // Admin General Settings Routes
        Route::prefix('general')->group(function () {
            Route::get('settings', [GeneralSettingsController::class, 'getSettings']);
            Route::put('settings', [GeneralSettingsController::class, 'updateSettings']);
            Route::post('settings', [GeneralSettingsController::class, 'updateSettings']); // Method spoofing support
            Route::post('debug', [GeneralSettingsController::class, 'debugRequest']); // Debug endpoint
            Route::get('debug-storage', [GeneralSettingsController::class, 'debugStorage']); // Storage debug
            Route::post('logo', [GeneralSettingsController::class, 'uploadLogo']);
            Route::delete('logo', [GeneralSettingsController::class, 'deleteLogo']);
            Route::get('options', [GeneralSettingsController::class, 'getOptions']);
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