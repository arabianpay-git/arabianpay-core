<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lean Open Banking Environment
    |--------------------------------------------------------------------------
    |
    | Supported: "sandbox", "production"
    |
    */
    'environment' => env('LEAN_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | API Environments
    |--------------------------------------------------------------------------
    */
    'environments' => [
        'sandbox' => [
            'auth_url' => 'https://auth.sandbox.sa.leantech.me/oauth2/token',
            'api_url' => 'https://sandbox.sa.leantech.me',
        ],
        'production' => [
            'auth_url' => 'https://auth.sa.leantech.me/oauth2/token',
            'api_url' => 'https://api.sa.leantech.me',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    */
    'client_id' => env('LEAN_CLIENT_ID'),
    'client_secret' => env('LEAN_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'token_ttl' => 3500, // 58 minutes in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => env('LEAN_TIMEOUT', 60),
    'retry_attempts' => env('LEAN_RETRY_ATTEMPTS', 2),
];
