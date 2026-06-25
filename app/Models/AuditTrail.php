<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditTrail extends Model
{
    protected $table = 'audit_trails';

    protected $fillable = [
        'timestamp',
        'environment',
        'request_id',
        'correlation_id',
        'actor_type',
        'actor_id',
        'actor_email',
        'actor_role',
        'ip_address',
        'device_fingerprint',
        'event_category',
        'event_type',
        'entity_type',
        'entity_id',
        'action_summary',
        'before_state',
        'after_state',
        'justification',
        'pdpl_category',
        'pii_fields_involved',
        'masking_state',
        'properties',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'before_state' => 'array',
        'after_state' => 'array',
        'properties' => 'array',
        'entity_id' => 'string',
        'pii_fields_involved' => 'array',
    ];

    public function actorUser()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'actor_id',   // FK in audit_trails
            'id'          // PK in users
        );
    }

    /**
     * Auto capture request + device context for audit trails created directly via model
     */
    protected static function booted()
    {
        static::creating(function (AuditTrail $trail) {

            if (app()->runningInConsole()) {
                return;
            }

            $request = Request::instance();

            $trail->ip_address ??= $request->ip();
            $trail->device_fingerprint ??= sha1(
                $request->userAgent().'|'.$request->ip()
            );

            /**
             * ===============================
             * ✅ FIX IS HERE
             * ===============================
             * Ensure properties is ALWAYS an array
             */
            $existingProperties = $trail->properties;

            if (is_string($existingProperties)) {
                $existingProperties = json_decode($existingProperties, true) ?? [];
            }

            if (! is_array($existingProperties)) {
                $existingProperties = [];
            }

            $trail->properties = array_merge_recursive($existingProperties, [
                'request' => [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'path' => $request->path(),
                    'query' => $request->query(),
                ],
                'device' => [
                    'user_agent' => $request->userAgent(),
                    'platform' => php_uname('s'),
                    'browser' => $request->header('sec-ch-ua'),
                    'mobile' => $request->header('sec-ch-ua-mobile'),
                ],
                'headers' => [
                    'accept' => $request->header('accept'),
                    'content_type' => $request->header('content-type'),
                    'language' => $request->header('accept-language'),
                ],
            ]);
        });
    }
}
