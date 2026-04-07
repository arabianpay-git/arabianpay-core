<?php

namespace Tests\Feature;

use App\Models\Settlement;
use App\Models\User;
use App\Policies\SettlementPolicy;
use App\Policies\OrderPolicy;
use App\Policies\CustomerCreditLimitPolicy;
use App\Policies\SensitiveDataApprovalPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Phase 1 RBAC & Access Control Tests
 *
 * Validates that Spatie Permission middleware, policies, maker-checker,
 * and FormRequest authorization are properly enforced.
 */
class Phase1RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Helper to create a user with given type and optional permissions.
     *
     * Uses forceCreate to bypass the EncryptsAttributes trait's __construct
     * issue which drops factory attributes during model instantiation.
     */
    private function makeUser(string $userType = 'admin', array $permissions = []): User
    {
        $user = User::forceCreate([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'business_name' => fake()->unique()->company(),
            'phone_number' => fake()->unique()->numerify('05########'),
            'user_type' => $userType,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        if (! empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        return $user->fresh();
    }

    // ─────────────────────────────────────────────
    // Policy unit tests
    // ─────────────────────────────────────────────

    public function test_settlement_policy_denies_without_permission(): void
    {
        $user = $this->makeUser('employee', []);
        $policy = new SettlementPolicy();

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->create($user));
    }

    public function test_settlement_policy_allows_with_permission(): void
    {
        $user = $this->makeUser('admin', ['settlement.view', 'settlement.create']);
        $policy = new SettlementPolicy();

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->create($user));
    }

    public function test_settlement_maker_checker_denies_approver_is_creator(): void
    {
        $user = $this->makeUser('admin', ['settlement.approve']);
        $settlement = new Settlement([
            'created_by' => $user->id,
            'status' => 'draft',
        ]);
        $policy = new SettlementPolicy();

        $response = $policy->approve($user, $settlement);
        $this->assertTrue($response->denied());
        $this->assertStringContainsString('Maker-checker', $response->message());
    }

    public function test_settlement_maker_checker_allows_different_approver(): void
    {
        $creator = $this->makeUser('admin', []);
        $approver = $this->makeUser('admin', ['settlement.approve']);
        $settlement = new Settlement([
            'created_by' => $creator->id,
            'status' => 'draft',
        ]);
        $policy = new SettlementPolicy();

        $response = $policy->approve($approver, $settlement);
        $this->assertTrue($response->allowed());
    }

    public function test_settlement_maker_checker_denies_payer_is_approver(): void
    {
        $user = $this->makeUser('admin', ['settlement.pay']);
        $settlement = new Settlement([
            'approved_by' => $user->id,
            'status' => 'approved',
        ]);
        $policy = new SettlementPolicy();

        $response = $policy->pay($user, $settlement);
        $this->assertTrue($response->denied());
    }

    public function test_settlement_maker_checker_allows_different_payer(): void
    {
        $approver = $this->makeUser('admin', []);
        $payer = $this->makeUser('admin', ['settlement.pay']);
        $settlement = new Settlement([
            'approved_by' => $approver->id,
            'status' => 'approved',
        ]);
        $policy = new SettlementPolicy();

        $response = $policy->pay($payer, $settlement);
        $this->assertTrue($response->allowed());
    }

    public function test_order_policy_merchant_can_only_see_own_orders(): void
    {
        $merchant = $this->makeUser('merchant', ['order.view']);
        $policy = new OrderPolicy();

        $ownOrder = new \App\Models\Order();
        $ownOrder->seller_id = $merchant->id;

        $otherOrder = new \App\Models\Order();
        $otherOrder->seller_id = $merchant->id + 999;

        $this->assertTrue($policy->view($merchant, $ownOrder));
        $this->assertFalse($policy->view($merchant, $otherOrder));
    }

    public function test_order_policy_admin_can_see_any_order(): void
    {
        $admin = $this->makeUser('admin', ['order.view']);
        $policy = new OrderPolicy();

        $anyOrder = new \App\Models\Order();
        $anyOrder->seller_id = 99999;
        $this->assertTrue($policy->view($admin, $anyOrder));
    }

    public function test_credit_limit_policy_denies_without_permission(): void
    {
        $user = $this->makeUser('employee', []);
        $policy = new CustomerCreditLimitPolicy();

        $this->assertFalse($policy->create($user));
    }

    public function test_credit_limit_policy_allows_with_permission(): void
    {
        $user = $this->makeUser('admin', ['credit-limit.create']);
        $policy = new CustomerCreditLimitPolicy();

        $this->assertTrue($policy->create($user));
    }

    public function test_sensitive_data_approval_maker_checker(): void
    {
        $requester = $this->makeUser('admin', ['sensitive-data.access', 'sensitive-data.approve']);
        $approval = new \App\Models\SensitiveDataApproval([
            'requested_by' => $requester->id,
        ]);
        $policy = new SensitiveDataApprovalPolicy();

        // Cannot approve own request
        $response = $policy->approve($requester, $approval);
        $this->assertTrue($response->denied());
    }

    // ─────────────────────────────────────────────
    // Permission middleware tests
    // ─────────────────────────────────────────────

    public function test_settlement_routes_denied_without_permission(): void
    {
        $user = $this->makeUser('admin', []); // admin with no permissions

        $response = $this->actingAs($user)->get('/admin/settlements');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Settlement index should be denied without settlement.view permission, got {$response->status()}"
        );
    }

    public function test_settlement_routes_allowed_with_permission(): void
    {
        $user = $this->makeUser('admin', ['settlement.view']);

        $response = $this->actingAs($user)->get('/admin/settlements');
        // Should not get 403 (may get 200 or 500 due to missing DB data, but not 403)
        $this->assertNotEquals(403, $response->status());
    }

    public function test_financial_routes_denied_without_permission(): void
    {
        $user = $this->makeUser('admin', []);

        $response = $this->actingAs($user)->get('/admin/financial');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Financial dashboard should be denied without financial.view permission"
        );
    }

    public function test_risk_routes_denied_without_permission(): void
    {
        $user = $this->makeUser('admin', []);

        $response = $this->actingAs($user)->get('/admin/risk/dashboard');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Risk dashboard should be denied without risk.view permission"
        );
    }

    public function test_reports_denied_without_permission(): void
    {
        $user = $this->makeUser('admin', []);

        $response = $this->actingAs($user)->get('/admin/reports/portfolio-performance');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Reports should be denied without report.view permission"
        );
    }

    public function test_audit_denied_without_permission(): void
    {
        $user = $this->makeUser('admin', []);

        $response = $this->actingAs($user)->get('/admin/audit/trails');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Audit trails should be denied without audit.view permission"
        );
    }

    public function test_credit_routes_denied_without_permission(): void
    {
        $user = $this->makeUser('admin', []);

        $response = $this->actingAs($user)->get('/admin/credit/credit-profiles');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Credit profiles should be denied without credit-limit.view permission"
        );
    }

    public function test_collections_denied_without_permission(): void
    {
        $user = $this->makeUser('admin', []);

        $response = $this->actingAs($user)->get('/admin/collections');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Collections dashboard should be denied without collection.view permission"
        );
    }

    public function test_settings_denied_without_permission(): void
    {
        $user = $this->makeUser('admin', []);

        $response = $this->actingAs($user)->get('/admin/settings');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Settings should be denied without settings.manage permission"
        );
    }

    // ─────────────────────────────────────────────
    // FormRequest authorization tests
    // ─────────────────────────────────────────────

    public function test_product_form_request_checks_permission(): void
    {
        $request = new \App\Http\Requests\StoreProductRequest();

        // Without user
        $this->assertFalse($request->authorize());
    }

    // ─────────────────────────────────────────────
    // Permission matrix integrity
    // ─────────────────────────────────────────────

    public function test_permission_seeder_creates_all_required_permissions(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $requiredPermissions = [
            'settlement.view', 'settlement.create', 'settlement.approve', 'settlement.pay', 'settlement.cancel', 'settlement.export',
            'payout.view', 'payout.process', 'payout.complete',
            'payment.view', 'payment.process',
            'schedule_payment.view', 'schedule_payment.update', 'schedule_payment.pay-now',
            'credit-limit.view', 'credit-limit.create', 'credit-limit.update',
            'refund.view', 'refund.manage', 'refund.approve',
            'merchant.view', 'merchant.update',
            'sensitive-data.access', 'sensitive-data.approve',
            'risk.view', 'risk.manage', 'risk.export', 'risk.update-score',
            'risk-weight.view', 'risk-weight.manage',
            'compliance.view', 'compliance.manage',
            'collection.view', 'collection.manage',
            'financial.view', 'financial.manage', 'financial.export',
            'report.view', 'report.export',
            'audit.view', 'audit.export',
            'settings.manage',
        ];

        foreach ($requiredPermissions as $perm) {
            $this->assertNotNull(
                Permission::findByName($perm),
                "Permission '{$perm}' must exist after seeding"
            );
        }
    }

    public function test_permission_seeder_creates_all_roles(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $requiredRoles = ['admin', 'finance', 'risk', 'compliance', 'collections', 'support'];

        foreach ($requiredRoles as $roleName) {
            $this->assertNotNull(
                Role::findByName($roleName),
                "Role '{$roleName}' must exist after seeding"
            );
        }
    }

    public function test_admin_role_has_all_permissions(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $adminRole = Role::findByName('admin');
        $allPermissions = Permission::all();

        $this->assertEquals(
            $allPermissions->count(),
            $adminRole->permissions->count(),
            'Admin role must have all permissions'
        );
    }

    public function test_finance_role_has_settlement_permissions(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $financeRole = Role::findByName('finance');
        $this->assertTrue($financeRole->hasPermissionTo('settlement.view'));
        $this->assertTrue($financeRole->hasPermissionTo('settlement.pay'));
        $this->assertTrue($financeRole->hasPermissionTo('payout.process'));
    }

    public function test_support_role_lacks_financial_permissions(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $supportRole = Role::findByName('support');
        $this->assertFalse($supportRole->hasPermissionTo('settlement.view'));
        $this->assertFalse($supportRole->hasPermissionTo('settlement.pay'));
        $this->assertFalse($supportRole->hasPermissionTo('credit-limit.create'));
    }

    // ─────────────────────────────────────────────
    // Policies exist for all critical models
    // ─────────────────────────────────────────────

    public function test_all_financial_policies_exist(): void
    {
        $policyFiles = [
            'OrderPolicy.php',
            'SettlementPolicy.php',
            'PaymentPolicy.php',
            'SchedulePaymentPolicy.php',
            'CustomerCreditLimitPolicy.php',
            'RefundRequestPolicy.php',
            'MerchantPolicy.php',
            'CustomerPolicy.php',
            'SensitiveDataApprovalPolicy.php',
        ];

        foreach ($policyFiles as $file) {
            $this->assertFileExists(
                app_path("Policies/{$file}"),
                "Policy file {$file} must exist"
            );
        }
    }

    public function test_form_requests_exist(): void
    {
        $requestFiles = [
            'ApproveSettlementRequest.php',
            'PaySettlementRequest.php',
            'CancelSettlementRequest.php',
            'BatchSettlementRequest.php',
            'CreateCreditLimitRequest.php',
            'UpdateCreditLimitRequest.php',
        ];

        foreach ($requestFiles as $file) {
            $this->assertFileExists(
                app_path("Http/Requests/{$file}"),
                "FormRequest {$file} must exist"
            );
        }
    }
}
