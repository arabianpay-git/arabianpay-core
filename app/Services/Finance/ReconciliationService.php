<?php

namespace App\Services\Finance;

use App\Helpers\Money;
use App\Models\FEntry;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\SupplierPayout;
use Illuminate\Support\Facades\Log;

/**
 * [PHASE-3] Financial reconciliation service.
 *
 * Provides automated checks that settlements, payouts, and accounting
 * entries are internally consistent. Produces exception reports for
 * mismatches requiring manual investigation.
 */
class ReconciliationService
{
    /**
     * Reconcile a single settlement against its source orders.
     *
     * Returns an array with 'status' ('pass'|'fail') and 'details'.
     */
    public function reconcileSettlement(Settlement $settlement): array
    {
        $orders = Order::where('settlement_id', $settlement->id)->get();
        $issues = [];

        // Check 1: total_amount matches sum of order grand_totals
        $orderTotalSum = '0.00';
        $orderCommissionSum = '0.00';
        foreach ($orders as $order) {
            $orderTotalSum = Money::add($orderTotalSum, $order->grand_total);
            $orderCommissionSum = Money::add($orderCommissionSum, $order->commission_amount ?? 0);
        }

        if (Money::compare($settlement->total_amount, $orderTotalSum) !== 0) {
            $issues[] = [
                'check' => 'total_amount_vs_orders',
                'expected' => $orderTotalSum,
                'actual' => $settlement->total_amount,
                'message' => "Settlement total ({$settlement->total_amount}) != order sum ({$orderTotalSum})",
            ];
        }

        if (Money::compare($settlement->commission_amount, $orderCommissionSum) !== 0) {
            $issues[] = [
                'check' => 'commission_amount_vs_orders',
                'expected' => $orderCommissionSum,
                'actual' => $settlement->commission_amount,
                'message' => "Settlement commission ({$settlement->commission_amount}) != order commission sum ({$orderCommissionSum})",
            ];
        }

        // Check 2: payable_amount = total - commission
        $expectedPayable = Money::max('0.00', Money::subtract($orderTotalSum, $orderCommissionSum));
        if (Money::compare($settlement->payable_amount, $expectedPayable) !== 0) {
            $issues[] = [
                'check' => 'payable_amount_calculation',
                'expected' => $expectedPayable,
                'actual' => $settlement->payable_amount,
                'message' => "Payable ({$settlement->payable_amount}) != expected ({$expectedPayable})",
            ];
        }

        // Check 3: If paid, payout amount must match payable_amount
        if ($settlement->status === 'paid' || (is_object($settlement->status) && $settlement->status->value === 'paid')) {
            $payout = SupplierPayout::where('settlement_id', $settlement->id)->first();
            if (! $payout) {
                $issues[] = [
                    'check' => 'payout_exists',
                    'message' => 'Settlement is paid but no payout record found.',
                ];
            } elseif (Money::compare($payout->amount, $settlement->payable_amount) !== 0) {
                $issues[] = [
                    'check' => 'payout_amount_vs_payable',
                    'expected' => $settlement->payable_amount,
                    'actual' => $payout->amount,
                    'message' => "Payout ({$payout->amount}) != settlement payable ({$settlement->payable_amount})",
                ];
            }
        }

        // Check 4: If paid, accounting entries must balance (debit = credit)
        if ($settlement->status === 'paid' || (is_object($settlement->status) && $settlement->status->value === 'paid')) {
            $payout = SupplierPayout::where('settlement_id', $settlement->id)->first();
            if ($payout) {
                $entries = FEntry::whereHas('transaction', function ($q) use ($payout) {
                    $q->where('supplier_id', $payout->supplier_id)
                      ->where('transaction_type', 'payout')
                      ->where('amount', $payout->amount);
                })->get();

                if ($entries->isNotEmpty()) {
                    $totalDebit = '0.00';
                    $totalCredit = '0.00';
                    foreach ($entries as $entry) {
                        $totalDebit = Money::add($totalDebit, $entry->debit);
                        $totalCredit = Money::add($totalCredit, $entry->credit);
                    }

                    if (Money::compare($totalDebit, $totalCredit) !== 0) {
                        $issues[] = [
                            'check' => 'accounting_balance',
                            'debit' => $totalDebit,
                            'credit' => $totalCredit,
                            'message' => "Accounting imbalance: debit={$totalDebit} != credit={$totalCredit}",
                        ];
                    }
                }
            }
        }

        $status = empty($issues) ? 'pass' : 'fail';

        if ($status === 'fail') {
            Log::warning('[RECONCILIATION] Settlement mismatch detected', [
                'settlement_number' => $settlement->settlement_number,
                'issues' => $issues,
            ]);
        }

        return [
            'status' => $status,
            'settlement_number' => $settlement->settlement_number,
            'order_count' => $orders->count(),
            'issues' => $issues,
        ];
    }

    /**
     * Run reconciliation across all paid settlements.
     *
     * Returns a summary with pass/fail counts and detailed exceptions.
     */
    public function reconcileAllPaidSettlements(): array
    {
        $settlements = Settlement::where('status', 'paid')->get();

        $results = [
            'total' => $settlements->count(),
            'passed' => 0,
            'failed' => 0,
            'exceptions' => [],
        ];

        foreach ($settlements as $settlement) {
            $result = $this->reconcileSettlement($settlement);
            if ($result['status'] === 'pass') {
                $results['passed']++;
            } else {
                $results['failed']++;
                $results['exceptions'][] = $result;
            }
        }

        Log::info('[RECONCILIATION] Batch completed', [
            'total' => $results['total'],
            'passed' => $results['passed'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }
}
