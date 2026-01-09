<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SmartOrderController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\VendorCategoryController;
use App\Http\Controllers\Api\VendorAuthController;
use App\Http\Controllers\Api\VendorWalletController;
use App\Http\Controllers\Api\UserWalletController;
use App\Http\Controllers\Api\WalletTransferController;
use App\Http\Controllers\Api\Vendor\VendorOrderController;
use Illuminate\Support\Facades\Route;

// API v1 routes
Route::prefix('v1')->group(function () {

    // Public routes (no auth required)
    Route::post('auth/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);

    // Vendor Authentication (public)
    Route::prefix('vendor/auth')->group(function () {
        Route::post('request-otp', [VendorAuthController::class, 'requestOtp']);
        Route::post('verify-otp', [VendorAuthController::class, 'verifyOtp']);
    });

    // Catalog (public with optional auth for personalized features)
    Route::middleware('optional.auth')->group(function () {
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{id}', [CategoryController::class, 'show']);
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{id}', [ProductController::class, 'show']);
        Route::get('vendors', [VendorController::class, 'index']);
        Route::get('vendors/{id}', [VendorController::class, 'show']);
        Route::get('vendors/{id}/products', [VendorController::class, 'products']);
        Route::get('vendors/{id}/categories', [VendorCategoryController::class, 'index']);
    });

    // Areas (public)
    Route::get('areas', [AreaController::class, 'index']);

    // Payment Methods (public)
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);

    // Order tracking (public)
    Route::get('orders/{id}/tracking', [OrderController::class, 'tracking']);

    // Protected routes (auth required)
    Route::middleware('auth:sanctum')->group(function () {

        // Auth & Profile
        Route::get('me', [AuthController::class, 'me']);
        Route::put('me', [AuthController::class, 'updateProfile']);
        Route::post('me/avatar', [AuthController::class, 'updateAvatar']);
        Route::delete('me/avatar', [AuthController::class, 'deleteAvatar']);
        Route::post('me/change-phone/request', [AuthController::class, 'requestPhoneChange']);
        Route::post('me/change-phone/verify', [AuthController::class, 'verifyPhoneChange']);
        Route::post('me/fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Addresses
        Route::apiResource('addresses', AddressController::class);

        // Favorites
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites', [FavoriteController::class, 'store']);
        Route::delete('favorites', [FavoriteController::class, 'clearAll']);
        Route::delete('favorites/{product_id}', [FavoriteController::class, 'destroy']);
        Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
        Route::get('favorites/check/{product_id}', [FavoriteController::class, 'check']);

        // Cart
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart/sync', [CartController::class, 'sync']);
        Route::post('cart/items', [CartController::class, 'store']);
        Route::put('cart/items/{id}', [CartController::class, 'update']);
        Route::delete('cart/items/{id}', [CartController::class, 'destroy']);
        Route::delete('cart', [CartController::class, 'clear']);

        // Coupons
        Route::post('coupons/apply', [CouponController::class, 'apply']);
        Route::post('coupons/misapply', [CouponController::class, 'misapply']);

        // Orders
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::post('checkout', [OrderController::class, 'checkout']);
        Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);
        Route::post('orders/{id}/payment', [OrderController::class, 'updatePayment']);

        // Smart Order (AI-powered text order parsing)
        Route::post('smart-order/parse', [SmartOrderController::class, 'parse']);
        Route::get('smart-order/history', [SmartOrderController::class, 'history']);
        Route::get('smart-order/favorites', [SmartOrderController::class, 'favorites']);
        Route::get('smart-order/{id}', [SmartOrderController::class, 'show']);
        Route::put('smart-order/{id}', [SmartOrderController::class, 'update']);
        Route::delete('smart-order/{id}', [SmartOrderController::class, 'destroy']);
        Route::post('smart-order/{id}/reuse', [SmartOrderController::class, 'reuse']);

        // User Wallet
        Route::get('wallet', [UserWalletController::class, 'index']);
        Route::get('wallet/transactions', [UserWalletController::class, 'transactions']);
        
        // Wallet Transfers
        Route::prefix('wallet/transfer')->group(function () {
            Route::post('calculate-fee', [WalletTransferController::class, 'calculateFee']);
            Route::post('validate', [WalletTransferController::class, 'validate']);
            Route::post('/', [WalletTransferController::class, 'transfer'])->middleware('throttle:5,1'); // 5 per minute
            Route::get('stats', [WalletTransferController::class, 'stats']);
        });
        Route::get('wallet/transfers', [WalletTransferController::class, 'history']);
        Route::get('wallet/transfers/{id}', [WalletTransferController::class, 'show']);
    });

    // Vendor Protected Routes (vendor auth required)
    Route::prefix('vendor')->middleware('auth:sanctum')->group(function () {
        Route::get('me', [VendorAuthController::class, 'me']);
        Route::put('me', [VendorAuthController::class, 'updateProfile']);
        Route::post('fcm-token', [VendorAuthController::class, 'updateFcmToken']);
        Route::post('auth/logout', [VendorAuthController::class, 'logout']);

        // Packages (public)
        Route::get('packages', [PackageController::class, 'index']);
        Route::get('packages/{id}', [PackageController::class, 'show']);

        // Wallet & Subscriptions
        Route::get('wallet', [VendorWalletController::class, 'index']);
        Route::get('wallet/transactions', [VendorWalletController::class, 'transactions']);
        Route::post('wallet/subscribe', [VendorWalletController::class, 'subscribe']);
        Route::post('wallet/change-package', [VendorWalletController::class, 'changePackage']);
        Route::get('wallet/subscriptions', [VendorWalletController::class, 'subscriptions']);
        Route::post('wallet/subscription/toggle-auto-renew', [VendorWalletController::class, 'toggleAutoRenew']);
        Route::post('wallet/subscription/disable-auto-renew', [VendorWalletController::class, 'disableAutoRenew']);
        Route::post('wallet/subscription/enable-auto-renew', [VendorWalletController::class, 'enableAutoRenew']);
        Route::post('wallet/subscription/cancel', [VendorWalletController::class, 'cancelSubscription']);

        // Wallet Transfers (Vendors)
        Route::prefix('wallet/transfer')->group(function () {
            Route::post('calculate-fee', [WalletTransferController::class, 'calculateFee']);
            Route::post('validate', [WalletTransferController::class, 'validate']);
            Route::post('/', [WalletTransferController::class, 'transfer'])->middleware('throttle:5,1'); // 5 per minute
            Route::get('stats', [WalletTransferController::class, 'stats']);
        });
        Route::get('wallet/transfers', [WalletTransferController::class, 'history']);
        Route::get('wallet/transfers/{id}', [WalletTransferController::class, 'show']);

        // Vendor Orders
        Route::get('orders', [VendorOrderController::class, 'index']);
        Route::get('orders/statistics', [VendorOrderController::class, 'statistics']);
        Route::get('orders/{id}', [VendorOrderController::class, 'show']);
        Route::put('orders/{id}/status', [VendorOrderController::class, 'updateStatus']);
    });
});
