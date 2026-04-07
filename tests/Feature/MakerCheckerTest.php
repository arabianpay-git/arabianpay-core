<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Models\ApprovalRequest;
use App\Models\CustomerCreditLimit;
use App\Models\RefundRequest;
use App\Models\Settlement;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\Finance\CreditLimitApprovalService;
use App\Services\Finance\RefundApprovalService;
use App\Services\RiskOverrideApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive Maker-Checker tests across all domains.
 */
class MakerCheckerTest extends TestCase
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

    // ═══════════════════════════════════════
    // GENERIC APPROVAL SERVICE
    // ═══════════════════════════════════════

    public function test_submit_creates_pending_approval(): void
    {
        $maker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(ApprovalService::class);
        $approval = $service->submitForApproval(
            $creditLimit, 'update', $maker,
            ['limit_arabianpay_after' => '10000.00'],
            'Business growth',
            ['limit_arabianpay_after' => '5000.00']
        );

        $this->assertEquals(ApprovalStatus::Pending, $approval->status);
        $this->assertEquals($maker->id, $approval->requested_by);
        $this->assertEquals('update', $approval->action_type);
        $this->assertNotNull($approval->uuid);
    }

    public function test_self_approval_blocked(): void
    {
        $maker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(ApprovalService::class);
        $approval = $service->submitForApproval($creditLimit, 'update', $maker);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cannot approve your own');
        $service->approve($approval, $maker);
    }

    public function test_different_user_can_approve(): void
    {
        $maker = $this->createUser();
        $checker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(ApprovalService::class);
        $approval = $service->submitForApproval($creditLimit, 'update', $maker);
        $approved = $service->approve($approval, $checker, 'Looks good');

        $this->assertEquals(ApprovalStatus::Approved, $approved->status);
        $this->assertEquals($checker->id, $approved->reviewed_by);
        $this->assertNotNull($approved->reviewed_at);
    }

    public function test_duplicate_pending_blocked(): void
    {
        $maker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(ApprovalService::class);
        $service->submitForApproval($creditLimit, 'update', $maker);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('pending approval request already exists');
        $service->submitForApproval($creditLimit, 'update', $maker);
    }

    public function test_rejected_request_cannot_be_approved(): void
    {
        $maker = $this->createUser();
        $checker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(ApprovalService::class);
        $approval = $service->submitForApproval($creditLimit, 'update', $maker);
        $service->reject($approval, $checker, 'Not justified');

        $this->expectException(\DomainException::class);
        $service->approve($approval->fresh(), $checker);
    }

    public function test_only_approved_can_be_executed(): void
    {
        $maker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(ApprovalService::class);
        $approval = $service->submitForApproval($creditLimit, 'update', $maker);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only approved');
        $service->markExecuted($approval, $maker);
    }

    public function test_cancel_own_request(): void
    {
        $maker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(ApprovalService::class);
        $approval = $service->submitForApproval($creditLimit, 'update', $maker);
        $cancelled = $service->cancel($approval, $maker);

        $this->assertEquals(ApprovalStatus::Cancelled, $cancelled->status);
    }

    // ═══════════════════════════════════════
    // CREDIT LIMIT MAKER-CHECKER
    // ═══════════════════════════════════════

    public function test_credit_limit_change_not_applied_before_approval(): void
    {
        $maker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(CreditLimitApprovalService::class);
        $service->proposeLimitChange(
            $creditLimit, ['limit_arabianpay_after' => '15000.00'], $maker, 'Growth'
        );

        // Value must NOT have changed
        $this->assertEquals('5000.00', $creditLimit->fresh()->limit_arabianpay_after);
    }

    public function test_credit_limit_applied_after_approval(): void
    {
        $maker = $this->createUser();
        $checker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(CreditLimitApprovalService::class);
        $approval = $service->proposeLimitChange(
            $creditLimit, ['limit_arabianpay_after' => '15000.00'], $maker, 'Growth'
        );

        $result = $service->approveAndApply($approval, $checker, 'Approved');
        $this->assertEquals('15000.00', $result->limit_arabianpay_after);
    }

    public function test_credit_limit_self_approval_blocked(): void
    {
        $maker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(CreditLimitApprovalService::class);
        $approval = $service->proposeLimitChange(
            $creditLimit, ['limit_arabianpay_after' => '15000.00'], $maker, 'Growth'
        );

        $this->expectException(\DomainException::class);
        $service->approveAndApply($approval, $maker);
    }

    public function test_credit_limit_rejection_preserves_old_value(): void
    {
        $maker = $this->createUser();
        $checker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(CreditLimitApprovalService::class);
        $approval = $service->proposeLimitChange(
            $creditLimit, ['limit_arabianpay_after' => '15000.00'], $maker, 'Growth'
        );

        $service->reject($approval, $checker, 'Insufficient justification');
        $this->assertEquals('5000.00', $creditLimit->fresh()->limit_arabianpay_after);
    }

    // ═══════════════════════════════════════
    // REFUND MAKER-CHECKER
    // ═══════════════════════════════════════

    public function test_refund_submitted_for_approval(): void
    {
        $maker = $this->createUser();
        $orderId = \Illuminate\Support\Facades\DB::table('orders')->insertGetId([
            'user_id' => $maker->id, 'seller_id' => $maker->id,
            'grand_total' => '1000.00', 'product_details' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $refund = RefundRequest::forceCreate([
            'user_id' => $maker->id, 'seller_id' => $maker->id,
            'order_id' => $orderId, 'refund_amount' => '500.00',
            'refund_status' => 'pending', 'reason' => 'Defective product',
        ]);

        $service = app(RefundApprovalService::class);
        $approval = $service->submitForApproval($refund, $maker, 'Product is defective');

        $this->assertEquals(ApprovalStatus::Pending, $approval->status);
        $this->assertEquals('approve_refund', $approval->action_type);
    }

    public function test_refund_self_approval_blocked(): void
    {
        $maker = $this->createUser();
        $orderId = \Illuminate\Support\Facades\DB::table('orders')->insertGetId([
            'user_id' => $maker->id, 'seller_id' => $maker->id,
            'grand_total' => '500.00', 'product_details' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $refund = RefundRequest::forceCreate([
            'user_id' => $maker->id, 'seller_id' => $maker->id,
            'order_id' => $orderId, 'refund_amount' => '500.00',
            'refund_status' => 'pending', 'reason' => 'Defective',
        ]);

        $service = app(RefundApprovalService::class);
        $approval = $service->submitForApproval($refund, $maker);

        $this->expectException(\DomainException::class);
        $service->approve($approval, $maker);
    }

    public function test_refund_approved_by_different_user(): void
    {
        $maker = $this->createUser();
        $checker = $this->createUser();
        $orderId = \Illuminate\Support\Facades\DB::table('orders')->insertGetId([
            'user_id' => $maker->id, 'seller_id' => $maker->id,
            'grand_total' => '500.00', 'product_details' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $refund = RefundRequest::forceCreate([
            'user_id' => $maker->id, 'seller_id' => $maker->id,
            'order_id' => $orderId, 'refund_amount' => '500.00',
            'refund_status' => 'pending', 'reason' => 'Defective',
        ]);

        $service = app(RefundApprovalService::class);
        $approval = $service->submitForApproval($refund, $maker);
        $result = $service->approve($approval, $checker, 'Confirmed defective');

        $this->assertEquals('approved', $result->refund_status instanceof \App\Enums\RefundStatus
            ? $result->refund_status->value : $result->refund_status);
    }

    // ═══════════════════════════════════════
    // RISK OVERRIDE MAKER-CHECKER
    // ═══════════════════════════════════════

    public function test_risk_override_request_logged_with_before_after(): void
    {
        $maker = $this->createUser();
        // Use User model as the risk-scored entity for simplicity
        $entity = $this->createUser('merchant');

        $service = app(RiskOverrideApprovalService::class);
        $approval = $service->requestOverride(
            $entity, 'risk_score', 65, 85, $maker, 'Recent positive data from SIMAH'
        );

        $this->assertEquals(ApprovalStatus::Pending, $approval->status);
        $this->assertEquals(65, $approval->before_state['old_value']);
        $this->assertEquals(85, $approval->payload['new_value']);
        $this->assertEquals('risk_score', $approval->payload['field']);
    }

    public function test_risk_override_self_approval_blocked(): void
    {
        $maker = $this->createUser();
        $entity = $this->createUser('merchant');

        $service = app(RiskOverrideApprovalService::class);
        $approval = $service->requestOverride($entity, 'risk_score', 65, 85, $maker, 'Reason');

        $this->expectException(\DomainException::class);
        $service->approveAndApply($approval, $maker);
    }

    public function test_risk_override_approved_by_different_user(): void
    {
        $maker = $this->createUser();
        $checker = $this->createUser();
        $entity = $this->createUser('merchant');

        $service = app(RiskOverrideApprovalService::class);
        $approval = $service->requestOverride($entity, 'is_manager', false, true, $maker, 'Promoted');
        $service->approveAndApply($approval, $checker, 'Confirmed promotion');

        // Field should be updated
        $this->assertTrue((bool) $entity->fresh()->is_manager);
    }

    // ═══════════════════════════════════════
    // SETTLEMENT MAKER-CHECKER (existing — verify still works)
    // ═══════════════════════════════════════

    public function test_settlement_creator_cannot_approve(): void
    {
        $creator = $this->createUser();
        $settlement = Settlement::forceCreate([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'settlement_number' => 'SETT-MC-' . rand(100, 999),
            'supplier_user_id' => $creator->id,
            'start_date' => now()->subWeek(), 'end_date' => now(),
            'settlement_date' => now(),
            'total_amount' => '1000.00', 'commission_amount' => '100.00', 'payable_amount' => '900.00',
            'status' => 'draft', 'created_by' => $creator->id,
        ]);

        $service = app(\App\Services\Finance\SettlementService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Maker-checker');
        $service->approveSettlement($settlement, $creator);
    }

    public function test_settlement_approver_cannot_pay(): void
    {
        $creator = $this->createUser();
        $approver = $this->createUser();
        $settlement = Settlement::forceCreate([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'settlement_number' => 'SETT-MC2-' . rand(100, 999),
            'supplier_user_id' => $creator->id,
            'start_date' => now()->subWeek(), 'end_date' => now(),
            'settlement_date' => now(),
            'total_amount' => '1000.00', 'commission_amount' => '100.00', 'payable_amount' => '900.00',
            'status' => 'approved', 'created_by' => $creator->id,
            'approved_by' => $approver->id, 'approved_at' => now(),
        ]);

        $service = app(\App\Services\Finance\SettlementService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Maker-checker');
        $service->markSettlementAsPaid($settlement, $approver);
    }

    // ═══════════════════════════════════════
    // STATUS TRANSITION SAFETY
    // ═══════════════════════════════════════

    public function test_invalid_status_transition_fails(): void
    {
        $maker = $this->createUser();
        $checker = $this->createUser();
        $creditLimit = CustomerCreditLimit::forceCreate([
            'user_id' => $maker->id,
            'limit_arabianpay_before' => '5000.00',
            'limit_arabianpay_after' => '5000.00',
        ]);

        $service = app(ApprovalService::class);
        $approval = $service->submitForApproval($creditLimit, 'update', $maker);
        $service->approve($approval, $checker);

        // Try to approve again — should fail
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot transition');
        $service->approve($approval->fresh(), $checker);
    }

    // ═══════════════════════════════════════
    // APPROVAL MODEL / INFRASTRUCTURE
    // ═══════════════════════════════════════

    public function test_approval_request_model_exists(): void
    {
        $this->assertFileExists(app_path('Models/ApprovalRequest.php'));
    }

    public function test_approval_service_exists(): void
    {
        $this->assertFileExists(app_path('Services/ApprovalService.php'));
    }

    public function test_has_approval_requests_trait_exists(): void
    {
        $this->assertFileExists(app_path('Traits/HasApprovalRequests.php'));
    }

    public function test_approval_enum_exists(): void
    {
        $this->assertFileExists(app_path('Enums/ApprovalStatus.php'));
        $values = array_column(ApprovalStatus::cases(), 'value');
        $this->assertContains('pending', $values);
        $this->assertContains('approved', $values);
        $this->assertContains('rejected', $values);
        $this->assertContains('executed', $values);
        $this->assertContains('cancelled', $values);
    }

    public function test_models_have_approval_trait(): void
    {
        $this->assertTrue(method_exists(Settlement::class, 'approvalRequests'));
        $this->assertTrue(method_exists(CustomerCreditLimit::class, 'approvalRequests'));
        $this->assertTrue(method_exists(RefundRequest::class, 'approvalRequests'));
    }

    public function test_approval_policy_exists(): void
    {
        $this->assertFileExists(app_path('Policies/ApprovalRequestPolicy.php'));
    }
}
