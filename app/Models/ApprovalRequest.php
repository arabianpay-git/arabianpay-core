<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generic Maker-Checker approval request.
 *
 * Polymorphic — can be attached to any model (Settlement, CustomerCreditLimit,
 * RefundRequest, RiskScore, etc.) via approvable_type / approvable_id.
 */
class ApprovalRequest extends Model
{
    protected $fillable = [
        'uuid',
        'approvable_type',
        'approvable_id',
        'action_type',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'before_state',
        'after_state',
        'payload',
        'request_reason',
        'rejection_reason',
        'review_notes',
        'executed_by',
        'executed_at',
    ];

    protected $casts = [
        'status' => ApprovalStatus::class,
        'before_state' => 'array',
        'after_state' => 'array',
        'payload' => 'array',
        'reviewed_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?: (string) Str::uuid();
        });
    }

    // ── Relations ──

    public function approvable()
    {
        return $this->morphTo();
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function executor()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    // ── Scopes ──

    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::Pending);
    }

    public function scopeForEntity($query, string $type, int $id)
    {
        return $query->where('approvable_type', $type)->where('approvable_id', $id);
    }

    // ── Helpers ──

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === ApprovalStatus::Approved;
    }
}
