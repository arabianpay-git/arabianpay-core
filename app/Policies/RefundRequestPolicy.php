<?php

namespace App\Policies;

use App\Models\RefundRequest;
use App\Models\User;

class RefundRequestPolicy
{
    /**
     * Determine whether the user can view any refund requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('refund.view');
    }

    /**
     * Determine whether the user can view the refund request.
     *
     * Admin and employee users with the permission can see all refund requests.
     * Merchants can only view refund requests where they are the seller.
     */
    public function view(User $user, RefundRequest $refundRequest): bool
    {
        if (! $user->can('refund.view')) {
            return false;
        }

        if ($user->user_type === 'admin') {
            return true;
        }

        if ($user->user_type === 'employee') {
            return true;
        }

        if ($user->user_type === 'merchant') {
            return $refundRequest->seller_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage the refund request.
     */
    public function manage(User $user, RefundRequest $refundRequest): bool
    {
        return $user->can('refund.manage');
    }

    /**
     * Determine whether the user can approve the refund request.
     */
    public function approve(User $user, RefundRequest $refundRequest): bool
    {
        return $user->can('refund.approve');
    }
}
