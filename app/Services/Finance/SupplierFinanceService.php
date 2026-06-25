<?php

namespace App\Services\Finance;

use App\Models\FAccounts;
use App\Models\FEntry;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Settlement;
use App\Services\AuditTrailService;
use Illuminate\Support\Carbon;

class SupplierFinanceService
{
    public function __construct(private AuditTrailService $auditTrail) {}

    /*
     * Get upcoming payout for a supplier.
     * For now we will hard code the payout date to be every Tuesday, until we have a proper setting table for this feature.
     * This calculates the total amount to be paid on the next Tuesday payout.
     * Only includes delivered orders that have not been fully paid out yet.
     */
    public function getUpcomingPayout($supplierUserId)
    {
        // 1. Check if there's an existing generated settlement for next period
        // Status could be draft, pending_approval, approved
        $upcomingSettlement = Settlement::where('supplier_user_id', $supplierUserId)
            ->whereIn('status', ['draft', 'pending_approval', 'approved'])
            ->orderBy('settlement_date', 'asc')
            ->first();

        if ($upcomingSettlement) {
            $this->auditTrail->logViewOperation(
                'supplier_payout_read',
                'SupplierPayout',
                'Supplier upcoming payout accessed',
                ['supplier_user_id' => $supplierUserId]
            );

            return [
                'supplier_id' => $supplierUserId,
                'upcoming_payout_amount' => (float) $upcomingSettlement->payable_amount,
                'next_payout_date' => $upcomingSettlement->settlement_date ? $upcomingSettlement->settlement_date->format('Y-m-d') : null,
                'next_payout_day' => $upcomingSettlement->settlement_date ? $upcomingSettlement->settlement_date->format('l, F j, Y') : null,
                'orders_count' => $upcomingSettlement->orders()->count(),
                'orders' => $upcomingSettlement->orders()->get()->map(function ($order) {
                    return [
                        'order_id' => $order->id,
                        'order_uuid' => $order->uuid,
                        'order_total' => (float) $order->grand_total,
                        'commission' => (float) $order->commission_amount,
                        'payable' => (float) bcsub((string) $order->grand_total, (string) ($order->commission_amount ?? 0), 2),
                        'delivery_status' => $order->delivery_status,
                        'delivered_at' => $order->delivered_at,
                    ];
                }),
                'is_settlement' => true,
                'settlement_number' => $upcomingSettlement->settlement_number,
            ];
        }

        // 2. Fallback: Calculate from orders not yet in a settlement
        $nextTuesday = $this->getNextTuesday();

        // Get all delivered orders for this supplier NOT in a settlement
        $deliveredOrders = Order::where('seller_id', $supplierUserId)
            ->where('delivery_status', 'delivered')
            ->whereNull('settlement_id')
            ->get();

        $upcomingAmount = 0;
        $ordersData = [];

        foreach ($deliveredOrders as $order) {
            // Calculate the amount owed to supplier (grand_total - commission)
            $orderAmount = bcsub((string) $order->grand_total, (string) ($order->commission_amount ?? 0), 2);

            // Check if partially paid (legacy support)
            $paidAmount = (string) $order->payouts()->sum('amount');
            $remainingAmount = max(0, bcsub($orderAmount, $paidAmount, 2));

            if (bccomp($remainingAmount, '0', 2) === 1) {
                $upcomingAmount = bcadd((string) $upcomingAmount, (string) $remainingAmount, 2);
                $ordersData[] = [
                    'order_id' => $order->id,
                    'order_uuid' => $order->uuid,
                    'order_total' => $orderAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'delivery_status' => $order->delivery_status,
                    'general_status' => $order->general_status,
                    'created_at' => $order->created_at,
                    'delivered_at' => $order->delivered_at,
                ];
            }
        }

        $this->auditTrail->logViewOperation(
            'supplier_payout_read',
            'SupplierPayout',
            'Supplier upcoming payout accessed',
            ['supplier_user_id' => $supplierUserId]
        );

        return [
            'supplier_id' => $supplierUserId,
            'upcoming_payout_amount' => round($upcomingAmount, 2),
            'next_payout_date' => $nextTuesday->format('Y-m-d'),
            'next_payout_day' => $nextTuesday->format('l, F j, Y'),
            'orders_count' => count($ordersData),
            'orders' => $ordersData,
            'is_settlement' => false,
        ];
    }

