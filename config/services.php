<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_translate' => [
        'key' => env('GOOGLE_TRANSLATE_API_KEY'),
    ],

    'clickpay' => [
        'profile_id'   => env('CLICKPAY_PROFILE_ID'),
        'server_key'   => env('CLICKPAY_SERVER_KEY'),
        'base_url'     => env('CLICKPAY_BASE_URL', 'https://secure.clickpay.com.sa'),
        'currency'     => env('CLICKPAY_CURRENCY', 'SAR'),
        'webhook_secret' => env('CLICKPAY_WEBHOOK_SECRET'), // [PHASE-5] For callback signature verification
    ],
    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
    ],

    // [PHASE-0] Wathq CR validation - removed hardcoded API key fallback (F-004)
    'wathq' => [
        'api_key'  => env('WATHQ_API_KEY'),
        'api_base' => env('WATHQ_API_BASE', 'https://api.wathq.sa/'),
    ],

    // [PHASE-0] SMS providers - moved hardcoded credentials to config (F-026)
    'oursms' => [
        'token' => env('OURSMS_API_TOKEN'),
        'sender' => env('OURSMS_SENDER', 'Arabianpay'),
    ],

    'msegat' => [
        'username' => env('MSEGAT_USERNAME', 'Arabianpay'),
        'api_key'  => env('MSEGAT_API_KEY'),
        'sender'   => env('MSEGAT_SENDER', 'Arabianpay'),
    ],

    // [PHASE-5] Additional integration configs — moved from env() calls in app code
    'google' => [
        'places_api_key' => env('GOOGLE_PLACE_API_KEY'),
        'reviews_api_key' => env('GOOGLE_REVIEW'),
    ],

    'nafith' => [
        'max_amount' => env('NAFITH_MAX_AMOUNT', 10000),
    ],

    'reverb' => [
        'server_port' => env('REVERB_SERVER_PORT'),
    ],

    'vite' => [
        'dev_server' => env('VITE_DEV_SERVER'),
    ],

];
