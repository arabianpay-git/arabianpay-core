<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('customer.view');
    }

    /**
     * Determine whether the user can view the customer.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customer.view');
    }

    /**
     * Determine whether the user can manage the customer.
     */
    public function manage(User $user, Customer $customer): bool
    {
        return $user->can('customer.manage');
    }

    /**
     * Determine whether the user can update the customer's status.
     */
    public function updateStatus(User $user, Customer $customer): bool
    {
        return $user->can('customer.update-status');
    }
}
