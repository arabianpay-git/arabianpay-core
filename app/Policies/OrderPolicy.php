<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('order.view');
    }

    /**
     * Determine whether the user can view the order.
     *
     * Admin and employee users with the permission can see all orders.
     * Merchants can only view orders where they are the seller.
     */
    public function view(User $user, Order $order): bool
    {
        if (! $user->can('order.view')) {
            return false;
        }

        if ($user->user_type === 'admin') {
            return true;
        }

        if ($user->user_type === 'employee') {
            return true;
        }

        if ($user->user_type === 'merchant') {
            return $order->seller_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can accept the order.
     */
    public function accept(User $user, Order $order): bool
    {
        return $user->can('order.accept');
    }

    /**
     * Determine whether the user can reject the order.
     */
    public function reject(User $user, Order $order): bool
    {
        return $user->can('order.reject');
    }

    /**
     * Determine whether the user can manage the order.
     */
    public function manage(User $user, Order $order): bool
    {
        return $user->can('order.manage');
    }
}
