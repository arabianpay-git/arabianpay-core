<?php

namespace App\Services\Privacy;

use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Support\Facades\Log;

/**
 * [PHASE-4] Consent lifecycle management service.
 *
 * Handles consent granting, withdrawal, and status checks.
 * Consent withdrawal is recorded with timestamp, actor, and reason,
 * and prevents future processing where consent was required.
 */
class ConsentService
{
    /**
     * Record a new consent grant.
     */
    public function grantConsent(User $user, string $consentType, array $metadata = []): UserConsent
    {
        $consent = UserConsent::create([
            'user_id' => $user->id,
            'consent_type' => $consentType,
            'consent_given' => true,
            'consent_date' => now(),
            'ip_address' => request()?->ip(),
            'metadata' => $metadata,
        ]);

        Log::info('[CONSENT] Granted', [
            'user_id' => $user->id,
            'consent_type' => $consentType,
            'ip' => request()?->ip(),
        ]);

        return $consent;
    }

    /**
     * Withdraw consent. Records withdrawal timestamp and reason.
     *
     * Does NOT delete the consent record — marks it as withdrawn
     * for audit trail purposes.
     */
    public function withdrawConsent(User $user, string $consentType, ?string $reason = null, ?User $actor = null): bool
    {
        $consent = UserConsent::where('user_id', $user->id)
            ->where('consent_type', $consentType)
            ->where('consent_given', true)
            ->latest()
            ->first();

        if (! $consent) {
            Log::warning('[CONSENT] Withdrawal attempted but no active consent found', [
                'user_id' => $user->id,
                'consent_type' => $consentType,
            ]);
            return false;
        }

        $consent->update([
            'consent_given' => false,
            'withdrawn_at' => now(),
            'withdrawn_by' => $actor?->id ?? $user->id,
            'withdrawal_reason' => $reason,
        ]);

        Log::info('[CONSENT] Withdrawn', [
            'user_id' => $user->id,
            'consent_type' => $consentType,
            'reason' => $reason,
            'withdrawn_by' => $actor?->id ?? $user->id,
        ]);

        return true;
    }

    /**
     * Check if a user has active consent for a given type.
     */
    public function hasActiveConsent(User $user, string $consentType): bool
    {
        return UserConsent::where('user_id', $user->id)
            ->where('consent_type', $consentType)
            ->where('consent_given', true)
            ->exists();
    }

    /**
     * Get all active consents for a user.
     */
    public function getActiveConsents(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return UserConsent::where('user_id', $user->id)
            ->where('consent_given', true)
            ->get();
    }

    /**
     * Get consent history for a user (including withdrawals).
     */
    public function getConsentHistory(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return UserConsent::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
