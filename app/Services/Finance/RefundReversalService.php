<?php

namespace App\Services\Finance;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\Settlement;
use Illuminate\Support\Facades\Log;

/**
 * [PHASE-3] Refund and reversal service skeleton.
 *
 * IMPORTANT: Full refund processing (ClickPay reversal, accounting entries,
 * wallet adjustments) is NOT yet implemented. This service provides:
 * - Explicit guards against unsupported reversal paths
 * - Logging for refund attempts on settled orders
 * - Foundation for Phase 4 full refund implementation
 */
class RefundReversalService
{
    /**
     * Check if a refund can be processed for a given order.
     *
     * @return array{allowed: bool, reason: string|null}
     */
    public function canProcessRefund(Order $order): array
    {
        // Block refund if order is part of a paid settlement
        if ($order->settlement_id) {
            $settlement = Settlement::find($order->settlement_id);
            $statusValue = $settlement->status instanceof \App\Enums\SettlementStatus
                ? $settlement->status->value : $settlement->status;
            if ($settlement && $statusValue === 'paid') {
                Log::warning('[REFUND] Blocked: order is part of a paid settlement', [
                    'order_id' => $order->id,
                    'settlement_number' => $settlement->settlement_number,
                ]);

                return [
                    'allowed' => false,
                    'reason' => "Order is part of paid settlement #{$settlement->settlement_number}. "
                        . 'Settlement reversals require manual processing through the finance team.',
                ];
            }
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Process a refund request.
     *
     * NOT YET IMPLEMENTED. Currently only validates and logs.
     *
     * @throws \DomainException always — refund processing not yet implemented
     */
    public function processRefund(RefundRequest $refundRequest): never
    {
        Log::info('[REFUND] Refund processing attempted (not yet implemented)', [
            'refund_request_id' => $refundRequest->id,
            'order_id' => $refundRequest->order_id,
            'amount' => $refundRequest->refund_amount,
        ]);

        throw new \DomainException(
            'Automated refund processing is not yet implemented. '
            . 'Refunds must be processed manually through the ClickPay portal and reconciled.'
        );
    }

    /**
     * Reverse a settlement payout.
     *
     * NOT YET IMPLEMENTED. Explicitly blocked.
     *
     * @throws \DomainException always — settlement reversal not yet implemented
     */
    public function reverseSettlementPayout(Settlement $settlement): never
    {
        Log::warning('[REFUND] Settlement reversal attempted (not supported)', [
            'settlement_number' => $settlement->settlement_number,
            'status' => $settlement->status,
        ]);

        throw new \DomainException(
            'Settlement payout reversal is not yet implemented. '
            . 'Contact the finance team for manual reversal processing.'
        );
    }
}
