<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        'up',
        'ping',
        'favicon.ico',
        'build/assets',
        'build/static',
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
        'cvv',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');

        foreach ($this->skipPrefixes as $prefix) {
            if (Str::startsWith($path, trim($prefix, '/'))) {
                return $next($request);
            }
        }

        $requestId = $request->headers->get('X-Request-Id') ?? (string) Str::uuid();
        $request->headers->set('X-Request-Id', $requestId);

        /** @var Response $response */
        $response = $next($request);

        try {
            $response->headers->set('X-Request-Id', $requestId);
        } catch (\Throwable $e) {
        }

        try {
            $status = $response->getStatusCode();

            $severity = $this->determineSeverity($status);

            $samplingRate = (int) config('audit.sampling_rate', 100); // 100 = log all, 10 = log 10%
            if ($samplingRate < 100 && mt_rand(1, 100) > $samplingRate) {
                return $response;
            }

            $user = Auth::user();
            $subjectType = $user ? (class_basename(get_class($user))) : 'Guest';
            $subjectIdentifier = $user
                ? (string) ($user->getAuthIdentifier() ?? $user->id ?? $user->uuid ?? '')
                : $request->ip();

            $route = $request->route();
            $routeName = null;
            if ($route) {
                try {
                    $routeName = $route->getName() ?? $this->routeActionString($route);
                } catch (\Throwable $e) {
                    $routeName = $this->routeActionString($route);
                }
            }

            $endpoint = $request->getRequestUri();

            $rawPayload = $request->all();
            [$piiFields, $maskedPayload] = $this->maskPii($rawPayload);

            $pdplCategory = count($piiFields) ? 'PII' : 'Non-PII';
            $maskingState = count($piiFields) ? 'Masked' : 'None';

            $safeHeaders = [
                'accept' => $request->header('accept'),
                'accept_language' => $request->header('accept-language'),
                'content_type' => $request->header('content-type'),
                'user_agent' => $request->userAgent(),
                'sec_ch_ua' => $request->header('sec-ch-ua'),
                'sec_ch_ua_mobile' => $request->header('sec-ch-ua-mobile'),
            ];

            $deviceFingerprint = $request->header('X-Device-Fingerprint') ?? $request->cookie('device_fp') ?? null;

            $idpProvider = $request->header('X-IdP-Provider') ?? $request->header('X-Idp-Provider') ?? null;
            $conditionalAccess = $request->header('X-Conditional-Access-Result') ?? $request->header('X-Conditional-Access') ?? null;

            $failureReason = null;
            if ($status >= 400) {
                // Do not capture response bodies — they may contain PII, stack traces, or tokens.
                $failureReason = "HTTP {$status} error (response body omitted for PII safety)";
            }

            $properties = [
                'request_id' => $requestId,
                'method' => $request->method(),
                'url' => $request->url(),
                'path' => $request->path(),
                'endpoint' => $endpoint,
                'route' => $routeName,
                'query' => $request->query(),
                'route_parameters' => $this->maskPii($route ? $route->parameters() : [], false)[1],
                'query' => $this->maskPii($request->query(), false)[1],
                'body' => $maskedPayload,
                'headers' => $safeHeaders,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            if (! $idpProvider && isset($properties['body']['idp_provider'])) {
                $idpProvider = $properties['body']['idp_provider'];
            }
            if (! $conditionalAccess && isset($properties['body']['conditional_access_result'])) {
                $conditionalAccess = $properties['body']['conditional_access_result'];
            }

            $logCategory = $this->detectLogCategory($request);

            $auditData = [
                'timestamp' => now(),
                'environment' => config('app.env', 'production'),
                'log_category' => $logCategory,
                'event_type' => strtoupper($request->method()),
                'severity' => $severity,
                'subject_type' => $subjectType,
                'subject_identifier' => $subjectIdentifier,
                'resource' => $routeName ?? $request->path(),
                'endpoint' => $endpoint,
                'method' => $request->method(),
                'status' => (string) $status,
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

            AuditLog::create($auditData);
        } catch (\Throwable $e) {
            Log::warning('AuditLogMiddleware failed to persist audit log: '.$e->getMessage(), [
                'request_id' => $requestId,
                'path' => $request->path(),
            ]);
        }

        return $response;
    }

    protected function detectLogCategory(Request $request): string
    {
        if ($request->is('api/*')) {
            return 'api';
        }

        if ($request->is('login', 'logout', 'register', 'password/*', 'otp/*')) {
            return 'auth';
        }

        if ($request->expectsJson()) {
            return 'ajax';
        }

        // HTTPS
        if ($request->isSecure()) {
            return 'https';
        }

        // HTTP (explicit)
        if ($request->getScheme() === 'http') {
            return 'http';
        }

        return 'web';
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
     * @param  bool  $returnPiisAlso  If true returns [$piiFields, $maskedPayload]; if false returns [$unused, $maskedPayload]
     */
    protected function maskPii(array $data, bool $returnPiisAlso = true): array
    {
        $masked = [];
        $piiFound = [];

        foreach ($data as $k => $v) {
            $lower = strtolower((string) $k);
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
                if ($lower === strtolower($piiKey)) {
                    $isPii = true;
                    break;
                }
            }

            if ($isPii) {
                $piiFound[] = $k;
                $masked[$k] = $this->maskValue((string) $v);
            } else {
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
            return str_repeat('*', max(1, $len - 1)).mb_substr($value, -1);
        }
        $visible = 2;

        return str_repeat('*', $len - $visible).mb_substr($value, -$visible);
    }

    /**
     * Strip binary data and control characters from response content before storing
     */
    protected function stripBinary(string $s): string
    {
        return preg_replace('/[^\P{C}\n\r\t]+/u', '', $s) ?? $s;
    }

    /**
     * Mask PII values embedded in a plain text string (e.g. response body or query string).
     */
    protected function maskPiiString(string $text): string
    {
        foreach ($this->piiKeys as $piiKey) {
            // Match JSON/query-style "key":"value" or key=value
            $text = preg_replace(
                '/(["\']?'.preg_quote($piiKey, '/').'["\']?\s*[:=]\s*["\'])([^"\']+)(["\'])/i',
                '$1***$3',
                $text
            );
        }

        return $text;
    }
}
