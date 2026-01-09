<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    */

    'version' => env('API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => env('API_RATE_LIMIT', 60),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (in seconds)
    |--------------------------------------------------------------------------
    */

    'cache_ttl' => [
        'categories' => 3600, // 1 hour
        'products' => 1800, // 30 minutes
        'vendors' => 3600, // 1 hour
        'banners' => 7200, // 2 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Transfer Settings
    |--------------------------------------------------------------------------
    */

    'wallet_transfer' => [
        // Transfer fee as percentage (e.g., 2 = 2%)
        'fee_percentage' => env('WALLET_TRANSFER_FEE_PERCENTAGE', 1),
        
        // Fixed transfer fee (in EGP)
        'fee_fixed' => env('WALLET_TRANSFER_FEE_FIXED', 0),
        
        // Minimum transfer amount
        'min_amount' => env('WALLET_TRANSFER_MIN_AMOUNT', 10),
        
        // Maximum transfer amount per transaction
        'max_amount' => env('WALLET_TRANSFER_MAX_AMOUNT', 10000),
        
        // Daily transfer limit per user
        'daily_limit' => env('WALLET_TRANSFER_DAILY_LIMIT', 50000),
        
        // Maximum number of transfers per day per user
        'daily_count_limit' => env('WALLET_TRANSFER_DAILY_COUNT_LIMIT', 20),
        
        // Enable/disable transfers
        'enabled' => env('WALLET_TRANSFER_ENABLED', true),
        
        // Require phone verification for transfers
        'require_verification' => env('WALLET_TRANSFER_REQUIRE_VERIFICATION', true),
        
        // Auto-flag suspicious transfers for review
        'auto_flag_suspicious' => env('WALLET_TRANSFER_AUTO_FLAG', true),
        
        // Threshold for auto-flagging (in EGP)
        'suspicious_amount_threshold' => env('WALLET_TRANSFER_SUSPICIOUS_THRESHOLD', 5000),
    ],

];
