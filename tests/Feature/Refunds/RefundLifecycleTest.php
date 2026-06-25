<?php

namespace Tests\Feature\Refunds;

use App\Models\RefundRequest;
use App\Models\User;
use App\Services\Finance\RefundApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function createApprover(): User
    {
        $user = User::factory()->create([
            'user_type' => 'admin',
            'sensitive_permissions' => ['refund_management'],
        ]);
        $this->actingAs($user);

        return $user;
    }

    private function service(): RefundApprovalService
    {
        return app(RefundApprovalService::class);
    }

    public function test_approve_pending_refund(): void
    {
        $refund = RefundRequest::factory()->create(['refund_status' => 'pending']);
        $admin = $this->createApprover();

        $this->service()->approve($refund, $admin);

        $refund->refresh();
        $this->assertSame('approved', $refund->refund_status);
    }

    public function test_reject_pending_refund(): void
    {
        $refund = RefundRequest::factory()->create(['refund_status' => 'pending']);
        $admin = $this->createApprover();

        $this->service()->reject($refund, $admin, 'Customer requested cancellation');

        $refund->refresh();
        $this->assertSame('rejected', $refund->refund_status);
    }

    public function test_approve_already_approved_refund_throws(): void
    {
        $refund = RefundRequest::factory()->create(['refund_status' => 'approved']);
        $admin = $this->createApprover();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/already approved/');

        $this->service()->approve($refund, $admin);
    }

    public function test_reject_already_rejected_refund_throws(): void
    {
        $refund = RefundRequest::factory()->create(['refund_status' => 'rejected']);
        $admin = $this->createApprover();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/already rejected/');

        $this->service()->reject($refund, $admin);
    }

    public function test_approval_adds_audit_trail_entry(): void
    {
        $refund = RefundRequest::factory()->create(['refund_status' => 'pending']);
        $admin = $this->createApprover();

        $this->service()->approve($refund, $admin);

        $this->assertDatabaseHas('audit_trails', [
            'entity_type' => 'RefundRequest',
            'entity_id' => $refund->id,
            'event_type' => 'update',
        ]);
    }

    public function test_rejection_records_reason_in_audit_trail(): void
    {
        $refund = RefundRequest::factory()->create(['refund_status' => 'pending']);
        $admin = $this->createApprover();

        $this->service()->reject($refund, $admin, 'Duplicate request');

        $this->assertDatabaseHas('audit_trails', [
            'entity_type' => 'RefundRequest',
            'entity_id' => $refund->id,
            'event_type' => 'update',
        ]);
    }

    public function test_user_without_permission_cannot_approve(): void
    {
        $refund = RefundRequest::factory()->create(['refund_status' => 'pending', 'assigned_to' => null]);
        $user = User::factory()->create(['user_type' => 'employee', 'sensitive_permissions' => []]);
        $this->actingAs($user);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->service()->approve($refund, $user);
    }
}
