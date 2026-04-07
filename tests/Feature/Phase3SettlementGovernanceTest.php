<?php

namespace Tests\Feature;

use App\Helpers\Money;
use App\Models\FAccounts;
use App\Models\FEntry;
use App\Models\FTransaction;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\SupplierPayout;
use App\Models\User;
use App\Services\Finance\ReconciliationService;
use App\Services\Finance\RefundReversalService;
use App\Services\Finance\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3 Settlement Governance Tests
 *
 * Validates maker-checker, idempotency, atomicity, accounting integrity,
 * reconciliation, and refund guards.
 */
class Phase3SettlementGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $type = 'admin'): User
    {
        return User::forceCreate([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'business_name' => fake()->unique()->company(),
            'phone_number' => fake()->unique()->numerify('05########'),
            'user_type' => $type,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }

    private function createSettlement(User $creator, string $status = 'draft', array $overrides = []): Settlement
    {
        return Settlement::forceCreate(array_merge([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'settlement_number' => 'SETT-' . now()->format('Ymd') . '-' . $creator->id . '-' . rand(100, 999),
            'supplier_user_id' => $creator->id,
            'start_date' => now()->subWeek(),
            'end_date' => now()->subDay(),
            'settlement_date' => now(),
            'total_amount' => '1000.00',
            'commission_amount' => '100.00',
            'payable_amount' => '900.00',
            'status' => $status,
            'created_by' => $creator->id,
        ], $overrides));
    }

    // ─────────────────────────────────────────────
    // Maker-Checker Tests
    // ─────────────────────────────────────────────

    public function test_creator_cannot_approve_own_settlement(): void
    {
        $creator = $this->createUser();
        $settlement = $this->createSettlement($creator, 'draft');

        $service = app(SettlementService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Maker-checker violation');
        $service->approveSettlement($settlement, $creator);
    }

    public function test_different_user_can_approve_settlement(): void
    {
        $creator = $this->createUser();
        $approver = $this->createUser();
        $settlement = $this->createSettlement($creator, 'draft');

        $service = app(SettlementService::class);
        $result = $service->approveSettlement($settlement, $approver);

        $this->assertEquals('approved', $result->status instanceof \App\Enums\SettlementStatus
            ? $result->status->value : $result->status);
        $this->assertEquals($approver->id, $result->approved_by);
    }

    public function test_approver_cannot_pay_same_settlement(): void
    {
        $creator = $this->createUser();
        $approver = $this->createUser();
        $settlement = $this->createSettlement($creator, 'approved', [
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $service = app(SettlementService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Maker-checker violation');
        $service->markSettlementAsPaid($settlement, $approver);
    }

    public function test_different_user_can_pay_approved_settlement(): void
    {
        $creator = $this->createUser();
        $approver = $this->createUser();
        $payer = $this->createUser();

        // Create merchant for the supplier
        Merchant::forceCreate([
            'user_id' => $creator->id,
            'status' => 'approved',
        ]);

        // Create required FAccounts
        FAccounts::forceCreate([
            'id' => config('financial.accounts.accounts_payable', 2400),
            'account_name' => 'Accounts Payable',
            'account_type1' => 'liability',
            'status' => 'active',
        ]);
        FAccounts::forceCreate([
            'id' => config('financial.accounts.bank_account', 1201),
            'account_name' => 'Bank Account',
            'account_type1' => 'asset',
            'status' => 'active',
        ]);

        $settlement = $this->createSettlement($creator, 'approved', [
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $service = app(SettlementService::class);
        $result = $service->markSettlementAsPaid($settlement, $payer);

        $this->assertEquals('paid', $result->status instanceof \App\Enums\SettlementStatus
            ? $result->status->value : $result->status);
        $this->assertEquals($payer->id, $result->paid_by);
    }

    // ─────────────────────────────────────────────
    // Idempotency Tests
    // ─────────────────────────────────────────────

    public function test_duplicate_settlement_generation_prevented(): void
    {
        $supplier = $this->createUser('merchant');
        $creator = $this->createUser();
        $startDate = Carbon::parse('2026-03-25');
        $endDate = Carbon::parse('2026-03-31');

        // Create existing settlement for this supplier+period
        $this->createSettlement($creator, 'draft', [
            'supplier_user_id' => $supplier->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $service = app(SettlementService::class);
        $result = $service->generateSettlementsForPeriod($startDate, $endDate, $creator);

        // Should return empty — duplicate skipped
        $this->assertTrue($result->isEmpty());
    }

    public function test_duplicate_payout_prevented(): void
    {
        $creator = $this->createUser();
        $approver = $this->createUser();
        $payer = $this->createUser();

        Merchant::forceCreate(['user_id' => $creator->id, 'status' => 'approved']);
        FAccounts::forceCreate(['id' => config('financial.accounts.accounts_payable', 2400), 'account_name' => 'AP', 'account_type1' => 'liability', 'status' => 'active']);
        FAccounts::forceCreate(['id' => config('financial.accounts.bank_account', 1201), 'account_name' => 'Bank', 'account_type1' => 'asset', 'status' => 'active']);

        $settlement = $this->createSettlement($creator, 'approved', [
            'approved_by' => $approver->id,
        ]);

        // Create an existing payout for this settlement
        SupplierPayout::forceCreate([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'supplier_id' => 1,
            'settlement_id' => $settlement->id,
            'amount' => '900.00',
            'status' => 'completed',
            'payout_date' => now(),
            'created_by' => $payer->id,
        ]);

        $service = app(SettlementService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Payout already exists');
        $service->markSettlementAsPaid($settlement, $payer);
    }

    // ─────────────────────────────────────────────
    // Accounting Integrity Tests
    // ─────────────────────────────────────────────

    public function test_accounting_entries_created_on_payment(): void
    {
        $creator = $this->createUser();
        $approver = $this->createUser();
        $payer = $this->createUser();

        Merchant::forceCreate(['user_id' => $creator->id, 'status' => 'approved']);
        FAccounts::forceCreate(['id' => config('financial.accounts.accounts_payable', 2400), 'account_name' => 'AP', 'account_type1' => 'liability', 'status' => 'active']);
        FAccounts::forceCreate(['id' => config('financial.accounts.bank_account', 1201), 'account_name' => 'Bank', 'account_type1' => 'asset', 'status' => 'active']);

        $settlement = $this->createSettlement($creator, 'approved', [
            'approved_by' => $approver->id,
        ]);

        $service = app(SettlementService::class);
        $service->markSettlementAsPaid($settlement, $payer);

        // Verify payout created
        $payout = SupplierPayout::where('settlement_id', $settlement->id)->first();
        $this->assertNotNull($payout);
        $this->assertEquals('900.00', $payout->amount);

        // Verify accounting entries
        $transaction = FTransaction::where('transaction_type', 'payout')
            ->where('amount', '900.00')
            ->first();
        $this->assertNotNull($transaction);

        $entries = FEntry::where('transaction_id', $transaction->id)->get();
        $this->assertCount(2, $entries);

        // Verify debit = credit
        $totalDebit = '0.00';
        $totalCredit = '0.00';
        foreach ($entries as $entry) {
            $totalDebit = Money::add($totalDebit, $entry->debit);
            $totalCredit = Money::add($totalCredit, $entry->credit);
        }
        $this->assertEquals(0, Money::compare($totalDebit, $totalCredit));
        $this->assertEquals('900.00', $totalDebit);
    }

    public function test_payment_fails_if_accounts_missing(): void
    {
        $creator = $this->createUser();
        $approver = $this->createUser();
        $payer = $this->createUser();

        Merchant::forceCreate(['user_id' => $creator->id, 'status' => 'approved']);
        // Deliberately NOT creating FAccounts

        $settlement = $this->createSettlement($creator, 'approved', [
            'approved_by' => $approver->id,
        ]);

        $service = app(SettlementService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not found');
        $service->markSettlementAsPaid($settlement, $payer);

        // Verify settlement is NOT marked as paid (rolled back)
        $settlement->refresh();
        $this->assertNotEquals('paid', $settlement->status instanceof \App\Enums\SettlementStatus
            ? $settlement->status->value : $settlement->status);
    }

    public function test_settlement_not_marked_paid_if_accounting_fails(): void
    {
        $creator = $this->createUser();
        $approver = $this->createUser();
        $payer = $this->createUser();

        Merchant::forceCreate(['user_id' => $creator->id, 'status' => 'approved']);
        // Only create one account — the second will fail
        FAccounts::forceCreate(['id' => config('financial.accounts.accounts_payable', 2400), 'account_name' => 'AP', 'account_type1' => 'liability', 'status' => 'active']);
        // Bank account missing

        $settlement = $this->createSettlement($creator, 'approved', [
            'approved_by' => $approver->id,
        ]);

        $service = app(SettlementService::class);

        try {
            $service->markSettlementAsPaid($settlement, $payer);
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Settlement must still be 'approved' (not 'paid') due to rollback
        $settlement->refresh();
        $statusValue = $settlement->status instanceof \App\Enums\SettlementStatus
            ? $settlement->status->value : $settlement->status;
        $this->assertEquals('approved', $statusValue);

        // No payout should exist
        $this->assertNull(SupplierPayout::where('settlement_id', $settlement->id)->first());
    }

    // ─────────────────────────────────────────────
    // Reconciliation Tests
    // ─────────────────────────────────────────────

    public function test_reconciliation_passes_for_correct_settlement(): void
    {
        $service = new ReconciliationService();

        $user = $this->createUser();
        // No commission in test schema — set commission to 0
        $settlement = $this->createSettlement($user, 'draft', [
            'total_amount' => '500.00',
            'commission_amount' => '0.00',
            'payable_amount' => '500.00',
        ]);

        // Insert orders using DB::table (orders table lacks commission_amount in test SQLite)
        DB::table('orders')->insert([
            'user_id' => $user->id, 'seller_id' => $user->id,
            'grand_total' => '300.00',
            'settlement_id' => $settlement->id,
            'product_details' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('orders')->insert([
            'user_id' => $user->id, 'seller_id' => $user->id,
            'grand_total' => '200.00',
            'settlement_id' => $settlement->id,
            'product_details' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $result = $service->reconcileSettlement($settlement);
        $this->assertEquals('pass', $result['status']);
        $this->assertEmpty($result['issues']);
    }

    public function test_reconciliation_detects_total_mismatch(): void
    {
        $service = new ReconciliationService();

        $user = $this->createUser();
        $settlement = $this->createSettlement($user, 'draft', [
            'total_amount' => '9999.00',
            'commission_amount' => '0.00',
            'payable_amount' => '9999.00',
        ]);

        DB::table('orders')->insert([
            'user_id' => $user->id, 'seller_id' => $user->id,
            'grand_total' => '100.00',
            'settlement_id' => $settlement->id,
            'product_details' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $result = $service->reconcileSettlement($settlement);
        $this->assertEquals('fail', $result['status']);
        $this->assertNotEmpty($result['issues']);
        $this->assertEquals('total_amount_vs_orders', $result['issues'][0]['check']);
    }

    // ─────────────────────────────────────────────
    // Refund / Reversal Guard Tests
    // ─────────────────────────────────────────────

    public function test_refund_blocked_for_settled_order(): void
    {
        $user = $this->createUser();
        $settlement = $this->createSettlement($user, 'paid');

        // Insert a real order record linked to the paid settlement
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $user->id, 'seller_id' => $user->id,
            'grand_total' => '100.00',
            'settlement_id' => $settlement->id,
            'product_details' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $order = Order::find($orderId);

        $refundService = new RefundReversalService();
        $result = $refundService->canProcessRefund($order);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('paid settlement', $result['reason']);
    }

    public function test_settlement_reversal_throws(): void
    {
        $user = $this->createUser();
        $settlement = $this->createSettlement($user, 'paid');

        $refundService = new RefundReversalService();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not yet implemented');
        $refundService->reverseSettlementPayout($settlement);
    }

    public function test_paid_settlement_cannot_be_cancelled(): void
    {
        $user = $this->createUser();
        $settlement = $this->createSettlement($user, 'paid');

        $service = app(SettlementService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot cancel a paid settlement');
        $service->cancelSettlement($settlement, 'test', $user);
    }

    // ─────────────────────────────────────────────
    // Status Transition Tests
    // ─────────────────────────────────────────────

    public function test_cannot_approve_already_paid_settlement(): void
    {
        $user = $this->createUser();
        $approver = $this->createUser();
        $settlement = $this->createSettlement($user, 'paid');

        $service = app(SettlementService::class);

        $this->expectException(\DomainException::class);
        $service->approveSettlement($settlement, $approver);
    }

    public function test_cannot_pay_draft_settlement(): void
    {
        $user = $this->createUser();
        $payer = $this->createUser();
        $settlement = $this->createSettlement($user, 'draft');

        $service = app(SettlementService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('must be approved');
        $service->markSettlementAsPaid($settlement, $payer);
    }

    // ─────────────────────────────────────────────
    // Service / File Existence Tests
    // ─────────────────────────────────────────────

    public function test_reconciliation_service_exists(): void
    {
        $this->assertFileExists(app_path('Services/Finance/ReconciliationService.php'));
    }

    public function test_refund_reversal_service_exists(): void
    {
        $this->assertFileExists(app_path('Services/Finance/RefundReversalService.php'));
    }

    public function test_idempotency_migration_exists(): void
    {
        $migrations = glob(database_path('migrations/*settlement_idempotency*'));
        $this->assertNotEmpty($migrations, 'Idempotency migration must exist');
    }
}
