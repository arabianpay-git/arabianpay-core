<?php


return [
    // SIMAH base url (UAT/PROD) - keep without trailing slash
    'base_url' => env('SIMAH_BASE_URL', 'https://spapiuat.simah.com'),


    // credentials used to call the login endpoint
    'username' => env('SIMAH_USERNAME', 'arayadmin'),
    'password' => env('SIMAH_PASSWORD', 'arayadmin_123'),


    // HTTP timeout (seconds)
    'timeout' => env('SIMAH_TIMEOUT', 60),


    // How long to cache the token (seconds). Set slightly lower than token expiry.
    'cache_token_ttl' => env('SIMAH_TOKEN_TTL', 3500),


    // Cache key to store the token
    'cache_key' => env('SIMAH_CACHE_KEY', 'simah_token'),
];
