<?php

return [
    'enabled' => env('CSP_ENABLED', true),

    'directives' => [
        'default-src' => ["'self'"],
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
        'style-src' => [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.googleapis.com',
            'https://cdn.jsdelivr.net',
            env('VITE_DEV_SERVER', 'http://localhost:5174'),
        ],
        'font-src' => [
            "'self'",
            'https://fonts.gstatic.com',
            'https://cdn.jsdelivr.net',
            'data:',
            env('VITE_DEV_SERVER', 'http://localhost:5174'),
        ],
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
        'connect-src' => [
            "'self'",
            'ws://*',
            'wss://*',
            'https://fcm.googleapis.com',
            'https://firebase.googleapis.com',
            'https://www.googleapis.com',
            'https://www.gstatic.com',
            'https://firebaseinstallations.googleapis.com',
            'https://fcmregistrations.googleapis.com',
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

    // For development, add more permissive rules
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
