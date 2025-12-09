<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'broadcasting/auth',    // <<< added so the socket server can POST to /broadcasting/auth
        'broadcasting/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // for dev you can allow all, restrict in production
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // IMPORTANT: allow credentials so cookies are sent
    'supports_credentials' => true,
];
