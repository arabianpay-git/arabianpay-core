<?php

namespace App\Services\Finance;

use App\Models\Order;
use App\Models\SupplierPayout;
use App\Models\FEntry;
use App\Models\FAccounts;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SupplierFinanceService
{
    /*
     * Get upcoming payout for a supplier.
     * For now we will hard code the payout date to be every Tuesday, until we have a proper setting table for this feature.
     * This calculates the total amount to be paid on the next Tuesday payout. 
     * Only includes delivered orders that have not been fully paid out yet.
     */
    public function getUpcomingPayout($supplierUserId)
    {
        // Get the next Tuesday
        $nextTuesday = $this->getNextTuesday();
        
        // Get all delivered orders for this supplier
        $deliveredOrders = Order::where('seller_id', $supplierUserId)
            ->where('delivery_status', 'delivered')
            ->with(['payouts'])
            ->get();
        
        $upcomingAmount = 0;
        $ordersData = [];
        
        foreach ($deliveredOrders as $order) {
            // Calculate the amount owed to supplier (grand_total - commission)
            $orderAmount = (float)$order->grand_total - (float)($order->commission_amount ?? 0);
            
            // Calculate how much has already been paid out
            $paidAmount = $order->payouts()
                ->sum('amount');
            
            // Calculate the remaining amount
            $remainingAmount = $orderAmount - $paidAmount;
            
            if ($remainingAmount > 0) {
                $upcomingAmount += $remainingAmount;
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
        
        return [
            'supplier_id' => $supplierId,
            'upcoming_payout_amount' => round($upcomingAmount, 2),
            'next_payout_date' => $nextTuesday->format('Y-m-d'),
            'next_payout_day' => $nextTuesday->format('l, F j, Y'),
            'orders_count' => count($ordersData),
            'orders' => $ordersData,
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
        if(!$supplier) {
            return [
                'error' => 'Supplier not found',
                'supplier_id' => $supplierUserId,
                'period_start' => $startDate,
                'period_end' => $endDate,
            ];
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $accountId = 2400;
        $account = FAccounts::find($accountId);
        
        if (!$account) {
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
        $isDebitNormal = ((int)$account->account_type2) === 1;
        
        $openingBalance = $isDebitNormal
            ? (float)$priorEntries->sum_debit - (float)$priorEntries->sum_credit
            : (float)$priorEntries->sum_credit - (float)$priorEntries->sum_debit;
        
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
            $debit = (float)($entry->debit ?? 0);
            $credit = (float)($entry->credit ?? 0);
            
            $totalDebit += $debit;
            $totalCredit += $credit;
            
            // Calculate balance change
            $delta = $isDebitNormal
                ? $debit - $credit
                : $credit - $debit;
            
            $runningBalance += $delta;
            
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
                'net_change' => round($closingBalance - $openingBalance, 2),
            ],
            'entries_count' => $entries->count(),
            'entries' => $formattedEntries,
        ];
    }
    
    private function getNextTuesday()
    {
        $now = Carbon::now();
        
        // If today is Tuesday, get next Tuesday (7 days from now)
        if ($now->dayOfWeek === Carbon::TUESDAY) {
            return $now->copy()->addWeek()->startOfDay();
        }
        
        // Otherwise, get the next Tuesday
        return $now->copy()->next(Carbon::TUESDAY)->startOfDay();
    }
}
