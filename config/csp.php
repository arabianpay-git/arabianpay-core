<?php

return [
    'enabled' => env('CSP_ENABLED', true),

    'directives' => [
        'default-src' => ["'self'"],

        // which scripts we allow to be downloaded/executed
        'script-src' => [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            'https://code.jquery.com',
            'https://cdn.jsdelivr.net',
            'https://www.gstatic.com',
            'https://www.googleapis.com',
            'https://js.pusher.com',
            env('VITE_DEV_SERVER', 'http://localhost:5174'),
        ],

        // styles
        'style-src' => [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.googleapis.com',
            'https://cdn.jsdelivr.net',
            env('VITE_DEV_SERVER', 'http://localhost:5174'),
        ],

        // fonts
        'font-src' => [
            "'self'",
            'https://fonts.gstatic.com',
            'https://cdn.jsdelivr.net',
            'data:',
            env('VITE_DEV_SERVER', 'http://localhost:5174'),
        ],

        // images
        'img-src' => [
            "'self'",
            'data:',
            'blob:',
            'https://ui-avatars.com',
            'https://cdn.salla.sa',
            'https://core.arabianpay.net',
            'https://partners.arabianpay.net',
            env('VITE_DEV_SERVER', 'http://localhost:5174'),
        ],

        // connections: XHR, fetch, websockets, eventsource, wss
        'connect-src' => [
            // Scheme tokens (allow ws/wss schemes) placed here, middleware may prepend them
            // The middleware will add wss://<currentHost> and https://<currentHost>, and cdn domains.
            "'self'",
            // Keep some Google/Firebase endpoints by default:
            'https://fcm.googleapis.com',
            'https://firebase.googleapis.com',
            'https://www.googleapis.com',
            'https://www.gstatic.com',
            'https://firebaseinstallations.googleapis.com',
            'https://fcmregistrations.googleapis.com',
            // Allow JS/Map/CDN connections (pusher + maps)
            'https://cdn.jsdelivr.net',
            'https://js.pusher.com',
            // Vite dev (if used)
            env('VITE_DEV_SERVER', 'http://localhost:5174'),
        ],

        'media-src' => [
            "'self'",
            'data:',
            'blob:',
        ],

        'frame-src' => [
            "'self'",
            'https://www.gstatic.com',
        ],

        'frame-ancestors' => ["'none'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'object-src' => ["'none'"],
        'worker-src' => ["'self'", 'blob:'],
        'child-src' => ["'self'", 'blob:'],
        'manifest-src' => ["'self'"],
    ],

    // For development, add more permissive rules (merged by middleware in local env)
    'development' => [
        'script-src' => [
            "'unsafe-inline'",
            "'unsafe-eval'",
            'http://localhost:*',
            'http://127.0.0.1:*',
        ],
        'connect-src' => [
            'ws://localhost:*',
            'wss://localhost:*',
            'ws://127.0.0.1:*',
            'wss://127.0.0.1:*',
        ],
    ],
];
