<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * [PHASE-4] Enhanced with consent management fields for PDPL compliance.
 *
 * Tracks consent grants, withdrawals, and history.
 */
class UserConsent extends Model
{
    protected $fillable = [
        'user_id',
        'bank_code',
        'consent_id',
        'consent_type',
        'consent_given',
        'consent_date',
        'ip_address',
        'withdrawn_at',
        'withdrawn_by',
        'withdrawal_reason',
        'metadata',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
        'consent_date' => 'datetime',
        'withdrawn_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawer()
    {
        return $this->belongsTo(User::class, 'withdrawn_by');
    }

    /**
     * Scope to active (non-withdrawn) consents.
     */
    public function scopeActive($query)
    {
        return $query->where('consent_given', true);
    }

    /**
     * Check if this consent has been withdrawn.
     */
    public function isWithdrawn(): bool
    {
        return ! $this->consent_given && $this->withdrawn_at !== null;
    }
}
