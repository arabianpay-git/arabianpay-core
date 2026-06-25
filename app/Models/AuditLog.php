<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'timestamp',
        'environment',
        'log_category',
        'event_type',
        'severity',
        'subject_type',
        'subject_identifier',
        'resource',
        'endpoint',
        'method',
        'status',
        'failure_reason',
        'ip_address',
        'device_fingerprint',
        'request_id',
        'idp_provider',
        'conditional_access_result',
        'pdpl_category',
        'pii_fields_involved',
        'masking_state',
        'properties',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'properties' => 'array',
    ];

    /**
     * AUTO CAPTURE REQUEST + DEVICE PROPERTIES
     */
    protected static function booted()
    {
        static::creating(function (AuditLog $log) {
            if (app()->runningInConsole()) {
                return;
            }

            $request = Request::instance();

            $log->properties = [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'referrer' => $request->headers->get('referer'),

                'device' => [
                    'platform' => php_uname('s'),
                    'browser' => $request->header('sec-ch-ua'),
                    'mobile' => $request->header('sec-ch-ua-mobile'),
                ],

                // SAFE headers only (PDPL compliant)
                'headers' => [
                    'accept' => $request->header('accept'),
                    'accept_lang' => $request->header('accept-language'),
                    'content_type' => $request->header('content-type'),
                ],
            ];
        });
    }
}
