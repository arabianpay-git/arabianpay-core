<?php

namespace App\Policies;

use App\Models\RefundRequest;
use App\Models\User;

class RefundRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return hasSensitivePermission('refund_management');
    }

    public function view(User $user, RefundRequest $refundRequest): bool
    {
        return hasSensitivePermission('refund_management')
            || $refundRequest->assigned_to === $user->id
            || $refundRequest->seller_id === $user->id;
    }

    public function approve(User $user, RefundRequest $refundRequest): bool
    {
        return hasSensitivePermission('refund_management')
            || $refundRequest->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return hasSensitivePermission('refund_management');
    }

    public function update(User $user, RefundRequest $refundRequest): bool
    {
        return $this->approve($user, $refundRequest);
    }

    public function delete(User $user, RefundRequest $refundRequest): bool
    {
        return hasSensitivePermission('refund_management');
    }
}
