<?php

namespace Tests\Feature\Settlements;

use App\Models\FAccounts;
use App\Models\FTransaction;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\User;
use App\Services\Finance\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementApprovalTest extends TestCase
{
    use RefreshDatabase;

    private SettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SettlementService::class);
    }

    public function test_approve_draft_settlement(): void
    {
        $settlement = Settlement::factory()->create(['status' => 'draft']);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);

        $this->runInApprovalContext(fn () => $this->service->approveSettlement($settlement, $admin));

        $settlement->refresh();

        $this->assertSame('approved', $settlement->status);
        $this->assertSame($admin->id, $settlement->approved_by);
        $this->assertNotNull($settlement->approved_at);
    }

    public function test_cannot_approve_paid_settlement(): void
    {
        $settlement = Settlement::factory()->paid()->create();
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Settlement cannot be approved');

        $this->runInApprovalContext(fn () => $this->service->approveSettlement($settlement, $admin));
    }

    public function test_batch_approve_draft_settlements(): void
    {
        $settlements = Settlement::factory()->count(3)->create(['status' => 'draft']);
        $ids = $settlements->pluck('id')->toArray();
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);

        $result = $this->service->batchApprove($ids, $admin);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['processed']);
        $this->assertSame(0, $result['failed']);

        foreach ($settlements as $s) {
            $s->refresh();
            $this->assertSame('approved', $s->status);
        }
    }

    public function test_batch_approve_skips_already_paid(): void
    {
        $draft = Settlement::factory()->create(['status' => 'draft']);
        $paid = Settlement::factory()->paid()->create();
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);

        $result = $this->service->batchApprove([$draft->id, $paid->id], $admin);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['processed']);
        $this->assertSame(0, $result['failed']);
    }

    public function test_batch_approve_empty_list(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);

        $result = $this->service->batchApprove([999], $admin);

        $this->assertFalse($result['success']);
        $this->assertSame('No settlements found or none are in draft/pending_approval status.', $result['error']);
    }

    public function test_cancel_draft_settlement_unlinks_orders(): void
    {
        $settlement = Settlement::factory()->create(['status' => 'draft']);
        $order = \App\Models\Order::factory()->create(['settlement_id' => $settlement->id]);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);

        $this->runInApprovalContext(fn () => $this->service->cancelSettlement($settlement, 'Wrong period', $admin));

        $settlement->refresh();
        $this->assertSame('cancelled', $settlement->status);
        $this->assertStringContainsString('Wrong period', $settlement->notes);

        $order->refresh();
        $this->assertNull($order->settlement_id);
    }

    public function test_cannot_cancel_paid_settlement(): void
    {
        $settlement = Settlement::factory()->paid()->create();
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot cancel a paid settlement');

        $this->runInApprovalContext(fn () => $this->service->cancelSettlement($settlement, 'nope', $admin));
    }

    public function test_mark_settlement_paid_creates_payout_and_financial_entries(): void
    {
        $supplier = User::factory()->create(['user_type' => 'merchant']);
        $merchant = Merchant::factory()->create(['user_id' => $supplier->id]);

        FAccounts::create([
            'id' => 2400,
            'account_name' => 'Accounts Payable',
            'account_type1' => 2,
            'account_type2' => 2,
            'status' => 'active',
        ]);
        FAccounts::create([
            'id' => 1201,
            'account_name' => 'Bank Account',
            'account_type1' => 2,
            'account_type2' => 1,
            'status' => 'active',
        ]);
        $supplierAccount = FAccounts::find(2400);
        $bankAccount = FAccounts::find(1201);

        $settlement = Settlement::factory()->create([
            'status' => 'approved',
            'supplier_user_id' => $supplier->id,
            'total_amount' => 5000,
            'commission_amount' => 250,
            'payable_amount' => 4750,
        ]);

        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);

        $result = $this->runInApprovalContext(fn () => $this->service->markSettlementAsPaid($settlement, $admin));

        $this->assertSame('paid', $result->status);
        $this->assertNotNull($result->paid_at);
        $this->assertSame($admin->id, $result->paid_by);

        $this->assertDatabaseHas('supplier_payouts', [
            'settlement_id' => $settlement->id,
            'amount' => 4750.00,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('f_transactions', [
            'supplier_id' => $merchant->id,
            'transaction_type' => 'payout',
            'amount' => 4750.00,
        ]);

        $transaction = FTransaction::where('supplier_id', $merchant->id)->firstOrFail();

        $this->assertDatabaseHas('f_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $supplierAccount->id,
            'debit' => 4750.00,
            'credit' => 0.00,
        ]);

        $this->assertDatabaseHas('f_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $bankAccount->id,
            'debit' => 0.00,
            'credit' => 4750.00,
        ]);
    }

    public function test_mark_settlement_paid_fails_without_approved_status(): void
    {
        $settlement = Settlement::factory()->create(['status' => 'draft']);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Settlement must be approved before payment');

        $this->runInApprovalContext(fn () => $this->service->markSettlementAsPaid($settlement, $admin));
    }

    public function test_process_batch_payout(): void
    {
        $supplier = User::factory()->create(['user_type' => 'merchant']);
        Merchant::factory()->create(['user_id' => $supplier->id]);

        FAccounts::create(['id' => 2400, 'account_name' => 'AP', 'account_type1' => 2, 'account_type2' => 2, 'status' => 'active']);
        FAccounts::create(['id' => 1201, 'account_name' => 'Bank', 'account_type1' => 2, 'account_type2' => 1, 'status' => 'active']);

        $s1 = Settlement::factory()->create([
            'status' => 'approved',
            'supplier_user_id' => $supplier->id,
            'payable_amount' => 1000,
        ]);
        $s2 = Settlement::factory()->create([
            'status' => 'approved',
            'supplier_user_id' => $supplier->id,
            'payable_amount' => 2000,
        ]);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);

        $result = $this->runInApprovalContext(fn () => $this->service->processBatchPayout([$s1->id, $s2->id], $admin));

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['processed']);
        $this->assertEquals(3000, $result['total_amount']);

        $s1->refresh();
        $s2->refresh();
        $this->assertSame('paid', $s1->status);
        $this->assertSame('paid', $s2->status);
    }

    public function test_process_batch_payout_fails_if_not_approved(): void
    {
        $supplier = User::factory()->create(['user_type' => 'merchant']);
        Merchant::factory()->create(['user_id' => $supplier->id]);
        FAccounts::create(['id' => 2400, 'account_name' => 'AP', 'account_type1' => 2, 'account_type2' => 2, 'status' => 'active']);
        FAccounts::create(['id' => 1201, 'account_name' => 'Bank', 'account_type1' => 2, 'account_type2' => 1, 'status' => 'active']);

        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($admin);
        $draftSettlement = Settlement::factory()->create([
            'status' => 'draft',
            'supplier_user_id' => $supplier->id,
        ]);

        $result = $this->service->processBatchPayout([$draftSettlement->id], $admin);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not in approved status', $result['error']);
    }
}
