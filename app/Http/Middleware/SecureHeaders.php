<?php

namespace App\Http\Middleware;

use App\Support\CspNonce;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! config('csp.enabled', true)) {
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

        // XSS protection (legacy header, but harmless)
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
        // Base directives from config
        $directives = config('csp.directives', []);

        // CORE-P0-10: attach per-request nonce to script-src and style-src.
        // When CSP_NONCE_ENFORCE=true we also strip 'unsafe-inline' from
        // script-src so a mis-nonced inline script is blocked by the
        // browser. Until all inline scripts are migrated (Phase 2) the
        // default is permissive — nonce is emitted but 'unsafe-inline'
        // remains as a fallback.
        $nonce = app(CspNonce::class)->value();
        $nonceToken = "'nonce-{$nonce}'";
        $enforceNonce = filter_var(env('CSP_NONCE_ENFORCE', false), FILTER_VALIDATE_BOOLEAN);

        foreach (['script-src', 'style-src'] as $directive) {
            $sources = $directives[$directive] ?? [];
            if ($enforceNonce) {
                $sources = array_values(array_filter($sources, fn ($s) => $s !== "'unsafe-inline'" && $s !== "'unsafe-eval'"));
            }
            array_unshift($sources, $nonceToken);
            $directives[$directive] = $sources;
        }

        $currentHost = $request->getHost();                      // e.g. core.arabianpay.net
        $isSecure = $request->isSecure();                        // https?
        $httpProtocol = $isSecure ? 'https://' : 'http://';
        $httpsHost = $httpProtocol.$currentHost;               // https://core.arabianpay.net
        $wssHost = 'wss://'.$currentHost;                      // wss://core.arabianpay.net

        // If you run Reverb on a specific port (like 8080 internally), add that too (optional)
        $reverbPort = env('REVERB_SERVER_PORT', null);
        if (! empty($reverbPort) && is_numeric($reverbPort) && (int) $reverbPort !== 443) {
            $directives['connect-src'][] = 'wss://'.$currentHost.':'.$reverbPort;
        }

        // Add dynamic domains to sensible directives
        $directives['img-src'][] = $httpsHost;
        $directives['form-action'][] = $httpsHost;

        // Connect-src: allow secure websocket to this host and the HTTPS origin
        // Include scheme sources 'wss:' and 'ws:' so other valid ws/wss endpoints are allowed if needed
        $connectSrc = $directives['connect-src'] ?? [];

        // Ensure scheme tokens exist (prefer scheme tokens over ws://* / wss://*).
        array_unshift($connectSrc, 'wss:', 'ws:'); // allow websocket schemes (kept first)

        // Allow this application's domain via wss and https explicitly
        $connectSrc[] = $wssHost;
        $connectSrc[] = $httpsHost;

        // Allow CDN and pusher sources for maps / requests
        $connectSrc[] = 'https://cdn.jsdelivr.net';
        $connectSrc[] = 'https://js.pusher.com';

        // Keep other well-known Google/Firebase sources
        $connectSrc[] = 'https://fcm.googleapis.com';
        $connectSrc[] = 'https://firebase.googleapis.com';
        $connectSrc[] = 'https://www.googleapis.com';
        $connectSrc[] = 'https://www.gstatic.com';
        $connectSrc[] = 'https://firebaseinstallations.googleapis.com';
        $connectSrc[] = 'https://fcmregistrations.googleapis.com';

        // Add Vite dev server if present in env
        $viteDev = env('VITE_DEV_SERVER', null);
        if ($viteDev) {
            $connectSrc[] = $viteDev;
        }

        // Replace the connect-src directive with the cleaned one
        $directives['connect-src'] = $connectSrc;

        // For development environment, merge more permissive rules
        if (app()->environment('local', 'development')) {
            $devDirectives = config('csp.development', []);
            foreach ($devDirectives as $directive => $sources) {
                if (isset($directives[$directive])) {
                    $directives[$directive] = array_merge($directives[$directive], $sources);
                } else {
                    $directives[$directive] = $sources;
                }
            }
        }

        // Normalize and deduplicate per-directive entries, keep the order
        $cspParts = [];
        foreach ($directives as $directive => $sources) {
            if (empty($sources)) {
                continue;
            }

            // flatten and unique
            $flat = [];
            foreach ($sources as $s) {
                if (is_string($s)) {
                    $flat[] = trim($s);
                }
            }
            // unique while preserving order
            $seen = [];
            $unique = [];
            foreach ($flat as $s) {
                if ($s === '') {
                    continue;
                }
                if (! isset($seen[$s])) {
                    $seen[$s] = true;
                    $unique[] = $s;
                }
            }

            if (! empty($unique)) {
                $cspParts[] = $directive.' '.implode(' ', $unique).';';
            }
        }

        return implode(' ', $cspParts);
    }
}
