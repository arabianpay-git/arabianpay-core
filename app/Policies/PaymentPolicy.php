<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('payment.view');
    }

    /**
     * Determine whether the user can view the payment.
     *
     * Admin and employee users with the permission can see all payments.
     * Merchants can only view payments where they are the seller.
     */
    public function view(User $user, Payment $payment): bool
    {
        if (! $user->can('payment.view')) {
            return false;
        }

        if ($user->user_type === 'admin') {
            return true;
        }

        if ($user->user_type === 'employee') {
            return true;
        }

        if ($user->user_type === 'merchant') {
            return $payment->seller_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can process the payment.
     */
    public function process(User $user, Payment $payment): bool
    {
        return $user->can('payment.process');
    }
}
