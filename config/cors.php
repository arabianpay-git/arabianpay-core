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

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // SAMA Compliance: CORS origins must be explicitly listed when supports_credentials is true.
    // Never use wildcard '*' with credentials — this enables cross-origin authenticated requests from any site.
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'X-CSRF-TOKEN', 'X-Socket-Id'],

    'exposed_headers' => [],

    // IMPORTANT: allow credentials so cookies are sent (requires explicit allowed_origins — never use '*')
    'supports_credentials' => true,
];
