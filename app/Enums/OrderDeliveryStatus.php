<?php

namespace App\Enums;

/**
 * [PHASE-2] Order delivery statuses.
 */
enum OrderDeliveryStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';
}
