<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Prevent caching of sensitive pages
        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');

        // HTTP Strict Transport Security (HSTS)
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Clickjacking protection
        $response->headers->set('X-Frame-Options', 'DENY');

        // XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');

        // Feature policy (Permissions-Policy)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Content Security Policy (CSP)
        $viteDevServer = "http://adminpanel.test/:5174"; // your main vite server URL

        $csp = implode(' ', [
            "default-src 'self';",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net https://www.gstatic.com https://www.googleapis.com {$viteDevServer};",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net {$viteDevServer};",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net {$viteDevServer};",
            "img-src 'self' data: https://core.arabianpay.net https://ui-avatars.com {$viteDevServer};",
            "connect-src 'self' https://fcm.googleapis.com https://firebase.googleapis.com https://www.googleapis.com https://www.gstatic.com https://firebaseinstallations.googleapis.com https://fcmregistrations.googleapis.com ws://localhost:5174 ws://localhost:5173 wss://merchant.test:5174 wss://merchant.test:5173;",
            "frame-src https://www.gstatic.com;",
            "frame-ancestors 'none';",
            "base-uri 'self';",
            "form-action 'self';",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
