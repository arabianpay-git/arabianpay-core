<?php

namespace App\Policies;

use App\Models\Settlement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SettlementPolicy
{
    /**
     * Determine whether the user can view any settlements.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('settlement.view');
    }

    /**
     * Determine whether the user can view the settlement.
     *
     * Admin and employee users with the permission can see all settlements.
     */
    public function view(User $user, Settlement $settlement): bool
    {
        if (! $user->can('settlement.view')) {
            return false;
        }

        if ($user->user_type === 'admin' || $user->user_type === 'employee') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create settlements.
     */
    public function create(User $user): bool
    {
        return $user->can('settlement.create');
    }

    /**
     * Determine whether the user can approve the settlement.
     *
     * Maker-checker (F-009): the approver must not be the creator.
     */
    public function approve(User $user, Settlement $settlement): Response
    {
        if (! $user->can('settlement.approve')) {
            return Response::deny('You do not have permission to approve settlements.');
        }

        if ($settlement->created_by === $user->id) {
            return Response::deny('Maker-checker: you cannot approve/pay your own settlement');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can cancel the settlement.
     */
    public function cancel(User $user, Settlement $settlement): bool
    {
        return $user->can('settlement.cancel');
    }

    /**
     * Determine whether the user can pay the settlement.
     *
     * Maker-checker (F-009): the payer must not be the approver.
     */
    public function pay(User $user, Settlement $settlement): Response
    {
        if (! $user->can('settlement.pay')) {
            return Response::deny('You do not have permission to pay settlements.');
        }

        if ($settlement->approved_by === $user->id) {
            return Response::deny('Maker-checker: you cannot approve/pay your own settlement');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can export settlements.
     */
    public function export(User $user): bool
    {
        return $user->can('settlement.export');
    }
}
