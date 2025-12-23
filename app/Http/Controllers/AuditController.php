<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogsExport;
use App\Exports\AuditTrailsExport;
use App\Models\AuditLog;
use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class AuditController extends Controller
{
    public function showAuditTrails(Request $request)
    {
        // Get filter parameters
        $filters = [
            'event_category' => $request->get('event_category'),
            'entity_type' => $request->get('entity_type'),
            'pdpl_category' => $request->get('pdpl_category'),
            'actor_email' => $request->get('actor_email'),
            'search' => $request->get('search'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        // Build query with filters
        $query = AuditTrail::query()
            ->with(['actorUser' => function ($q) {
                $q->select('id', 'email', 'first_name', 'last_name');
            }])
            ->latest('timestamp');

        // Apply filters
        if ($filters['event_category']) {
            $query->where('event_category', $filters['event_category']);
        }

        if ($filters['entity_type']) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if ($filters['pdpl_category']) {
            $query->where('pdpl_category', $filters['pdpl_category']);
        }

        if ($filters['actor_email']) {
            $query->where('actor_email', 'like', '%' . $filters['actor_email'] . '%');
        }

        if ($filters['date_from']) {
            $query->whereDate('timestamp', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('timestamp', '<=', $filters['date_to']);
        }

        if ($filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('action_summary', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('event_type', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('entity_type', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('justification', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Get filter options for dropdowns (cached for performance)
        $filterOptions = Cache::remember('audit_filter_options', 3600, function () {
            return [
                'event_categories' => AuditTrail::distinct()->pluck('event_category')->sort()->values(),
                'entity_types' => AuditTrail::distinct()->pluck('entity_type')->sort()->values(),
                'pdpl_categories' => AuditTrail::distinct()->pluck('pdpl_category')->sort()->values(),
                'actor_roles' => AuditTrail::distinct()->pluck('actor_role')->sort()->values(),
            ];
        });

        // Get statistics
        $stats = Cache::remember('audit_stats_' . md5(serialize($filters)), 300, function () use ($query) {
            $baseQuery = clone $query;
            return [
                'total_records' => $baseQuery->count(),
                'unique_users' => $baseQuery->distinct('actor_email')->count('actor_email'),
                'today_count' => AuditTrail::whereDate('timestamp', today())->count(),
                'high_sensitive_count' => $baseQuery->where('pdpl_category', 'Highly Sensitive')->count(),
            ];
        });

        // Paginate results
        $auditTrails = $query->paginate(20)
            ->through(function (AuditTrail $trail) {
                $actorName = $trail->actorUser
                    ? $trail->actorUser->first_name . ' ' . $trail->actorUser->last_name
                    : 'System';

                return [
                    'record_id' => $trail->id,
                    'timestamp' => optional($trail->timestamp)->format('Y-m-d H:i:s'),
                    'timestamp_diff' => optional($trail->timestamp)->diffForHumans(),
                    'environment' => strtoupper($trail->environment),
                    'request_id' => $trail->request_id,
                    'correlation_id' => $trail->correlation_id,

                    'actor_type' => $trail->actor_type,
                    'actor_id' => $trail->actor_id,
                    'actor_name' => $actorName,
                    'actor_email' => $this->maskEmail($trail->actor_email),
                    'actor_full_email' => $trail->actor_email,
                    'actor_role' => $trail->actor_role,

                    'ip_address' => $this->maskIp($trail->ip_address),
                    'full_ip_address' => $trail->ip_address,
                    'device_fingerprint' => $this->maskFingerprint($trail->device_fingerprint),
                    'full_device_fingerprint' => $trail->device_fingerprint,

                    'event_category' => $trail->event_category,
                    'event_type' => $trail->event_type,
                    'event_icon' => $this->getEventIcon($trail->event_category, $trail->event_type),

                    'entity_type' => $trail->entity_type,
                    'entity_id' => $trail->entity_id,

                    'action_summary' => $trail->action_summary,
                    'action_summary_short' => Str::limit($trail->action_summary, 80),

                    'before_state' => $trail->before_state,
                    'after_state' => $trail->after_state,
                    'has_changes' => !empty($trail->before_state) || !empty($trail->after_state),

                    'justification' => $trail->justification,
                    'pdpl_category' => $trail->pdpl_category,
                    'pdpl_badge_color' => $this->getPdplBadgeColor($trail->pdpl_category),
                    'pii_fields_involved' => $trail->pii_fields_involved,
                    'pii_fields_array' => is_array($trail->pii_fields_involved)
                        ? $trail->pii_fields_involved
                        : json_decode($trail->pii_fields_involved, true) ?? [],
                    'masking_state' => $trail->masking_state,
                    'masking_badge_color' => $this->getMaskingBadgeColor($trail->masking_state),

                    'properties' => $trail->properties,
                    'properties_array' => is_array($trail->properties)
                        ? $trail->properties
                        : json_decode($trail->properties, true) ?? [],

                    'created_at' => $trail->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $trail->updated_at->format('Y-m-d H:i:s'),
                ];
            });

        return view('admin.audit.trails', compact('auditTrails', 'filterOptions', 'stats', 'filters'));
    }

    public function getAuditDetails($id)
    {
        $audit = AuditTrail::with(['actorUser' => function ($q) {
            $q->select('id', 'email', 'first_name', 'last_name', 'user_type');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $audit->id,
                'timestamp' => optional($audit->timestamp)->format('Y-m-d H:i:s'),
                'environment' => strtoupper($audit->environment),
                'request_id' => $audit->request_id,
                'correlation_id' => $audit->correlation_id,

                'actor' => [
                    'type' => $audit->actor_type,
                    'id' => $audit->actor_id,
                    'name' => $audit->actorUser ? $audit->actorUser->first_name . ' ' . $audit->actorUser->last_name : 'System',
                    'email' => $this->maskEmail($audit->actor_email),
                    'full_email' => $audit->actor_email,
                    'role' => $audit->actor_role,
                ],

                'technical' => [
                    'ip_address' => $this->maskIp($audit->ip_address),
                    'full_ip' => $audit->ip_address,
                    'device_fingerprint' => $this->maskFingerprint($audit->device_fingerprint),
                    'full_device_fingerprint' => $audit->device_fingerprint,
                ],

                'event' => [
                    'category' => $audit->event_category,
                    'type' => $audit->event_type,
                    'icon' => $this->getEventIcon($audit->event_category, $audit->event_type),
                ],

                'entity' => [
                    'type' => $audit->entity_type,
                    'id' => $audit->entity_id,
                ],

                'action' => [
                    'summary' => $audit->action_summary,
                ],

                'data_changes' => [
                    'before_state' => $audit->before_state,
                    'after_state' => $audit->after_state,
                    'has_changes' => !empty($audit->before_state) || !empty($audit->after_state),
                ],

                'compliance' => [
                    'justification' => $audit->justification,
                    'pdpl_category' => $audit->pdpl_category,
                    'pdpl_color' => $this->getPdplBadgeColor($audit->pdpl_category),
                    'pii_fields' => is_array($audit->pii_fields_involved)
                        ? $audit->pii_fields_involved
                        : json_decode($audit->pii_fields_involved, true) ?? [],
                    'masking_state' => $audit->masking_state,
                    'masking_color' => $this->getMaskingBadgeColor($audit->masking_state),
                ],

                'properties' => $audit->properties,

                'timestamps' => [
                    'created_at' => $audit->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $audit->updated_at->format('Y-m-d H:i:s'),
                ],
            ],
        ]);
    }

    public function showAuditLogs(Request $request)
    {
        // Get filter parameters
        $filters = [
            'log_category' => $request->get('log_category'),
            'event_type' => $request->get('event_type'),
            'severity' => $request->get('severity'),
            'status' => $request->get('status'),
            'subject_type' => $request->get('subject_type'),
            'resource' => $request->get('resource'),
            'pdpl_category' => $request->get('pdpl_category'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];

        // Build query with filters
        $query = AuditLog::query()->latest('timestamp');

        // Apply filters
        if ($filters['log_category']) {
            $query->where('log_category', $filters['log_category']);
        }

        if ($filters['event_type']) {
            $query->where('event_type', $filters['event_type']);
        }

        if ($filters['severity']) {
            $query->where('severity', $filters['severity']);
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['subject_type']) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if ($filters['resource']) {
            $query->where('resource', $filters['resource']);
        }

        if ($filters['pdpl_category']) {
            $query->where('pdpl_category', $filters['pdpl_category']);
        }

        if ($filters['date_from']) {
            $query->whereDate('timestamp', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('timestamp', '<=', $filters['date_to']);
        }

        if ($filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('subject_identifier', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('endpoint', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('failure_reason', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('request_id', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Get filter options for dropdowns (cached for performance)
        $filterOptions = Cache::remember('audit_log_filter_options', 3600, function () {
            return [
                'log_categories' => AuditLog::distinct()->pluck('log_category')->sort()->values(),
                'event_types' => AuditLog::distinct()->pluck('event_type')->sort()->values(),
                'severities' => AuditLog::distinct()->pluck('severity')->sort()->values(),
                'statuses' => AuditLog::distinct()->pluck('status')->sort()->values(),
                'subject_types' => AuditLog::distinct()->pluck('subject_type')->sort()->values(),
                'resources' => AuditLog::distinct()->pluck('resource')->sort()->values(),
                'pdpl_categories' => AuditLog::distinct()->pluck('pdpl_category')->sort()->values(),
                'idp_providers' => AuditLog::distinct()->pluck('idp_provider')->sort()->values(),
            ];
        });

        // Get statistics
        $stats = Cache::remember('audit_log_stats_' . md5(serialize($filters)), 300, function () use ($query) {
            $baseQuery = clone $query;
            return [
                'total_records' => $baseQuery->count(),
                'critical_events' => $baseQuery->where('severity', 'Critical')->count(),
                'warning_events' => $baseQuery->where('severity', 'Warning')->count(),
                'failed_events' => $baseQuery->where('status', 'Failed')->count(),
                'auth_events' => $baseQuery->where('log_category', 'Auth')->count(),
                'high_sensitive_count' => $baseQuery->where('pdpl_category', 'Highly Sensitive')->count(),
            ];
        });

        // Paginate results
        $auditLogs = $query->paginate(20)
            ->through(function (AuditLog $log) {
                return [
                    'log_id' => $log->id,
                    'timestamp' => optional($log->timestamp)->format('Y-m-d H:i:s'),
                    'timestamp_diff' => optional($log->timestamp)->diffForHumans(),
                    'environment' => strtoupper($log->environment),

                    'log_category' => $log->log_category,
                    'log_category_icon' => $this->getLogCategoryIcon($log->log_category),
                    'log_category_color' => $this->getLogCategoryColor($log->log_category),

                    'event_type' => $log->event_type,
                    'event_type_icon' => $this->getEventTypeIcon($log->event_type),

                    'severity' => $log->severity,
                    'severity_icon' => $this->getSeverityIcon($log->severity),
                    'severity_color' => $this->getSeverityColor($log->severity),

                    'subject_type' => $log->subject_type,
                    'subject_identifier' => $this->maskEmail($log->subject_identifier),
                    'subject_name' => $this->getSubjectName($log->subject_identifier),
                    'subject_identifier_full' => $log->subject_identifier,

                    'resource' => $log->resource,
                    'endpoint' => $log->endpoint,
                    'method' => $log->method,
                    'method_color' => $this->getMethodColor($log->method),

                    'status' => $log->status,
                    'status_icon' => $this->getStatusIcon($log->status),
                    'status_color' => $this->getStatusColor($log->status),

                    'failure_reason' => $log->failure_reason,

                    'ip_address' => $this->maskIp($log->ip_address),
                    'full_ip_address' => $log->ip_address,

                    'device_fingerprint' => $this->maskFingerprint($log->device_fingerprint),
                    'full_device_fingerprint' => $log->device_fingerprint,

                    'request_id' => $log->request_id,
                    'idp_provider' => $log->idp_provider,
                    'conditional_access_result' => $log->conditional_access_result,
                    'conditional_access_color' => $this->getConditionalAccessColor($log->conditional_access_result),

                    'pdpl_category' => $log->pdpl_category,
                    'pdpl_color' => $this->getPdplBadgeColor($log->pdpl_category),

                    'pii_fields_involved' => $log->pii_fields_involved,
                    'pii_fields_array' => is_array($log->pii_fields_involved)
                        ? $log->pii_fields_involved
                        : json_decode($log->pii_fields_involved, true) ?? [],

                    'masking_state' => $log->masking_state,
                    'masking_color' => $this->getMaskingBadgeColor($log->masking_state),

                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $log->updated_at->format('Y-m-d H:i:s'),
                ];
            });

        return view('admin.audit.logs', compact('auditLogs', 'filterOptions', 'stats', 'filters'));
    }

    public function getLogDetails($id)
    {
        $log = AuditLog::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log->id,
                'timestamp' => optional($log->timestamp)->format('Y-m-d H:i:s'),
                'environment' => strtoupper($log->environment),

                'log_category' => $log->log_category,
                'log_category_icon' => $this->getLogCategoryIcon($log->log_category),
                'log_category_color' => $this->getLogCategoryColor($log->log_category),

                'event' => [
                    'type' => $log->event_type,
                    'icon' => $this->getEventTypeIcon($log->event_type),
                ],

                'severity' => [
                    'level' => $log->severity,
                    'icon' => $this->getSeverityIcon($log->severity),
                    'color' => $this->getSeverityColor($log->severity),
                ],

                'subject' => [
                    'type' => $log->subject_type,
                    'identifier' => $this->maskEmail($log->subject_identifier),
                    'full_identifier' => $log->subject_identifier,
                ],

                'resource' => [
                    'name' => $log->resource,
                    'endpoint' => $log->endpoint,
                    'method' => $log->method,
                    'method_color' => $this->getMethodColor($log->method),
                ],

                'status' => [
                    'value' => $log->status,
                    'icon' => $this->getStatusIcon($log->status),
                    'color' => $this->getStatusColor($log->status),
                    'failure_reason' => $log->failure_reason,
                ],

                'technical' => [
                    'ip_address' => $this->maskIp($log->ip_address),
                    'full_ip' => $log->ip_address,
                    'device_fingerprint' => $this->maskFingerprint($log->device_fingerprint),
                    'full_device_fingerprint' => $log->device_fingerprint,
                    'request_id' => $log->request_id,
                ],

                'authentication' => [
                    'idp_provider' => $log->idp_provider,
                    'conditional_access_result' => $log->conditional_access_result,
                    'conditional_access_color' => $this->getConditionalAccessColor($log->conditional_access_result),
                ],

                'compliance' => [
                    'pdpl_category' => $log->pdpl_category,
                    'pdpl_color' => $this->getPdplBadgeColor($log->pdpl_category),
                    'pii_fields' => is_array($log->pii_fields_involved)
                        ? $log->pii_fields_involved
                        : json_decode($log->pii_fields_involved, true) ?? [],
                    'masking_state' => $log->masking_state,
                    'masking_color' => $this->getMaskingBadgeColor($log->masking_state),
                ],

                'timestamps' => [
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $log->updated_at->format('Y-m-d H:i:s'),
                ],
            ],
        ]);
    }

    private function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email);
        return Str::substr($name, 0, 1) . '***@' . $domain;
    }

    private function maskIp(?string $ip): ?string
    {
        if (!$ip || !str_contains($ip, '.')) {
            return $ip;
        }

        $parts = explode('.', $ip);
        return $parts[0] . '.***.***.' . end($parts);
    }

    private function maskFingerprint(?string $fp): ?string
    {
        if (!$fp) {
            return null;
        }

        return 'fp-****' . Str::substr($fp, -3);
    }

    private function getLogCategoryIcon(string $category): string
    {
        return match ($category) {
            'Auth' => 'ki-user-shield',
            'Access' => 'ki-key',
            'API' => 'ki-code',
            'Security' => 'ki-shield-tick',
            'Network' => 'ki-wifi',
            'Database' => 'ki-data',
            'Application' => 'ki-app',
            default => 'ki-document',
        };
    }

    private function getLogCategoryColor(string $category): string
    {
        return match ($category) {
            'Auth' => 'text-blue-600',
            'Access' => 'text-purple-600',
            'API' => 'text-green-600',
            'Security' => 'text-red-600',
            'Network' => 'text-indigo-600',
            'Database' => 'text-amber-600',
            'Application' => 'text-cyan-600',
            default => 'text-gray-600',
        };
    }

    private function getEventTypeIcon(string $type): string
    {
        $icons = [
            'login_success' => 'ki-login',
            'login_failed' => 'ki-login-2',
            'logout' => 'ki-logout',
            'password_changed' => 'ki-password-check',
            'password_reset_requested' => 'ki-password',
            'access_granted' => 'ki-unlock',
            'access_denied' => 'ki-lock',
            'permission_changed' => 'ki-user-security',
            'role_assigned' => 'ki-user-add',
            'role_revoked' => 'ki-user-remove',
            'api_call' => 'ki-call',
            'rate_limit_exceeded' => 'ki-speedometer',
            'data_export' => 'ki-download',
            'data_access' => 'ki-eye',
            'file_upload' => 'ki-upload',
            'file_download' => 'ki-download',
            'configuration_change' => 'ki-setting-3',
            'system_error' => 'ki-danger',
            'security_alert' => 'ki-notification',
            'breach_attempt' => 'ki-shield-cross',
        ];

        return $icons[$type] ?? 'ki-document';
    }

    private function getSeverityIcon(string $severity): string
    {
        return match ($severity) {
            'Critical' => 'ki-shield-cross',
            'High' => 'ki-warning',
            'Warning' => 'ki-information-2',
            'Info' => 'ki-information',
            'Low' => 'ki-message-info',
            default => 'ki-information',
        };
    }

    private function getSeverityColor(string $severity): string
    {
        return match ($severity) {
            'Critical' => 'bg-red-100 text-red-800 border-red-200',
            'High' => 'bg-orange-100 text-orange-800 border-orange-200',
            'Warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'Info' => 'bg-blue-100 text-blue-800 border-blue-200',
            'Low' => 'bg-gray-100 text-gray-800 border-gray-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    }

    private function getStatusIcon(string $status): string
    {
        return match ($status) {
            'Success' => 'ki-verify',
            'Failed' => 'ki-close-circle',
            'Pending' => 'ki-clock',
            'In Progress' => 'ki-loading',
            'Partially Successful' => 'ki-warning',
            default => 'ki-document',
        };
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'Success' => 'text-green-600',
            'Failed' => 'text-red-600',
            'Pending' => 'text-yellow-600',
            'In Progress' => 'text-blue-600',
            'Partially Successful' => 'text-orange-600',
            default => 'text-gray-600',
        };
    }

    private function getMethodColor(string $method): string
    {
        return match ($method) {
            'GET' => 'text-green-600',
            'POST' => 'text-blue-600',
            'PUT' => 'text-yellow-600',
            'PATCH' => 'text-orange-600',
            'DELETE' => 'text-red-600',
            default => 'text-gray-600',
        };
    }

    private function getConditionalAccessColor(?string $result): string
    {
        return match ($result) {
            'granted' => 'text-green-600',
            'denied' => 'text-red-600',
            'requires_mfa' => 'text-yellow-600',
            'requires_justification' => 'text-orange-600',
            default => 'text-gray-600',
        };
    }

    private function getPdplBadgeColor(?string $category): string
    {
        return match ($category) {
            'Highly Sensitive' => 'bg-red-100 text-red-800 border-red-200',
            'Personal' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'Sensitive' => 'bg-orange-100 text-orange-800 border-orange-200',
            'Confidential' => 'bg-purple-100 text-purple-800 border-purple-200',
            'Internal' => 'bg-blue-100 text-blue-800 border-blue-200',
            'Public' => 'bg-green-100 text-green-800 border-green-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    }

    private function getMaskingBadgeColor(?string $state): string
    {
        return match ($state) {
            'Full' => 'bg-green-100 text-green-800 border-green-200',
            'Partial' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'None' => 'bg-red-100 text-red-800 border-red-200',
            'Encrypted' => 'bg-purple-100 text-purple-800 border-purple-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    }

    private function getEventIcon(string $category, string $type): string
    {
        $icons = [
            'user_management' => [
                'default' => 'ki-user',
                'user_created' => 'ki-user-add',
                'user_updated' => 'ki-user-edit',
                'user_deleted' => 'ki-user-remove',
                'user_role_updated' => 'ki-user-security',
            ],
            'data_access' => [
                'default' => 'ki-shield',
                'view' => 'ki-eye',
                'export' => 'ki-download',
                'access_granted' => 'ki-unlock',
                'access_revoked' => 'ki-lock',
            ],
            'transfer_requests' => [
                'default' => 'ki-arrows-loop',
                'created' => 'ki-send',
                'approved' => 'ki-check',
                'rejected' => 'ki-cross',
            ],
            'risk_management' => [
                'default' => 'ki-chart-line',
                'assessment' => 'ki-chart-bar',
                'update' => 'ki-chart-up',
            ],
            'communication' => [
                'default' => 'ki-messages',
                'email_sent' => 'ki-message-text',
                'sms_sent' => 'ki-message-notif',
                'notification' => 'ki-bell',
            ],
            'crud_operations' => [
                'default' => 'ki-document',
                'create' => 'ki-document-add',
                'update' => 'ki-document-edit',
                'delete' => 'ki-document-remove',
            ],
            'error_events' => [
                'default' => 'ki-danger-circle',
                'error' => 'ki-warning',
                'failed' => 'ki-close-circle',
            ],
        ];

        return $icons[$category][$type] ?? $icons[$category]['default'] ?? 'ki-document';
    }

    public function showAuditLogDetails($id)
    {
        try {
            // Find the audit log by ID
            $log = AuditLog::findOrFail($id);

            // Format the data for the modal view
            $formattedLog = [
                'success' => true,
                'data' => [
                    'log_id' => $log->id,
                    'event_type' => $log->event_type,
                    'log_category' => $log->log_category,
                    'log_category_icon' => $this->getLogCategoryIcon($log->log_category),
                    'log_category_color' => $this->getLogCategoryColor($log->log_category),
                    'severity' => $log->severity,
                    'severity_color' => $this->getSeverityColor($log->severity),
                    'description' => $log->description,
                    'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
                    'environment' => $log->environment ?? 'Production',

                    // Subject information
                    'subject_identifier' => $log->subject_identifier,
                    'subject_name' => $this->getSubjectName($log->subject_identifier),
                    'subject_type' => $log->subject_type,

                    // Resource information
                    'resource' => $log->resource,
                    'endpoint' => $log->endpoint,
                    'method' => $log->method,
                    'method_color' => $this->getMethodColor($log->method),

                    // Authentication
                    'idp_provider' => $log->idp_provider,

                    // Status
                    'status' => $log->status,
                    'status_icon' => $this->getStatusIcon($log->status),
                    'status_color' => $this->getStatusColor($log->status),
                    'failure_reason' => $log->failure_reason,

                    // Technical details
                    'ip_address' => $log->ip_address,
                    'device_fingerprint' => $log->device_fingerprint,
                    'request_id' => $log->request_id,
                    'user_agent' => $log->user_agent,

                    // Compliance
                    'pdpl_category' => $log->pdpl_category,
                    'pdpl_color' => $this->getPdplColor($log->pdpl_category),
                    'masking_state' => $log->masking_state,
                    'masking_color' => $this->getMaskingColor($log->masking_state),
                    'pii_fields_array' => $log->pii_fields ? json_decode($log->pii_fields, true) : [],

                    // Additional data
                    'geo_location' => $this->getGeoLocation($log->ip_address),
                    'session_id' => $log->session_id,
                    'conditional_access_result' => $log->conditional_access_result,
                    'conditional_access_color' => $this->getConditionalAccessColor($log->conditional_access_result),
                    'mfa_status' => $log->mfa_status,

                    // Raw data (if needed for debugging)
                    'request_data' => $log->request_data,
                    'response_data' => $log->response_data,
                    'metadata' => $log->metadata ? json_decode($log->metadata, true) : [],
                ]
            ];

            return response()->json($formattedLog);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Log entry not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to fetch log details: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load log details'
            ], 500);
        }
    }

    private function getSubjectName($identifier)
    {
        $user = User::where('id', $identifier)->select('first_name', 'last_name', 'email')->first();
        if ($user) {
            return $user;
        }
        return 'Unknown';
    }

    private function getPdplColor($category)
    {
        $colors = [
            'High Sensitivity' => 'badge-red',
            'Medium Sensitivity' => 'badge-yellow',
            'Low Sensitivity' => 'badge-green',
            'General' => 'badge-blue',
        ];

        return $colors[$category] ?? 'badge-info';
    }

    private function getMaskingColor($state)
    {
        $colors = [
            'Masked' => 'badge-success',
            'Unmasked' => 'badge-warning',
            'Partial' => 'badge-yellow',
        ];

        return $colors[$state] ?? 'badge-info';
    }

    private function getGeoLocation($ip)
    {
        // This is a simplified version. In production, you might use a geoIP service
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Localhost';
        }

        // For now, return empty. You can implement geoIP lookup here
        return null;
    }















    /**
     * Export audit logs to Excel
     */
    public function exportLogs(Request $request)
    {
        try {
            // Get filter parameters
            $filters = [
                'log_category' => $request->get('log_category'),
                'event_type' => $request->get('event_type'),
                'severity' => $request->get('severity'),
                'status' => $request->get('status'),
                'subject_type' => $request->get('subject_type'),
                'resource' => $request->get('resource'),
                'pdpl_category' => $request->get('pdpl_category'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'search' => $request->get('search'),
                'idp_provider' => $request->get('idp_provider'),
            ];

            // Build query with filters
            $query = AuditLog::query()->latest('timestamp');

            // Apply filters
            if ($filters['log_category']) {
                $query->where('log_category', $filters['log_category']);
            }

            if ($filters['event_type']) {
                $query->where('event_type', $filters['event_type']);
            }

            if ($filters['severity']) {
                $query->where('severity', $filters['severity']);
            }

            if ($filters['status']) {
                $query->where('status', $filters['status']);
            }

            if ($filters['subject_type']) {
                $query->where('subject_type', $filters['subject_type']);
            }

            if ($filters['resource']) {
                $query->where('resource', $filters['resource']);
            }

            if ($filters['pdpl_category']) {
                $query->where('pdpl_category', $filters['pdpl_category']);
            }

            if ($filters['date_from']) {
                $query->whereDate('timestamp', '>=', $filters['date_from']);
            }

            if ($filters['date_to']) {
                $query->whereDate('timestamp', '<=', $filters['date_to']);
            }

            if ($filters['idp_provider']) {
                if ($filters['idp_provider'] === '__null__') {
                    $query->whereNull('idp_provider');
                } else {
                    $query->where('idp_provider', $filters['idp_provider']);
                }
            }

            if ($filters['search']) {
                $query->where(function ($q) use ($filters) {
                    $q->where('subject_identifier', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('endpoint', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('failure_reason', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('request_id', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('description', 'like', '%' . $filters['search'] . '%');
                });
            }

            // Get total count
            $totalLogs = $query->count();

            if ($totalLogs === 0) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No logs found to export with the current filters.'
                    ], 404);
                }

                return back()->with('error', 'No logs found to export with the current filters.');
            }

            // Generate filename
            $timestamp = Carbon::now()->format('Y-m-d_His');
            $filename = "audit_logs_export_{$timestamp}.xlsx";

            // Export using Laravel Excel
            return Excel::download(new AuditLogsExport($query), $filename);
        } catch (\Exception $e) {
            Log::error('Audit logs export failed: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export failed: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
    public function exportTrails(Request $request)
    {
        try {
            // Get filter parameters (same as showAuditTrails method)
            $filters = [
                'event_category' => $request->get('event_category'),
                'entity_type' => $request->get('entity_type'),
                'pdpl_category' => $request->get('pdpl_category'),
                'actor_email' => $request->get('actor_email'),
                'search' => $request->get('search'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            // Build query with filters (reuse the same logic as showAuditTrails)
            $query = AuditTrail::query()
                ->with(['actorUser' => function ($q) {
                    $q->select('id', 'email', 'first_name', 'last_name');
                }])
                ->latest('timestamp');

            // Apply filters
            if ($filters['event_category']) {
                $query->where('event_category', $filters['event_category']);
            }

            if ($filters['entity_type']) {
                $query->where('entity_type', $filters['entity_type']);
            }

            if ($filters['pdpl_category']) {
                $query->where('pdpl_category', $filters['pdpl_category']);
            }

            if ($filters['actor_email']) {
                $query->where('actor_email', 'like', '%' . $filters['actor_email'] . '%');
            }

            if ($filters['date_from']) {
                $query->whereDate('timestamp', '>=', $filters['date_from']);
            }

            if ($filters['date_to']) {
                $query->whereDate('timestamp', '<=', $filters['date_to']);
            }

            if ($filters['search']) {
                $query->where(function ($q) use ($filters) {
                    $q->where('action_summary', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('event_type', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('entity_type', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('justification', 'like', '%' . $filters['search'] . '%');
                });
            }

            // Get the audit trails
            $auditTrails = $query->get();

            if ($auditTrails->isEmpty()) {
                return back()->with('error', 'No audit trails found to export with the current filters.');
            }

            // Generate filename
            $timestamp = Carbon::now()->format('Y-m-d_His');
            $filename = "audit_trails_export_{$timestamp}.csv";

            return Excel::download(new AuditTrailsExport($query), $filename);
        } catch (\Exception $e) {
            Log::error('Audit trails export failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
}
