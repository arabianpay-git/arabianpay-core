<?php

namespace App\Services\Finance;

use App\Helpers\Money;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\SupplierPayout;
use App\Models\User;
use App\Models\FTransaction;
use App\Models\FAccounts;
use App\Models\FEntry;
use App\Enums\SettlementStatus;
use App\Models\Merchant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Settlement lifecycle service.
 *
 * [PHASE-3] Hardened with:
 * - Maker-checker enforcement (creator != approver != payer)
 * - Idempotency guards on generation and payout
 * - Full DB transaction atomicity on payment (settlement + payout + accounting)
 * - Debit = credit validation on accounting entries
 * - Audit logging at every lifecycle transition
 * - Explicit failure on missing accounts (no silent skips)
 */
class SettlementService
{
    // ─────────────────────────────────────────────
    // Generation
    // ─────────────────────────────────────────────

    /**
     * Generate settlements for a given period.
     *
     * Idempotency: skips suppliers who already have a settlement for the same period.
     */
    public function generateSettlementsForPeriod(Carbon $startDate, Carbon $endDate, ?User $creator = null)
    {
        $supplierIds = Order::where('delivery_status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->whereNull('settlement_id')
            ->distinct()
            ->pluck('seller_id');

        $settlements = collect();

        foreach ($supplierIds as $supplierUserId) {
            // [PHASE-3] Idempotency: check if settlement already exists for this supplier+period
            $existing = Settlement::where('supplier_user_id', $supplierUserId)
                ->where('start_date', $startDate->toDateString())
                ->where('end_date', $endDate->toDateString())
                ->whereIn('status', [
                    SettlementStatus::Draft->value,
                    SettlementStatus::Pending->value,
                    SettlementStatus::PendingApproval->value,
                    SettlementStatus::Approved->value,
                    SettlementStatus::Paid->value,
                ])
                ->first();

            if ($existing) {
                Log::info('[SETTLEMENT] Skipped duplicate generation', [
                    'supplier_user_id' => $supplierUserId,
                    'existing_settlement' => $existing->settlement_number,
                    'period' => "{$startDate->toDateString()} to {$endDate->toDateString()}",
                ]);
                continue;
            }

            $orders = $this->getDeliveredOrdersForSettlement($supplierUserId, $startDate, $endDate);

            if ($orders->isEmpty()) {
                continue;
            }

            $amounts = $this->calculateSettlementAmount($orders);
            $settlementDate = $this->calculateSettlementDate($endDate);
            $settlementNumber = $this->generateSettlementNumber($supplierUserId, $settlementDate);

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
                    'status' => SettlementStatus::Draft->value,
                    'created_by' => $creator?->id,
                ]);

                foreach ($orders as $order) {
                    $order->settlement_id = $settlement->id;
                    $order->save();
                }

                DB::commit();

                Log::info('[SETTLEMENT] Generated', [
                    'settlement_number' => $settlement->settlement_number,
                    'supplier_user_id' => $supplierUserId,
                    'total_amount' => $amounts['total_amount'],
                    'payable_amount' => $amounts['payable_amount'],
                    'order_count' => $orders->count(),
                    'created_by' => $creator?->id,
                ]);

                $settlements->push($settlement);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('[SETTLEMENT] Generation failed', [
                    'supplier_user_id' => $supplierUserId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $settlements;
    }

    // ─────────────────────────────────────────────
    // Approval
    // ─────────────────────────────────────────────

    /**
     * Approve a settlement.
     *
     * [PHASE-3] Maker-checker: creator cannot approve.
     */
    public function approveSettlement(Settlement $settlement, User $user)
    {
        $statusValue = $settlement->status instanceof SettlementStatus
            ? $settlement->status->value : $settlement->status;

        if (! in_array($statusValue, [
            SettlementStatus::Draft->value,
            SettlementStatus::Pending->value,
            SettlementStatus::PendingApproval->value,
        ], true)) {
            throw new \DomainException("Settlement cannot be approved (status: {$statusValue})");
        }

        // [PHASE-3] Maker-checker: creator cannot approve
        if ($settlement->created_by && $settlement->created_by === $user->id) {
            Log::warning('[SETTLEMENT] Maker-checker violation: creator tried to approve', [
                'settlement_number' => $settlement->settlement_number,
                'user_id' => $user->id,
            ]);
            throw new \DomainException('Maker-checker violation: settlement creator cannot approve their own settlement.');
        }

        $settlement->status = SettlementStatus::Approved->value;
        $settlement->approved_by = $user->id;
        $settlement->approved_at = now();
        $settlement->save();

        Log::info('[SETTLEMENT] Approved', [
            'settlement_number' => $settlement->settlement_number,
            'approved_by' => $user->id,
            'created_by' => $settlement->created_by,
        ]);

        return $settlement;
    }

    /**
     * Batch approve settlements.
     */
    public function batchApprove(array $settlementIds, User $user)
    {
        $settlements = Settlement::whereIn('id', $settlementIds)
            ->whereIn('status', [
                SettlementStatus::Draft->value,
                SettlementStatus::Pending->value,
                SettlementStatus::PendingApproval->value,
            ])
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
            'success' => $processed > 0,
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    // ─────────────────────────────────────────────
    // Payment / Mark as Paid
    // ─────────────────────────────────────────────

    /**
     * Mark settlement as paid and create financial entries.
     *
     * [PHASE-3] Hardened:
     * - Maker-checker: approver cannot pay
     * - Full DB transaction: settlement status + payout + accounting entries
     * - Debit = credit validation
     * - Idempotency: rejects if payout already exists
     * - Settlement NOT marked paid if accounting fails
     */
    public function markSettlementAsPaid(Settlement $settlement, User $user)
    {
        $statusValue = $settlement->status instanceof SettlementStatus
            ? $settlement->status->value : $settlement->status;

        if ($statusValue !== SettlementStatus::Approved->value) {
            throw new \DomainException('Settlement must be approved before payment.');
        }

        // [PHASE-3] Maker-checker: approver cannot pay
        if ($settlement->approved_by && $settlement->approved_by === $user->id) {
            Log::warning('[SETTLEMENT] Maker-checker violation: approver tried to pay', [
                'settlement_number' => $settlement->settlement_number,
                'user_id' => $user->id,
            ]);
            throw new \DomainException('Maker-checker violation: settlement approver cannot mark the same settlement as paid.');
        }

        // [PHASE-3] Idempotency: check if payout already exists for this settlement
        $existingPayout = SupplierPayout::where('settlement_id', $settlement->id)->first();
        if ($existingPayout) {
            Log::warning('[SETTLEMENT] Duplicate payout attempt blocked', [
                'settlement_number' => $settlement->settlement_number,
                'existing_payout_id' => $existingPayout->id,
            ]);
            throw new \DomainException("Payout already exists for settlement #{$settlement->settlement_number}.");
        }

        // [PHASE-3] Wrap everything in a single transaction
        DB::beginTransaction();
        try {
            $payout = $this->createPayoutForSettlement($settlement, $user);

            $settlement->status = SettlementStatus::Paid->value;
            $settlement->paid_at = now();
            $settlement->paid_by = $user->id;
            $settlement->save();

            DB::commit();

            Log::info('[SETTLEMENT] Marked as paid', [
                'settlement_number' => $settlement->settlement_number,
                'paid_by' => $user->id,
                'payout_amount' => $payout->amount,
                'payout_id' => $payout->id,
            ]);

            return $settlement;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[SETTLEMENT] Payment failed (rolled back)', [
                'settlement_number' => $settlement->settlement_number,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // Payout & Accounting Entries
    // ─────────────────────────────────────────────

    /**
     * Create payout record and double-entry accounting entries.
     *
     * [PHASE-3] Hardened:
     * - Throws on missing accounts (no silent skip)
     * - Validates debit = credit after entry creation
     * - Returns the payout record for caller verification
     */
    protected function createPayoutForSettlement(Settlement $settlement, User $user): SupplierPayout
    {
        $merchant = Merchant::where('user_id', $settlement->supplier_user_id)->first();
        if (! $merchant) {
            throw new \RuntimeException("Merchant record not found for user {$settlement->supplier_user_id}");
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

        // Create Financial Transaction
        $fTransaction = FTransaction::create([
            'uuid' => (string) Str::uuid(),
            'supplier_id' => $merchant->id,
            'user_id' => $user->id,
            'transaction_type' => 'payout',
            'amount' => $payout->amount,
            'status' => 'approved',
            'transaction_date' => now(),
            'notes' => "Payout for Settlement #{$settlement->settlement_number}",
        ]);

        // Resolve accounts from config
        $apId = config('financial.accounts.accounts_payable');
        $bankId = config('financial.accounts.bank_account');

        $supplierAccount = FAccounts::where('id', $apId)->first();
        if (! $supplierAccount) {
            throw new \RuntimeException("FAccounts ID {$apId} (accounts_payable) not found. Cannot create payout entries.");
        }

        $bankAccount = FAccounts::where('id', $bankId)->first();
        if (! $bankAccount) {
            throw new \RuntimeException("FAccounts ID {$bankId} (bank_account) not found. Cannot create payout entries.");
        }

        $periodLabel = $settlement->start_date->format('Y-m-d') . ' to ' . $settlement->end_date->format('Y-m-d');

        // Debit Accounts Payable (Liability decreases)
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
            'notes' => "Settlement Payout {$settlement->settlement_number} ({$periodLabel})",
        ]);

        // Credit Bank Account (Asset decreases)
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
            'notes' => "Settlement Payout {$settlement->settlement_number} ({$periodLabel})",
        ]);

        // [PHASE-3] Validate debit = credit for this transaction
        $entries = FEntry::where('transaction_id', $fTransaction->id)->get();
        $totalDebit = '0.00';
        $totalCredit = '0.00';
        foreach ($entries as $entry) {
            $totalDebit = Money::add($totalDebit, $entry->debit);
            $totalCredit = Money::add($totalCredit, $entry->credit);
        }

        if (Money::compare($totalDebit, $totalCredit) !== 0) {
            throw new \RuntimeException(
                "Accounting imbalance for transaction #{$fTransaction->id}: "
                . "debit={$totalDebit} != credit={$totalCredit}"
            );
        }

        return $payout;
    }

    // ─────────────────────────────────────────────
    // Cancellation
    // ─────────────────────────────────────────────

    /**
     * Cancel a settlement. Cannot cancel paid settlements.
     */
    public function cancelSettlement(Settlement $settlement, $reason, User $user)
    {
        $statusValue = $settlement->status instanceof SettlementStatus
            ? $settlement->status->value : $settlement->status;

        if ($statusValue === SettlementStatus::Paid->value) {
            throw new \DomainException(
                'Cannot cancel a paid settlement. Use the refund/reversal process instead.'
            );
        }

        DB::beginTransaction();
        try {
            $settlement->status = SettlementStatus::Cancelled->value;
            $settlement->notes = ($settlement->notes ? $settlement->notes . "\n" : '')
                . "Cancelled by user #{$user->id}: {$reason}";
            $settlement->save();

            // Unlink orders so they can be included in future settlements
            Order::where('settlement_id', $settlement->id)
                ->update(['settlement_id' => null]);

            DB::commit();

            Log::info('[SETTLEMENT] Cancelled', [
                'settlement_number' => $settlement->settlement_number,
                'cancelled_by' => $user->id,
                'reason' => $reason,
            ]);

            return $settlement;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─────────────────────────────────────────────
    // Batch Payout
    // ─────────────────────────────────────────────

    /**
     * Process batch payout for multiple settlements.
     * All-or-nothing transaction strategy.
     */
    public function processBatchPayout(array $settlementIds, User $user)
    {
        $settlements = Settlement::whereIn('id', $settlementIds)->get();

        if ($settlements->count() !== count($settlementIds)) {
            return ['success' => false, 'error' => 'Some settlements were not found.'];
        }

        foreach ($settlements as $settlement) {
            $sv = $settlement->status instanceof SettlementStatus
                ? $settlement->status->value : $settlement->status;
            if ($sv !== SettlementStatus::Approved->value) {
                return [
                    'success' => false,
                    'error' => "Settlement {$settlement->settlement_number} is not in approved status.",
                ];
            }
        }

        DB::beginTransaction();
        try {
            $totalAmount = '0.00';
            $processedCount = 0;
            $successIds = [];

            foreach ($settlements as $settlement) {
                $this->markSettlementAsPaid($settlement, $user);
                $totalAmount = Money::add($totalAmount, $settlement->payable_amount);
                $processedCount++;
                $successIds[] = $settlement->id;
            }

            DB::commit();

            Log::info('[SETTLEMENT] Batch payout completed', [
                'count' => $processedCount,
                'total_amount' => $totalAmount,
                'paid_by' => $user->id,
            ]);

            return [
                'success' => true,
                'processed' => $processedCount,
                'total_amount' => $totalAmount,
                'settlement_ids' => $successIds,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[SETTLEMENT] Batch payout failed (rolled back)', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return ['success' => false, 'error' => 'Batch processing failed: ' . $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────
    // Calculation Helpers
    // ─────────────────────────────────────────────

    public function getDeliveredOrdersForSettlement($supplierUserId, Carbon $startDate, Carbon $endDate)
    {
        return Order::where('seller_id', $supplierUserId)
            ->where('delivery_status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->whereNull('settlement_id')
            ->get();
    }

    public function getAllEligibleUnsettledOrders()
    {
        return Order::where('delivery_status', 'delivered')
            ->whereNull('settlement_id')
            ->get();
    }

    public function calculateSettlementAmount($orders)
    {
        $totalAmount = '0.00';
        $commissionAmount = '0.00';

        foreach ($orders as $order) {
            $totalAmount = Money::add($totalAmount, $order->grand_total);
            $commissionAmount = Money::add($commissionAmount, $order->commission_amount ?? 0);
        }

        $payableAmount = Money::max(
            '0.00',
            Money::subtract($totalAmount, $commissionAmount)
        );

        return [
            'total_amount' => $totalAmount,
            'commission_amount' => $commissionAmount,
            'payable_amount' => $payableAmount,
        ];
    }

    public function generateSettlementNumber($supplierUserId, Carbon $settlementDate)
    {
        return 'SETT-' . $settlementDate->format('Ymd') . '-' . $supplierUserId;
    }

    public function calculateSettlementDate(Carbon $periodEndDate)
    {
        return $periodEndDate->copy()->next(Carbon::TUESDAY);
    }

    public function getCurrentSettlementPeriod()
    {
        $now = Carbon::now();
        $start = $now->copy()->previous(Carbon::TUESDAY);
        if ($now->dayOfWeek == Carbon::TUESDAY) {
            $start = $now->copy()->subWeek()->startOfDay();
        } else {
            $start = $now->copy()->previous(Carbon::TUESDAY)->startOfDay();
        }
        $end = $start->copy()->addDays(6)->endOfDay();
        $settlementDate = $end->copy()->next(Carbon::TUESDAY);

        return ['start_date' => $start, 'end_date' => $end, 'settlement_date' => $settlementDate];
    }

    public function getPreviousSettlementPeriod()
    {
        $now = Carbon::now();
        $end = $now->copy()->previous(Carbon::TUESDAY);
        if ($now->dayOfWeek == Carbon::TUESDAY) {
            $end = $now->copy()->subWeek()->startOfDay();
        } else {
            $end = $now->copy()->previous(Carbon::TUESDAY)->startOfDay();
        }
        $start = $end->copy()->subDays(6)->endOfDay();
        $settlementDate = $start->copy()->next(Carbon::TUESDAY);

        return ['start_date' => $start, 'end_date' => $end, 'settlement_date' => $settlementDate];
    }
}
