<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensitiveDataApproval extends Model
{
    protected $fillable = [
        'requested_by',
        'approved_by',
        'sensitive_permissions',
        'start_at',
        'end_at',
        'status',
        'request_reason',
        'decision_notes',
        'approved_at',
    ];

    protected $casts = [
        'sensitive_permissions' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /* ================= Relationships ================= */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /* ================= Scopes ================= */

    public function scopeActive($query)
    {
        return $query
            ->where('status', 'approved')
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now());
    }
}
