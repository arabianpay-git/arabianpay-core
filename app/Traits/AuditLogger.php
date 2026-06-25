<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Models\AuditTrail;
use Illuminate\Support\Facades\Auth;

trait AuditLogger
{
    /**
     * Create an AuditLog (system-level).
     *
     * $data can contain keys matching audit_logs table / AuditLog $fillable.
     */
    public function recordAuditLog(array $data = []): ?AuditLog
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $payload = array_merge([
            'timestamp' => now(),
            'environment' => config('app.env', 'production'),
            'log_category' => $data['log_category'] ?? $data['category'] ?? 'application',
            'event_type' => $data['event_type'] ?? ($data['action'] ?? 'unknown'),
            'severity' => $data['severity'] ?? 'Info',
            'subject_type' => $data['subject_type'] ?? ($this->resolveSubjectType() ?? 'user'),
            'subject_identifier' => $this->maskValue($data['subject_identifier'] ?? ($this->resolveSubjectIdentifier() ?? null)),
            'resource' => $data['resource'] ?? null,
            'endpoint' => $data['endpoint'] ?? request()->path(),
            'method' => $data['method'] ?? request()->method(),
            'status' => $data['status'] ?? null,
            'failure_reason' => $data['failure_reason'] ?? null,
            'ip_address' => $this->maskIp(request()->ip()),
            'device_fingerprint' => $data['device_fingerprint'] ?? sha1(request()->userAgent().'|'.request()->ip()),
            'request_id' => $data['request_id'] ?? request()->header('X-Request-Id') ?? (string) now()->timestamp,
            'idp_provider' => $data['idp_provider'] ?? null,
            'conditional_access_result' => $data['conditional_access_result'] ?? null,
            'pdpl_category' => $data['pdpl_category'] ?? null,
            'pii_fields_involved' => isset($data['pii_fields_involved']) ? implode(', ', (array) $data['pii_fields_involved']) : null,
            'masking_state' => $data['masking_state'] ?? 'Partial',
            'properties' => $data['properties'] ?? $this->buildRequestProperties(),
        ], $data);

        return AuditLog::create($payload);
    }

    /**
     * Create an AuditTrail (business-level) manually from controller.
     */
    public function recordAuditTrail(array $data = []): ?AuditTrail
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $payload = array_merge([
            'timestamp' => now(),
            'environment' => config('app.env', 'production'),
            'request_id' => $data['request_id'] ?? request()->header('X-Request-Id') ?? (string) now()->timestamp,
            'correlation_id' => $data['correlation_id'] ?? request()->header('X-Correlation-ID') ?? null,
            'actor_type' => $data['actor_type'] ?? ($this->resolveSubjectType() ?? 'user'),
            'actor_id' => $data['actor_id'] ?? Auth::id(),
            'actor_email' => $this->maskValue($data['actor_email'] ?? optional(Auth::user())->email),
            'actor_role' => $data['actor_role'] ?? null,
            'ip_address' => $this->maskIp(request()->ip()),
            'device_fingerprint' => $data['device_fingerprint'] ?? sha1(request()->userAgent().'|'.request()->ip()),
            'event_category' => $data['event_category'] ?? ($data['category'] ?? 'business'),
            'event_type' => $data['event_type'] ?? ($data['action'] ?? 'unknown'),
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'action_summary' => $data['action_summary'] ?? null,
            'before_state' => isset($data['before_state']) ? $this->maskState($data['before_state'], $data['pii_fields_involved'] ?? null) : null,
            'after_state' => isset($data['after_state']) ? $this->maskState($data['after_state'], $data['pii_fields_involved'] ?? null) : null,
            'justification' => $data['justification'] ?? null,
            'pdpl_category' => $data['pdpl_category'] ?? null,
            'pii_fields_involved' => isset($data['pii_fields_involved']) ? implode(', ', (array) $data['pii_fields_involved']) : null,
            'masking_state' => $data['masking_state'] ?? 'Full',
            'properties' => $data['properties'] ?? $this->buildRequestProperties(),
        ], $data);

        return AuditTrail::create($payload);
    }

    /**
     * Minimal helpers
     */
    protected function buildRequestProperties(): array
    {
        return [
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'path' => request()->path(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'headers' => [
                'accept' => request()->header('accept'),
                'content_type' => request()->header('content-type'),
            ],
        ];
    }

    protected function resolveSubjectType(): ?string
    {
        return Auth::check() ? (property_exists(Auth::user(), 'user_type') ? Auth::user()->user_type : 'user') : null;
    }

    protected function resolveSubjectIdentifier(): ?string
    {
        return optional(Auth::user())->email ?? null;
    }

    protected function maskValue(?string $val): ?string
    {
        if (empty($val)) {
            return null;
        }
        // mask simple emails and long strings
        if (strpos($val, '@') !== false) {
            [$local, $domain] = explode('@', $val, 2);
            $localMasked = strlen($local) > 1 ? substr($local, 0, 1).str_repeat('*', max(1, strlen($local) - 1)) : '*';

            return $localMasked.'@'.$domain;
        }
        if (strlen($val) > 8) {
            return substr($val, 0, 3).str_repeat('*', strlen($val) - 6).substr($val, -3);
        }

        return $val;
    }

    protected function maskIp(?string $ip): ?string
    {
        if (empty($ip)) {
            return null;
        }
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0].'.***.***.'.$parts[3];
        }

        return substr($ip, 0, 6).'***';
    }

    protected function maskState(array $state, $piiFields = null): array
    {
        // reuse a simple mask logic: mask keys that look like PII
        $defaultPii = ['email', 'mobile', 'phone', 'national_id', 'iban', 'bank_account', 'ssn', 'full_name', 'name', 'address', 'date_of_birth'];

        $masked = [];
        foreach ($state as $k => $v) {
            $lk = strtolower($k);
            $shouldMask = false;
            if ($piiFields && is_array($piiFields)) {
                $shouldMask = in_array($lk, array_map('strtolower', $piiFields), true);
            } else {
                $shouldMask = in_array($lk, $defaultPii, true);
            }
            if ($shouldMask && is_string($v)) {
                $masked[$k] = $this->maskValue((string) $v);
            } else {
                $masked[$k] = $v;
            }
        }

        return $masked;
    }
}
