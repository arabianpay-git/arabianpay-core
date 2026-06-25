<?php

namespace Tests\Feature\Authorization;

use App\Models\SensitiveDataApproval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitiveDataAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_sensitive_data_access(): void
    {
        $requester = User::factory()->create(['user_type' => 'employee']);
        $this->actingAs($requester);

        $approval = SensitiveDataApproval::create([
            'requested_by' => $requester->id,
            'sensitive_permissions' => ['view_customer_id_number'],
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'status' => 'pending',
            'request_reason' => 'Customer verification needed',
        ]);

        $this->assertNotNull($approval->id);
        $this->assertSame('pending', $approval->status);
        $this->assertSame('Customer verification needed', $approval->request_reason);
    }

    public function test_active_scope_returns_only_active_approvals(): void
    {
        $requester = User::factory()->create(['user_type' => 'employee']);
        $this->actingAs($requester);

        SensitiveDataApproval::create([
            'requested_by' => $requester->id,
            'sensitive_permissions' => ['perm_a'],
            'status' => 'approved',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        SensitiveDataApproval::create([
            'requested_by' => $requester->id,
            'sensitive_permissions' => ['perm_b'],
            'status' => 'approved',
            'start_at' => now()->subDay(2),
            'end_at' => now()->subDay(1), // expired
        ]);

        SensitiveDataApproval::create([
            'requested_by' => $requester->id,
            'sensitive_permissions' => ['perm_c'],
            'status' => 'rejected',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $active = SensitiveDataApproval::active()->get();

        $this->assertCount(1, $active);
        $this->assertSame('perm_a', $active->first()->sensitive_permissions[0]);
    }

    public function test_has_sensitive_permission_checks_active_approval(): void
    {
        $requester = User::factory()->create([
            'user_type' => 'employee',
            'sensitive_permissions' => [],
        ]);
        $this->actingAs($requester);

        $this->assertFalse(hasSensitivePermission('view_customer_phone'));

        SensitiveDataApproval::create([
            'requested_by' => $requester->id,
            'sensitive_permissions' => ['view_customer_phone'],
            'status' => 'approved',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $this->assertTrue(hasSensitivePermission('view_customer_phone'));
    }

    public function test_expired_approval_does_not_grant_permission(): void
    {
        $requester = User::factory()->create([
            'user_type' => 'employee',
            'sensitive_permissions' => [],
        ]);
        $this->actingAs($requester);

        SensitiveDataApproval::create([
            'requested_by' => $requester->id,
            'sensitive_permissions' => ['view_customer_phone'],
            'status' => 'approved',
            'start_at' => now()->subDay(2),
            'end_at' => now()->subDay(1),
        ]);

        $this->assertFalse(hasSensitivePermission('view_customer_phone'));
    }

    public function test_approve_sensitive_request(): void
    {
        $requester = User::factory()->create(['user_type' => 'employee']);
        $approver = User::factory()->create(['user_type' => 'admin']);
        $this->actingAs($requester);

        $approval = SensitiveDataApproval::create([
            'requested_by' => $requester->id,
            'sensitive_permissions' => ['view_bank_account'],
            'status' => 'pending',
            'start_at' => now(),
            'end_at' => now()->addDay(),
            'request_reason' => 'Audit purpose',
        ]);

        $approval->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'decision_notes' => 'Approved for audit',
        ]);

        $approval->refresh();
        $this->assertSame('approved', $approval->status);
        $this->assertSame($approver->id, $approval->approved_by);
        $this->assertNotNull($approval->approved_at);
    }

    public function test_time_bounded_access_expires(): void
    {
        $requester = User::factory()->create([
            'user_type' => 'employee',
            'sensitive_permissions' => [],
        ]);
        $this->actingAs($requester);

        Carbon::setTestNow(now()->subDay());

        SensitiveDataApproval::create([
            'requested_by' => $requester->id,
            'sensitive_permissions' => ['time_limited_perm'],
            'status' => 'approved',
            'start_at' => now(),
            'end_at' => now()->addHours(2),
        ]);

        // Within time: has permission
        $this->assertTrue(hasSensitivePermission('time_limited_perm'));

        // Fast-forward past expiry
        Carbon::setTestNow(now()->addDays(2));

        $this->assertFalse(hasSensitivePermission('time_limited_perm'));

        Carbon::setTestNow();
    }
}
