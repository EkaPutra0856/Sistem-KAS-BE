<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataUserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentScheduleController;

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

    // Payments endpoints
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::patch('/{id}/status', [PaymentController::class, 'updateStatus']);
    });

    // Payment schedules (admin)
    Route::prefix('schedules')->group(function () {
        Route::get('/', [PaymentScheduleController::class, 'index']);
        Route::post('/', [PaymentScheduleController::class, 'store']);
        Route::put('/{id}', [PaymentScheduleController::class, 'update']);
        Route::delete('/{id}', [PaymentScheduleController::class, 'destroy']);
    });
});
