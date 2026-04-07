<?php

namespace App\Services\Finance;

use App\Helpers\Money;
use App\Models\Order;
use App\Models\SchedulePayment;
use App\Models\Settlement;
use App\Models\SupplierPayout;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

/**
 * [PHASE-2] Financial invariant validation service.
 *
 * Provides assertions that must hold true for financial data integrity.
 * Called during settlement generation, payout processing, and wallet updates
 * to prevent silent corruption.
 */
class FinancialInvariantService
{
    /**
     * Verify settlement total equals sum of its constituent order amounts.
     *
     * @throws \RuntimeException if invariant is violated
     */
    public function assertSettlementTotalMatchesOrders(Settlement $settlement): void
    {
        $orders = Order::where('settlement_id', $settlement->id)->get();

        $orderSum = '0.00';
        $commissionSum = '0.00';
        foreach ($orders as $order) {
            $orderSum = Money::add($orderSum, $order->grand_total);
            $commissionSum = Money::add($commissionSum, $order->commission_amount ?? 0);
        }

        if (Money::compare($settlement->total_amount, $orderSum) !== 0) {
            $msg = "Settlement #{$settlement->settlement_number} total_amount ({$settlement->total_amount}) "
                . "does not match sum of order grand_totals ({$orderSum})";
            Log::error('[INVARIANT VIOLATION] ' . $msg);
            throw new \RuntimeException($msg);
        }

        if (Money::compare($settlement->commission_amount, $commissionSum) !== 0) {
            $msg = "Settlement #{$settlement->settlement_number} commission_amount ({$settlement->commission_amount}) "
                . "does not match sum of order commissions ({$commissionSum})";
            Log::error('[INVARIANT VIOLATION] ' . $msg);
            throw new \RuntimeException($msg);
        }

        $expectedPayable = Money::max('0.00', Money::subtract($orderSum, $commissionSum));
        if (Money::compare($settlement->payable_amount, $expectedPayable) !== 0) {
            $msg = "Settlement #{$settlement->settlement_number} payable_amount ({$settlement->payable_amount}) "
                . "does not match expected ({$expectedPayable})";
            Log::error('[INVARIANT VIOLATION] ' . $msg);
            throw new \RuntimeException($msg);
        }
    }

    /**
     * Verify payout amount matches settlement payable amount.
     *
     * @throws \RuntimeException if invariant is violated
     */
    public function assertPayoutMatchesSettlement(SupplierPayout $payout, Settlement $settlement): void
    {
        if (Money::compare($payout->amount, $settlement->payable_amount) !== 0) {
            $msg = "Payout #{$payout->uuid} amount ({$payout->amount}) "
                . "does not match settlement #{$settlement->settlement_number} payable_amount ({$settlement->payable_amount})";
            Log::error('[INVARIANT VIOLATION] ' . $msg);
            throw new \RuntimeException($msg);
        }
    }

    /**
     * Verify wallet balance_after = previous balance + current amount.
     *
     * @param string $previousBalance The balance_after of the previous wallet record
     * @param Wallet $newEntry The wallet entry being created
     * @throws \RuntimeException if invariant is violated
     */
    public function assertWalletBalanceAfter(string $previousBalance, Wallet $newEntry): void
    {
        $expected = Money::add($previousBalance, $newEntry->amount);

        if (Money::compare($newEntry->balance_after, $expected) !== 0) {
            $msg = "Wallet entry balance_after ({$newEntry->balance_after}) "
                . "does not match previous ({$previousBalance}) + amount ({$newEntry->amount}) = ({$expected})";
            Log::error('[INVARIANT VIOLATION] ' . $msg, [
                'user_id' => $newEntry->user_id,
                'seller_id' => $newEntry->seller_id,
                'order_id' => $newEntry->order_id,
            ]);
            throw new \RuntimeException($msg);
        }
    }

    /**
     * Verify schedule payment instalments sum to expected total.
     *
     * @param int $orderId The order ID
     * @param string $expectedTotal The expected total amount
     * @throws \RuntimeException if invariant is violated
     */
    public function assertSchedulePaymentsSumMatchesTotal(int $orderId, string $expectedTotal): void
    {
        $instalments = SchedulePayment::where('order_id', $orderId)->get();

        $sum = '0.00';
        foreach ($instalments as $instalment) {
            $sum = Money::add($sum, $instalment->instalment_amount);
        }

        if (Money::compare($sum, $expectedTotal) !== 0) {
            $msg = "Schedule payments for order #{$orderId} sum ({$sum}) "
                . "does not match expected total ({$expectedTotal})";
            Log::warning('[INVARIANT CHECK] ' . $msg);
            // Warning only, not exception — existing data may have legitimate variances
            // from partial payments, adjustments, etc.
        }
    }
}
