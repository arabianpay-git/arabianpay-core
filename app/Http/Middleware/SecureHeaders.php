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

        if (!config('csp.enabled', true)) {
            return $response;
        }

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

        // Build CSP from config
        $csp = $this->buildCsp($request);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    private function buildCsp(Request $request): string
    {
        $directives = config('csp.directives', []);
        $currentHost = $request->getHost();
        $isSecure = $request->isSecure();
        $httpProtocol = $isSecure ? 'https://' : 'http://';

        // Add dynamic domains
        $directives['img-src'][] = $httpProtocol . $currentHost;
        $directives['form-action'][] = $httpProtocol . $currentHost;

        // Explicitly allow WSS connections
        $wssUrl = 'wss://' . $currentHost . ':8080';
        if (!in_array($wssUrl, $directives['connect-src'])) {
            $directives['connect-src'][] = $wssUrl;
        }

        // Also allow HTTPS fallback
        $directives['connect-src'][] = $httpProtocol . $currentHost;

        // Development rules
        if (app()->environment('local', 'development')) {
            $devDirectives = config('csp.development', []);
            foreach ($devDirectives as $directive => $sources) {
                if (isset($directives[$directive])) {
                    $directives[$directive] = array_merge($directives[$directive], $sources);
                }
            }
        }

        // Build CSP string
        $cspParts = [];
        foreach ($directives as $directive => $sources) {
            if (!empty($sources)) {
                $cspParts[] = $directive . ' ' . implode(' ', array_unique($sources)) . ';';
            }
        }

        return implode(' ', $cspParts);
    }
}
