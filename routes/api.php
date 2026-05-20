<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;

Route::prefix('v1')->group(function () {
    // Public Routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('forgot-password/verify', [AuthController::class, 'verifyResetOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('verify-email', [AuthController::class, 'verifyEmail']);
    
    // Social Login
    Route::get('auth/google', [\App\Http\Controllers\Api\SocialAuthController::class, 'redirectToGoogle']);
    Route::get('auth/google/callback', [\App\Http\Controllers\Api\SocialAuthController::class, 'handleGoogleCallback']);
    
    // Menu Discovery
    Route::get('categories', [MenuController::class, 'categories']);
    Route::get('menu', [MenuController::class, 'index']);

    // Protected Routes
    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile/update', [AuthController::class, 'updateProfile']);
        Route::post('profile/upload-avatar', [AuthController::class, 'uploadAvatar']);
        Route::post('logout', [AuthController::class, 'logout']);
        
        // Notifications
        Route::get('notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::put('notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::post('notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);

        // Order Archiving
        Route::get('orders/archived', [OrderController::class, 'getArchivedOrders']);
        Route::post('orders/{id}/archive', [OrderController::class, 'archive']);
        Route::post('orders/{id}/restore', [OrderController::class, 'restore']);
        Route::delete('orders/{id}/delete', [OrderController::class, 'forceDelete']);

        // Student Routes
        Route::middleware('role:student')->group(function () {
            Route::post('orders', [OrderController::class, 'store']);
            Route::get('orders/history', [OrderController::class, 'history']);
            Route::get('orders/{id}', [OrderController::class, 'show']);
        });

        // Admin Routes
        Route::middleware('role:admin')->group(function () {
            Route::post('menu/store', [MenuController::class, 'store']); // Placeholder
            Route::put('menu/{id}/update', [MenuController::class, 'update']); // Placeholder
            Route::delete('menu/{id}/delete', [MenuController::class, 'destroy']); // Placeholder
            Route::get('admin/orders', [OrderController::class, 'index']); // Placeholder
            Route::put('orders/{id}/status', [OrderController::class, 'updateStatus']); // Placeholder
        });
        
        // Payments
        Route::post('payments/create-intent', [PaymentController::class, 'createPaymentIntent']);
    });
});
