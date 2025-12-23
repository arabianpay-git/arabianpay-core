<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /**
     * Paths to skip (prefix matching).
     * Add any internal endpoints here to avoid excessive logging.
     */
    protected array $skipPrefixes = [
        '_debugbar',
        'telescope',
        'horizon',
        'vendor',
        'storage',
        'api/health',
        'health',
        'ping',
        'favicon.ico',
        'assets',
        'static',
        'docs',
        'sanctum',
        'api/docs',
        'api/openapi',
        'audit-logs',
    ];

    /**
     * Keys that are considered PII and will be masked when stored in properties.
     */
    protected array $piiKeys = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'password',
        'password_confirmation',
        'ssn',
        'national_id',
        'cnic',
        'iqama',
        'passport',
        'address',
        'dob',
        'date_of_birth',
        'card_number',
        'card_expiry',
        'cvv'
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip CLI/env
        if (app()->runningInConsole()) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');

        // Skip if path matches any skip prefix
        foreach ($this->skipPrefixes as $prefix) {
            if (Str::startsWith($path, trim($prefix, '/'))) {
                return $next($request);
            }
        }

        // Generate or use existing request id
        $requestId = $request->headers->get('X-Request-Id') ?? (string) Str::uuid();
        // Attach to request for downstream usage
        $request->headers->set('X-Request-Id', $requestId);

        // Let the request proceed and capture response
        /** @var Response $response */
        $response = $next($request);

        // Always set X-Request-Id on response (safe for redirects)
        try {
            $response->headers->set('X-Request-Id', $requestId);
        } catch (\Throwable $e) {
            // ignore header set failures
        }

        // Build audit data in try/catch so logging never breaks the request flow
        try {
            $status = $response->getStatusCode();

            // Determine severity (title-case to match UI)
            $severity = $this->determineSeverity($status);

            // Determine subject
            $user = Auth::user();
            $subjectType = $user ? (class_basename(get_class($user))) : 'Guest';
            $subjectIdentifier = $user
                ? (string) ($user->getAuthIdentifier() ?? $user->id ?? $user->uuid ?? '')
                : $request->ip();

            // Resource / endpoint / route
            $route = $request->route();
            $routeName = null;
            if ($route) {
                try {
                    $routeName = $route->getName() ?? $this->routeActionString($route);
                } catch (\Throwable $e) {
                    $routeName = $this->routeActionString($route);
                }
            }

            // Save the endpoint as the full request URI (includes query string)
            $endpoint = $request->getRequestUri(); // e.g. /api/v1/users?page=2

            // Detect PII in request payload and mask
            $rawPayload = $request->all();
            [$piiFields, $maskedPayload] = $this->maskPii($rawPayload);

            $pdplCategory = count($piiFields) ? 'PII' : 'Non-PII';
            $maskingState = count($piiFields) ? 'Masked' : 'None';

            // Safe headers only
            $safeHeaders = [
                'accept' => $request->header('accept'),
                'accept_language' => $request->header('accept-language'),
                'content_type' => $request->header('content-type'),
                'user_agent' => $request->userAgent(),
                'sec_ch_ua' => $request->header('sec-ch-ua'),
                'sec_ch_ua_mobile' => $request->header('sec-ch-ua-mobile'),
            ];

            // device_fingerprint heuristics: header or cookie
            $deviceFingerprint = $request->header('X-Device-Fingerprint') ?? $request->cookie('device_fp') ?? null;

            // idp_provider and conditional_access_result: capture headers if present,
            // also allow properties to provide a fallback
            $idpProvider = $request->header('X-IdP-Provider') ?? $request->header('X-Idp-Provider') ?? null;
            $conditionalAccess = $request->header('X-Conditional-Access-Result') ?? $request->header('X-Conditional-Access') ?? null;

            // Truncate long response content (for failure_reason)
            $failureReason = null;
            if ($status >= 400) {
                $content = (string) $response->getContent();
                $failureReason = Str::limit($this->stripBinary($content), 1000);
            }

            // Build properties: include masked payload, safe headers, route params (masked), query, route name
            $properties = [
                'request_id' => $requestId,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'endpoint' => $endpoint,
                'route' => $routeName,
                'query' => $request->query(),
                'route_parameters' => $this->maskPii($route ? $route->parameters() : [], false)[1],
                'body' => $maskedPayload,
                'headers' => $safeHeaders,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            // If header did not provide idp/conditional, try to fetch from properties payload
            if (!$idpProvider && isset($properties['body']['idp_provider'])) {
                $idpProvider = $properties['body']['idp_provider'];
            }
            if (!$conditionalAccess && isset($properties['body']['conditional_access_result'])) {
                $conditionalAccess = $properties['body']['conditional_access_result'];
            }

            // Prepare audit payload for DB
            $auditData = [
                'timestamp' => now(),
                'environment' => config('app.env', 'production'),
                'log_category' => 'http',
                'event_type' => strtoupper($request->method()), // e.g. GET/POST
                'severity' => $severity,
                'subject_type' => $subjectType,
                'subject_identifier' => $subjectIdentifier,
                'resource' => $routeName ?? $request->path(),
                'endpoint' => $endpoint, // reliably store request URI including query
                'method' => $request->method(),
                'status' => (string)$status,
                'failure_reason' => $failureReason,
                'ip_address' => $request->ip(),
                'device_fingerprint' => $deviceFingerprint,
                'request_id' => $requestId,
                'idp_provider' => $idpProvider,
                'conditional_access_result' => $conditionalAccess,
                'pdpl_category' => $pdplCategory,
                'pii_fields_involved' => $piiFields ? implode(',', $piiFields) : null,
                'masking_state' => $maskingState,
                'properties' => $properties,
            ];

            // Create audit record (wrapped in try/catch to avoid breaking app)
            AuditLog::create($auditData);
        } catch (\Throwable $e) {
            // Never throw from middleware; log locally only
            Log::warning('AuditLogMiddleware failed to persist audit log: ' . $e->getMessage(), [
                'request_id' => $requestId,
                'path' => $request->path(),
            ]);
        }

        return $response;
    }

    /**
     * Determine severity string from HTTP status code.
     * Return title-case values to match UI: Critical, Error, Warning, Info
     */
    protected function determineSeverity(int $status): string
    {
        if ($status >= 500) {
            return 'Critical';
        }
        if ($status >= 400) {
            return 'Error';
        }
        if ($status >= 300) {
            return 'Warning';
        }
        return 'Info';
    }

    /**
     * Create a string representing controller@action when route name not available.
     */
    protected function routeActionString($route): string
    {
        try {
            $action = $route->getActionName();
            return $action === '__closure' ? 'closure' : $action;
        } catch (\Throwable $e) {
            return 'unknown';
        }
    }

    /**
     * Mask PII values in array.
     *
     * @param array $data
     * @param bool $returnPiisAlso If true returns [$piiFields, $maskedPayload]; if false returns [$unused, $maskedPayload]
     * @return array
     */
    protected function maskPii(array $data, bool $returnPiisAlso = true): array
    {
        $masked = [];
        $piiFound = [];

        foreach ($data as $k => $v) {
            $lower = strtolower((string)$k);
            if (is_array($v)) {
                [$childPii, $childMasked] = $this->maskPii($v, true);
                if ($childPii) {
                    $piiFound = array_merge($piiFound, $childPii);
                }
                $masked[$k] = $childMasked;
                continue;
            }

            $isPii = false;
            foreach ($this->piiKeys as $piiKey) {
                if (Str::contains($lower, strtolower($piiKey))) {
                    $isPii = true;
                    break;
                }
            }

            if ($isPii) {
                $piiFound[] = $k;
                $masked[$k] = $this->maskValue((string)$v);
            } else {
                // keep short values only to avoid huge storage
                if (is_string($v) && Str::length($v) > 2000) {
                    $masked[$k] = Str::limit($v, 2000);
                } else {
                    $masked[$k] = $v;
                }
            }
        }

        $piiFound = array_values(array_unique($piiFound));

        if ($returnPiisAlso) {
            return [$piiFound, $masked];
        }

        return [[], $masked];
    }

    /**
     * Mask a single value: show only last 2 characters, rest replaced by '*'
     */
    protected function maskValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $len = mb_strlen($value);
        if ($len <= 4) {
            return str_repeat('*', max(1, $len - 1)) . mb_substr($value, -1);
        }
        $visible = 2;
        return str_repeat('*', $len - $visible) . mb_substr($value, -$visible);
    }

    /**
     * Strip binary data and control characters from response content before storing
     */
    protected function stripBinary(string $s): string
    {
        // remove binary / non-printable characters
        return preg_replace('/[^\P{C}\n\r\t]+/u', '', $s) ?? $s;
    }
}
