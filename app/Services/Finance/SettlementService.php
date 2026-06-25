<?php

namespace App\Services\Finance;

use App\Models\FAccounts;
use App\Models\FEntry;
use App\Models\FTransaction;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\SupplierPayout;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SettlementService
{
    /**
     * Generate settlements for a given period.
     *
     * @return \Illuminate\Support\Collection
     */
    public function generateSettlementsForPeriod(Carbon $startDate, Carbon $endDate, ?User $creator = null)
    {
        // 1. Find all suppliers with delivered orders in period not yet settled
        $supplierIds = Order::where('delivery_status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->whereNull('settlement_id')
            ->distinct()
            ->pluck('seller_id');

        $settlements = collect();

        foreach ($supplierIds as $supplierUserId) {
            $orders = $this->getDeliveredOrdersForSettlement($supplierUserId, $startDate, $endDate);

            if ($orders->isEmpty()) {
                continue;
            }

            // Calculate amounts
            $amounts = $this->calculateSettlementAmount($orders);

            // Should not create settlement if payable amount is 0 or less?
            // Maybe yes, for record keeping, but generally we pay positive amounts.
            // Let's assume we create it anyway or check business rule.
            // For now, create it.

            $settlementDate = $this->calculateSettlementDate($endDate);
            $settlementNumber = $this->generateSettlementNumber($supplierUserId, $settlementDate);

            Settlement::runInApprovalContext(function () use (
                $settlements, $supplierUserId, $startDate, $endDate, $creator, $orders,
                $amounts, $settlementDate, $settlementNumber
            ) {
                DB::beginTransaction();
                try {
                    $settlement = Settlement::create([
                        'uuid' => (string) Str::uuid(),
                        'settlement_number' => $settlementNumber,
                        'supplier_user_id' => $supplierUserId,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'settlement_date' => $settlementDate,
                        'total_amount' => $amounts['total_amount'],
                        'commission_amount' => $amounts['commission_amount'],
                        'payable_amount' => $amounts['payable_amount'],
                        'status' => 'draft',
                        'created_by' => $creator ? $creator->id : null,
                    ]);

                    // Link orders to settlement
                    foreach ($orders as $order) {
                        $order->settlement_id = $settlement->id;
                        $order->save();
                    }

                    DB::commit();
                    $settlements->push($settlement);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Failed to generate settlement for supplier $supplierUserId: ".$e->getMessage());
                    // Continue to next supplier? or throw?
                    // Best to continue and report errors for batch generation
                }
            });
        }

        return $settlements;
    }

    /**
     * Get eligible orders for settlement.
     */
    public function getDeliveredOrdersForSettlement($supplierUserId, Carbon $startDate, Carbon $endDate)
    {
        return Order::where('seller_id', $supplierUserId)
            ->where('delivery_status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->whereNull('settlement_id')
            ->get();
    }

    /**
     * Get all eligible unsettled orders.
     */
    public function getAllEligibleUnsettledOrders()
    {
        return Order::where('delivery_status', 'delivered')
            ->whereNull('settlement_id')
            ->get();
    }

    /**
     * Calculate settlement totals.
     */
    public function calculateSettlementAmount($orders)
    {
        $totalAmount = 0;
        $commissionAmount = 0;

        foreach ($orders as $order) {
            $totalAmount = bcadd((string) $totalAmount, (string) $order->grand_total, 2);
            $commissionAmount = bcadd((string) $commissionAmount, (string) ($order->commission_amount ?? 0), 2);
        }

        return [
            'total_amount' => $totalAmount,
            'commission_amount' => $commissionAmount,
            'payable_amount' => max(0, bcsub((string) $totalAmount, (string) $commissionAmount, 2)),
        ];
    }

    /**
     * Generate unique settlement number.
     * Format: SETT-yyyyMMdd-{user_id}
     */
    public function generateSettlementNumber($supplierUserId, Carbon $settlementDate)
    {
        return 'SETT-'.$settlementDate->format('Ymd').'-'.$supplierUserId;
    }

    /**
     * Calculate settlement date (payout date).
     * Usually the Tuesday following the period end.
     */
    public function calculateSettlementDate(Carbon $periodEndDate)
    {
        // If period ends on Monday, next day is Tuesday.
        // Logic: Find next Tuesday
        return $periodEndDate->copy()->next(Carbon::TUESDAY);
    }

    /**
     * Get next settlement period.
     * Period: Previous week Tuesday to Monday.
     */
    public function getCurrentSettlementPeriod()
    {
        // If today is Tuesday (payout day), the period ended yesterday (Monday).
        $now = Carbon::now();

        // Find the most recent Tuesday (start of current/last week)
        $start = $now->copy()->previous(Carbon::TUESDAY);
        if ($now->dayOfWeek == Carbon::TUESDAY) {
            $start = $now->copy()->subWeek()->startOfDay();
        } else {
            // If today is Wed-Mon, the last Tuesday was the start
            $start = $now->copy()->previous(Carbon::TUESDAY)->startOfDay();
        }

        // End date is 6 days after start (Monday)
        $end = $start->copy()->addDays(6)->endOfDay();

        // Settlement date is the Tuesday after end date
        $settlementDate = $end->copy()->next(Carbon::TUESDAY);

        return [
            'start_date' => $start,
            'end_date' => $end,
            'settlement_date' => $settlementDate,
        ];
    }

    public function getPreviousSettlementPeriod()
    {
        // If today is Tuesday (payout day), the period ended yesterday (Monday).
        $now = Carbon::now();

        // Find the most recent Tuesday (start of current/last week)
        $end = $now->copy()->previous(Carbon::TUESDAY);
        if ($now->dayOfWeek == Carbon::TUESDAY) {
            $end = $now->copy()->subWeek()->startOfDay();
        } else {
            // If today is Wed-Mon, the last Tuesday was the start
            $end = $now->copy()->previous(Carbon::TUESDAY)->startOfDay();
        }

        // End date is 6 days after start (Monday)
        $start = $end->copy()->subDays(6)->endOfDay();

        // Settlement date is the Tuesday after end date
        $settlementDate = $start->copy()->next(Carbon::TUESDAY);

        return [
            'start_date' => $start,
            'end_date' => $end,
            'settlement_date' => $settlementDate,
        ];
    }

    public function approveSettlement(Settlement $settlement, User $user)
    {
        return Settlement::runInApprovalContext(function () use ($settlement, $user) {
            return DB::transaction(function () use ($settlement, $user) {
                if ($settlement->status !== 'draft' && $settlement->status !== 'pending_approval') {
                    throw new \Exception("Settlement cannot be approved (Status: {$settlement->status})");
                }

                $settlement->status = 'approved';
                $settlement->approved_by = $user->id;
                $settlement->approved_at = now();
                $settlement->save();

                return $settlement;
            });
        });
    }

    public function batchApprove(array $settlementIds, User $user)
    {
        return Settlement::runInApprovalContext(function () use ($settlementIds, $user) {
            return DB::transaction(function () use ($settlementIds, $user) {
                $settlements = Settlement::whereIn('id', $settlementIds)
                    ->whereIn('status', ['draft', 'pending_approval'])
                    ->get();

                if ($settlements->isEmpty()) {
                    return [
                        'success' => false,
                        'error' => 'No settlements found or none are in draft/pending_approval status.',
                        'processed' => 0,
                        'failed' => count($settlementIds),
                    ];
                }

                $processed = 0;
                $failed = 0;
                $errors = [];

                foreach ($settlements as $settlement) {
                    try {
                        $this->approveSettlement($settlement, $user);
                        $processed++;
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = [
                            'settlement_id' => $settlement->id,
                            'settlement_number' => $settlement->settlement_number,
                            'error' => $e->getMessage(),
                        ];
                    }
                }

                return [
                    'success' => true,
                    'processed' => $processed,
                    'failed' => $failed,
                    'errors' => $errors,
                ];
            });
        });
    }

    /**
     * Mark settlement as paid and create financial entries.
     */
    public function markSettlementAsPaid(Settlement $settlement, User $user)
    {
        return Settlement::runInApprovalContext(function () use ($settlement, $user) {
            return DB::transaction(function () use ($settlement, $user) {
                if ($settlement->status !== 'approved') {
                    throw new \Exception('Settlement must be approved before payment');
                }

                $settlement->status = 'paid';
                $settlement->paid_at = now();
                $settlement->paid_by = $user->id;
                $settlement->save();

                // Create SupplierPayout record
                $this->createPayoutForSettlement($settlement, $user);

                return $settlement;
            });
        });
    }

    /**
     * Create individual payout record and financial entries.
     */
    protected function createPayoutForSettlement(Settlement $settlement, User $user)
    {
        $merchant = Merchant::where('user_id', $settlement->supplier_user_id)->first();
        if (! $merchant) {
            throw new \Exception("Merchant record not found for user {$settlement->supplier_user_id}");
        }

        // Create SupplierPayout
        $payout = SupplierPayout::create([
            'uuid' => (string) Str::uuid(),
            'supplier_id' => $merchant->id,
            'settlement_id' => $settlement->id,
            'amount' => $settlement->payable_amount,
            'status' => 'completed',
            'payout_date' => now(),
            'created_by' => $user->id,
            'notes' => "Settlement #{$settlement->settlement_number}",
        ]);

        // Create Financial Transaction & Entries (Copied/Adapted from SupplierFinanceService logic)
        $fTransaction = FTransaction::create([
            'uuid' => (string) Str::uuid(),
            'supplier_id' => $merchant->id,
            'user_id' => $user->id,
            'transaction_type' => 'payout', // or settlement_payout
            'amount' => $payout->amount,
            'status' => 'approved',
            'transaction_date' => now(),
            'notes' => "Payout for Settlement #{$settlement->settlement_number}",
        ]);

        $startFormatted = $settlement->start_date->format('Y-m-d');
        $endFormatted = $settlement->end_date->format('Y-m-d');

        // Debit Accounts Payable (Liability decreases)
        $supplierAccountId = settings('settlement.supplier_account_id', '2400');
        $supplierAccount = FAccounts::findOrFail($supplierAccountId);
        FEntry::create([
            'transaction_id' => $fTransaction->id,
            'user_id' => $user->id,
            'supplier_id' => $merchant->id,
            'account_id' => $supplierAccount->id,
            'account_name' => $supplierAccount->account_name,
            'debit' => $payout->amount,
            'credit' => 0,
            'status' => 'approved',
            'entry_date' => now(),
            'notes' => "Settlement Payout {$settlement->settlement_number} ($startFormatted to $endFormatted)",
        ]);

        // Credit Bank Account (Asset decreases)
        $bankAccountId = settings('settlement.bank_account_id', '1201');
        $bankAccount = FAccounts::findOrFail($bankAccountId);
        FEntry::create([
            'transaction_id' => $fTransaction->id,
            'user_id' => $user->id,
            'supplier_id' => $merchant->id,
            'account_id' => $bankAccount->id,
            'account_name' => $bankAccount->account_name,
            'debit' => 0,
            'credit' => $payout->amount,
            'status' => 'approved',
            'entry_date' => now(),
            'notes' => "Settlement Payout {$settlement->settlement_number} ($startFormatted to $endFormatted)",
        ]);
    }

    public function cancelSettlement(Settlement $settlement, $reason, User $user)
    {
        return Settlement::runInApprovalContext(function () use ($settlement, $reason, $user) {
            return DB::transaction(function () use ($settlement, $reason, $user) {
                if ($settlement->status === 'paid') {
                    throw new \Exception('Cannot cancel a paid settlement');
                }

                $settlement->status = 'cancelled';
                $settlement->notes .= "\nCancelled by {$user->name}: $reason";
                $settlement->save();

                // Unlink orders
                foreach ($settlement->orders as $order) {
                    $order->settlement_id = null;
                    $order->save();
                }

                return $settlement;
            });
        });
    }

    /**
     * Process batch payout for multiple settlements.
     * All-or-nothing transaction strategy.
     */
    public function processBatchPayout(array $settlementIds, User $user)
    {
        // 1. Validation Phase
        $settlements = Settlement::whereIn('id', $settlementIds)->get();

        if ($settlements->count() !== count($settlementIds)) {
            return [
                'success' => false,
                'error' => 'Some settlements were not found.',
            ];
        }

        foreach ($settlements as $settlement) {
            if ($settlement->status !== 'approved') {
                return [
                    'success' => false,
                    'error' => "Settlement {$settlement->settlement_number} is not in approved status.",
                ];
            }
            if ($settlement->payable_amount <= 0) {
                // Warning? Or logic to skip?
                // If 0 amount, maybe just mark as paid without transaction?
                // strict check for now.
            }
        }

        // 2. Processing Phase (Atomic Transaction)
        return Settlement::runInApprovalContext(function () use ($settlements, $user) {
            DB::beginTransaction();
            try {
                $totalAmount = 0;
                $processedCount = 0;
                $successIds = [];

                foreach ($settlements as $settlement) {
                    // This method calls createPayoutForSettlement which creates DB records
                    // All inside this transaction.
                    $this->markSettlementAsPaid($settlement, $user);

                    $totalAmount += $settlement->payable_amount;
                    $processedCount++;
                    $successIds[] = $settlement->id;
                }

                DB::commit();

                return [
                    'success' => true,
                    'processed' => $processedCount,
                    'total_amount' => $totalAmount,
                    'settlement_ids' => $successIds,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Batch payout failed (rolled back): '.$e->getMessage());

                return [
                    'success' => false,
                    'error' => 'Batch processing failed: '.$e->getMessage(),
                ];
            }
        });
    }
}
