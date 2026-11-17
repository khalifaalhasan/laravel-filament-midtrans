<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', Event::class);
    }

    public function view(User $user, Order $order): bool
    {
        return $user->is($order->user)
            || $user->isStaff()
            || $user->isProjectOfficerOf($order->event);
    }

    /** Money leaves the company: CEO and Operation only. */
    public function refund(User $user, Order $order): bool
    {
        return $user->isStaff() && $order->status === OrderStatus::Paid;
    }
}