    /*
     * Get ledger/statement for a supplier within a date period.
     * Shows all financial entries for the supplier
     * with opening balance, transactions, and closing balance.
     */
    public function getLedger($supplierUserId, $startDate, $endDate)
    {
        $supplier = Merchant::find($supplierUserId);
        if (! $supplier) {
            return [
                'error' => 'Supplier not found',
                'supplier_id' => $supplierUserId,
                'period_start' => $startDate,
                'period_end' => $endDate,
            ];
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $accountId = settings('settlement.supplier_account_id', '2400');
        $account = FAccounts::find($accountId);

        if (! $account) {
            return [
                'error' => 'Accounts Payable account (2400) not found',
                'supplier_id' => $supplier->id,
                'period_start' => $start->format('Y-m-d'),
                'period_end' => $end->format('Y-m-d'),
            ];
        }

        // Calculate opening balance (all entries before start date for this supplier)
        $priorEntries = FEntry::where('account_id', $accountId)
            ->where('supplier_id', $supplier->id)
            ->whereDate('entry_date', '<', $start->toDateString())
            ->selectRaw('COALESCE(SUM(debit), 0) as sum_debit, COALESCE(SUM(credit), 0) as sum_credit')
            ->first();

        // Accounts Payable is a liability account with credit normal balance (account_type2 = 2)
        $isDebitNormal = ((int) $account->account_type2) === 1;

        $openingBalance = $isDebitNormal
            ? bcsub((string) $priorEntries->sum_debit, (string) $priorEntries->sum_credit, 2)
            : bcsub((string) $priorEntries->sum_credit, (string) $priorEntries->sum_debit, 2);

        // Get entries within the period for this supplier
        $entries = FEntry::with(['user', 'order', 'transaction'])
            ->where('account_id', $accountId)
            ->where('supplier_id', $supplier->id)
            ->whereDate('entry_date', '>=', $start->toDateString())
            ->whereDate('entry_date', '<=', $end->toDateString())
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate running balance and format entries
        $runningBalance = $openingBalance;
        $formattedEntries = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($entries as $entry) {
            $debit = (string) ($entry->debit ?? 0);
            $credit = (string) ($entry->credit ?? 0);

            $totalDebit = bcadd((string) $totalDebit, $debit, 2);
            $totalCredit = bcadd((string) $totalCredit, $credit, 2);

            // Calculate balance change
            $delta = $isDebitNormal
                ? bcsub($debit, $credit, 2)
                : bcsub($credit, $debit, 2);

            $runningBalance = bcadd((string) $runningBalance, $delta, 2);

            $formattedEntries[] = [
                'entry_id' => $entry->id,
                'date' => $entry->entry_date,
                'order_id' => $entry->order_id,
                'description' => $entry->notes,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($runningBalance, 2),
                'status' => $entry->status,
                'user' => $entry->user ? $entry->user->name : null,
            ];
        }

        $closingBalance = $runningBalance;

        $this->auditTrail->logViewOperation(
            'supplier_ledger_read',
            'SupplierLedger',
            'Supplier ledger accessed',
            ['supplier_user_id' => $supplierUserId]
        );

        return [
            'supplier_id' => $supplierUserId,
            'period' => [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'start_date_formatted' => $start->format('F j, Y'),
                'end_date_formatted' => $end->format('F j, Y'),
            ],
            'opening_balance' => round($openingBalance, 2),
            'closing_balance' => round($closingBalance, 2),
            'period_totals' => [
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'net_change' => round(bcsub((string) $closingBalance, (string) $openingBalance, 2), 2),
            ],
            'entries_count' => $entries->count(),
            'entries' => $formattedEntries,
        ];
    }

    private function getNextTuesday()
    {
        $payoutDay = settings('payout.day_of_week', 'Tuesday');

        $days = [
            'Sunday' => Carbon::SUNDAY,
            'Monday' => Carbon::MONDAY,
            'Tuesday' => Carbon::TUESDAY,
            'Wednesday' => Carbon::WEDNESDAY,
            'Thursday' => Carbon::THURSDAY,
            'Friday' => Carbon::FRIDAY,
            'Saturday' => Carbon::SATURDAY,
        ];

        $targetDay = $days[$payoutDay] ?? Carbon::TUESDAY;

        $now = Carbon::now();

        // If today is the target day, get the same day next week
        if ($now->dayOfWeek === $targetDay) {
            return $now->copy()->addWeek()->startOfDay();
        }

        // Otherwise, get the next target day
        return $now->copy()->next($targetDay)->startOfDay();
    }
}
