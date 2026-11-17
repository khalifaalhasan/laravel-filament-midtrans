<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /** Statuses whose seats no longer count against the quota. */
    public function releasesQuota(): bool
    {
        return in_array($this, [self::Expired, self::Cancelled, self::Refunded], true);
    }
}
