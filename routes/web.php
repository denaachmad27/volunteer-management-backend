<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Sanctum SPA Auth endpoints for web admin
Route::post('/login', [AuthController::class, 'webLogin'])->name('login');
Route::post('/logout', [AuthController::class, 'webLogout'])->name('logout');
