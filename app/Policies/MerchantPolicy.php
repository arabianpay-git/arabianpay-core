<?php

namespace App\Policies;

use App\Models\Merchant;
use App\Models\User;

class MerchantPolicy
{
    /**
     * Determine whether the user can view any merchants.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('merchant.view');
    }

    /**
     * Determine whether the user can view the merchant.
     *
     * Admin and employee users with the permission can see all merchants.
     * Merchants can only view their own merchant record.
     */
    public function view(User $user, Merchant $merchant): bool
    {
        if (! $user->can('merchant.view')) {
            return false;
        }

        if ($user->user_type === 'admin') {
            return true;
        }

        if ($user->user_type === 'employee') {
            return true;
        }

        if ($user->user_type === 'merchant') {
            return $merchant->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can update the merchant.
     */
    public function update(User $user, Merchant $merchant): bool
    {
        return $user->can('merchant.update');
    }
}
