<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataUserController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);

    // Admin-only user management endpoints
    Route::prefix('datausers')->group(function () {
        Route::get('/', [DataUserController::class, 'index']);
        Route::post('/', [DataUserController::class, 'store']);
        Route::get('/{id}', [DataUserController::class, 'show']);
        Route::put('/{id}', [DataUserController::class, 'update']);
        Route::delete('/{id}', [DataUserController::class, 'destroy']);
    });
});
