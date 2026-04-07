<?php

namespace App\Policies;

use App\Models\CustomerCreditLimit;
use App\Models\User;

class CustomerCreditLimitPolicy
{
    /**
     * Determine whether the user can view any credit limits.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('credit-limit.view');
    }

    /**
     * Determine whether the user can view the credit limit.
     */
    public function view(User $user, CustomerCreditLimit $creditLimit): bool
    {
        return $user->can('credit-limit.view');
    }

    /**
     * Determine whether the user can create a credit limit.
     */
    public function create(User $user): bool
    {
        return $user->can('credit-limit.create');
    }

    /**
     * Determine whether the user can update the credit limit.
     */
    public function update(User $user, CustomerCreditLimit $creditLimit): bool
    {
        return $user->can('credit-limit.update');
    }
}
