<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataUserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentScheduleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\WebhookController;

// Midtrans webhook - public endpoint
// Route::post('/midtrans/notification', [MidtransController::class, 'notification']);
Route::post('/midtrans/webhook', [WebhookController::class, 'handle']);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/auth/verify-whatsapp', [AuthController::class, 'verifyWhatsapp']);
    Route::post('/auth/reminder-toggle', [AuthController::class, 'toggleReminder']);
    Route::post('/auth/reminder-channels', [AuthController::class, 'updateReminderChannels']);

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

    // Company contact (admin)
    Route::get('/company-contact', [CompanyContactController::class, 'show']);
    Route::put('/company-contact', [CompanyContactController::class, 'update']);
    Route::get('/company-contact/history', [CompanyContactController::class, 'history']);

    // Dashboard aggregates
    Route::prefix('dashboard')->group(function () {
        Route::get('/user', [DashboardController::class, 'user']);
        Route::get('/admin', [DashboardController::class, 'admin']);
        Route::get('/super-admin', [DashboardController::class, 'superAdmin']);
    });
});
