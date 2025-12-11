<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Support\Facades\Route;

// API v1 routes
Route::prefix('v1')->group(function () {

    // Public routes (no auth required)
    Route::post('auth/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);

    // Catalog (public)
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('vendors', [VendorController::class, 'index']);
    Route::get('vendors/{id}', [VendorController::class, 'show']);
    Route::get('vendors/{id}/products', [VendorController::class, 'products']);

    // Order tracking (public)
    Route::get('orders/{id}/tracking', [OrderController::class, 'tracking']);

    // Protected routes (auth required)
    Route::middleware('auth:sanctum')->group(function () {

        // Auth & Profile
        Route::get('me', [AuthController::class, 'me']);
        Route::put('me', [AuthController::class, 'updateProfile']);
        Route::post('me/fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Addresses
        Route::apiResource('addresses', AddressController::class);

        // Cart
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart/items', [CartController::class, 'store']);
        Route::put('cart/items/{id}', [CartController::class, 'update']);
        Route::delete('cart/items/{id}', [CartController::class, 'destroy']);
        Route::delete('cart', [CartController::class, 'clear']);

        // Coupons
        Route::post('coupons/apply', [CouponController::class, 'apply']);

        // Orders
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::post('checkout', [OrderController::class, 'checkout']);
        Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);

    });
});
