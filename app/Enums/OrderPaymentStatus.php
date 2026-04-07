<?php

namespace App\Enums;

/**
 * [PHASE-2] Order payment statuses.
 */
enum OrderPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyPaid = 'partially_paid';
}
