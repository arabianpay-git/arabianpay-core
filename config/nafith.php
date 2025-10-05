<?php

return [
    'base_url' => env('NAFITH_BASE_URL', 'https://sandbox.nafith.sa'),
    'auth_url' => env('NAFITH_AUTH_URL', 'https://sandbox.nafith.sa/api/oauth/token/'),

    'credentials' => [
        'client_id' => env('NAFITH_CLIENT_ID'),
        'client_secret' => env('NAFITH_CLIENT_SECRET'),
        'auth_basic_token' => env('NAFITH_AUTH_BASIC_TOKEN'),
        'sign_secret' => env('NAFITH_SIGN_SECRET'),
    ],

    'endpoints' => [
        'auth' => '/api/oauth/token/',
        'sanad_group' => '/api/sanad-group/',
    ],

    'defaults' => [
        'scope' => 'read write',
        'grant_type' => 'client_credentials',
        'currency' => 'SAR',
        'max_approve_duration' => 100,
    ],
];
