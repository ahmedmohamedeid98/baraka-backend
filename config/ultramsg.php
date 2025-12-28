<?php

return [

    /*
    |--------------------------------------------------------------------------
    | UltraMsg WhatsApp Configuration
    |--------------------------------------------------------------------------
    |
    | Configure UltraMsg API for WhatsApp OTP and notifications
    |
    */

    'instance_id' => env('ULTRAMSG_INSTANCE_ID'),
    'token' => env('ULTRAMSG_TOKEN'),
    'enabled' => env('ULTRAMSG_ENABLED', true),
    'base_url' => 'https://api.ultramsg.com',

    /*
    |--------------------------------------------------------------------------
    | OTP Settings
    |--------------------------------------------------------------------------
    */

    'otp' => [
        'length' => env('OTP_LENGTH', 4),
        'expiry_minutes' => env('OTP_EXPIRY_MINUTES', 5),
        'rate_limit_per_hour' => env('OTP_RATE_LIMIT_PER_HOUR', 3),
    ],

];
