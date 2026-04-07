<?php

namespace App\Policies;

use App\Models\SchedulePayment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SchedulePaymentPolicy
{
    /**
     * Determine whether the user can view any schedule payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('schedule_payment.view');
    }

    /**
     * Determine whether the user can view the schedule payment.
     *
     * Admin and employee users with the permission can see all schedule payments.
     * Merchants can only view schedule payments where they are the seller.
     */
    public function view(User $user, SchedulePayment $schedulePayment): bool
    {
        if (! $user->can('schedule_payment.view')) {
            return false;
        }

        if ($user->user_type === 'admin') {
            return true;
        }

        if ($user->user_type === 'employee') {
            return true;
        }

        if ($user->user_type === 'merchant') {
            return $schedulePayment->seller_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can update the schedule payment.
     */
    public function update(User $user, SchedulePayment $schedulePayment): bool
    {
        return $user->can('schedule_payment.update');
    }

    /**
     * Determine whether the user can pay now the schedule payment.
     */
    public function payNow(User $user, SchedulePayment $schedulePayment): bool
    {
        return $user->can('schedule_payment.pay-now');
    }
}
