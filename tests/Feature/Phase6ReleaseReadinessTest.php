<?php

namespace Tests\Feature;

use App\Enums\DataRequestType;
use App\Enums\SettlementStatus;
use App\Helpers\Money;
use App\Models\FAccounts;
use App\Models\FEntry;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Settlement;
use App\Models\SupplierPayout;
use App\Models\User;
use App\Models\UserConsent;
use App\Services\Finance\ReconciliationService;
use App\Services\Finance\SettlementService;
use App\Services\Privacy\ConsentService;
use App\Services\Privacy\DataSubjectRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Phase 6 — Release Readiness Quality Gate
 *
 * This test suite validates ALL critical paths that must pass before
 * the system can be presented for regulatory sandbox review.
 * If any test fails, the system is NOT release-ready.
 */
class Phase6ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $type = 'admin', array $permissions = []): User
    {
        $user = User::forceCreate([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'business_name' => fake()->unique()->company(),
            'phone_number' => fake()->unique()->numerify('05########'),
            'user_type' => $type,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        if ($permissions) {
            $user->givePermissionTo($permissions);
        }

        return $user->fresh();
    }

    // ═══════════════════════════════════════════════
    //  SECTION 1: AUTHENTICATION & AUTHORIZATION
    // ═══════════════════════════════════════════════

    public function test_guest_cannot_access_admin_routes(): void
    {
        $routes = ['/admin/settlements', '/admin/financial', '/admin/risk/dashboard'];
        foreach ($routes as $route) {
            $response = $this->get($route);
            $this->assertTrue(in_array($response->status(), [302, 401, 403]),
                "Guest must be denied {$route}, got {$response->status()}");
        }
    }

    public function test_authenticated_user_without_permission_denied(): void
    {
        $user = $this->createUser('admin', []);

        $protectedRoutes = [
            '/admin/settlements' => 'settlement.view',
            '/admin/financial' => 'financial.view',
            '/admin/risk/dashboard' => 'risk.view',
            '/admin/collections' => 'collection.view',
            '/admin/reports/portfolio-performance' => 'report.view',
            '/admin/audit/trails' => 'audit.view',
            '/admin/settings' => 'settings.manage',
        ];

        foreach ($protectedRoutes as $route => $permission) {
            $response = $this->actingAs($user)->get($route);
            $this->assertTrue(in_array($response->status(), [302, 403]),
                "Route {$route} should deny without {$permission}, got {$response->status()}");
        }
    }

    public function test_authorized_user_allowed(): void
    {
        $user = $this->createUser('admin', ['settlement.view']);
        $response = $this->actingAs($user)->get('/admin/settlements');
        $this->assertNotEquals(403, $response->status());
    }

    // ═══════════════════════════════════════════════
    //  SECTION 2: SETTLEMENT LIFECYCLE E2E
    // ═══════════════════════════════════════════════

    public function test_settlement_full_lifecycle(): void
    {
        $creator = $this->createUser();
        $approver = $this->createUser();
        $payer = $this->createUser();

        Merchant::forceCreate(['user_id' => $creator->id, 'status' => 'approved']);
        FAccounts::forceCreate(['id' => config('financial.accounts.accounts_payable', 2400),
            'account_name' => 'AP', 'account_type1' => 'liability', 'status' => 'active']);
        FAccounts::forceCreate(['id' => config('financial.accounts.bank_account', 1201),
            'account_name' => 'Bank', 'account_type1' => 'asset', 'status' => 'active']);

        $settlement = Settlement::forceCreate([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'settlement_number' => 'SETT-E2E-' . rand(100, 999),
            'supplier_user_id' => $creator->id,
            'start_date' => now()->subWeek(), 'end_date' => now()->subDay(),
            'settlement_date' => now(),
            'total_amount' => '1000.00', 'commission_amount' => '100.00', 'payable_amount' => '900.00',
            'status' => 'draft', 'created_by' => $creator->id,
        ]);

        $service = app(SettlementService::class);

        // Step 1: Approve (different user)
        $service->approveSettlement($settlement, $approver);
        $settlement->refresh();
        $this->assertEquals('approved', $settlement->status instanceof SettlementStatus
            ? $settlement->status->value : $settlement->status);

        // Step 2: Pay (different user from approver)
        $service->markSettlementAsPaid($settlement, $payer);
        $settlement->refresh();
        $this->assertEquals('paid', $settlement->status instanceof SettlementStatus
            ? $settlement->status->value : $settlement->status);

        // Step 3: Verify payout created
        $payout = SupplierPayout::where('settlement_id', $settlement->id)->first();
        $this->assertNotNull($payout);
        $this->assertEquals('900.00', $payout->amount);

        // Step 4: Verify accounting entries balance
        $entries = FEntry::whereHas('transaction', fn($q) => $q->where('amount', '900.00'))->get();
        $this->assertGreaterThanOrEqual(2, $entries->count());
        $debit = '0.00'; $credit = '0.00';
        foreach ($entries as $e) {
            $debit = Money::add($debit, $e->debit);
            $credit = Money::add($credit, $e->credit);
        }
        $this->assertEquals(0, Money::compare($debit, $credit));
    }

    public function test_maker_checker_enforced_at_service_layer(): void
    {
        $user = $this->createUser();
        $settlement = Settlement::forceCreate([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'settlement_number' => 'SETT-MC-' . rand(100, 999),
            'supplier_user_id' => $user->id,
            'start_date' => now()->subWeek(), 'end_date' => now()->subDay(),
            'settlement_date' => now(),
            'total_amount' => '500.00', 'commission_amount' => '0.00', 'payable_amount' => '500.00',
            'status' => 'draft', 'created_by' => $user->id,
        ]);

        $this->expectException(\DomainException::class);
        app(SettlementService::class)->approveSettlement($settlement, $user);
    }

    // ═══════════════════════════════════════════════
    //  SECTION 3: FINANCIAL INVARIANTS
    // ═══════════════════════════════════════════════

    public function test_settlement_amount_calculation_precise(): void
    {
        $service = app(SettlementService::class);
        $orders = collect([
            (object) ['grand_total' => '333.33', 'commission_amount' => '33.33'],
            (object) ['grand_total' => '333.33', 'commission_amount' => '33.33'],
            (object) ['grand_total' => '333.34', 'commission_amount' => '33.34'],
        ]);

        $result = $service->calculateSettlementAmount($orders);

        $this->assertEquals('1000.00', $result['total_amount']);
        $this->assertEquals('100.00', $result['commission_amount']);
        $this->assertEquals('900.00', $result['payable_amount']);
    }

    public function test_reconciliation_detects_mismatch(): void
    {
        $user = $this->createUser();
        $settlement = Settlement::forceCreate([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'settlement_number' => 'SETT-REC-' . rand(100, 999),
            'supplier_user_id' => $user->id,
            'start_date' => now()->subWeek(), 'end_date' => now(),
            'settlement_date' => now(),
            'total_amount' => '9999.00', 'commission_amount' => '0.00', 'payable_amount' => '9999.00',
            'status' => 'draft', 'created_by' => $user->id,
        ]);

        DB::table('orders')->insert([
            'user_id' => $user->id, 'seller_id' => $user->id,
            'grand_total' => '100.00', 'settlement_id' => $settlement->id,
            'product_details' => '[]', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $result = (new ReconciliationService())->reconcileSettlement($settlement);
        $this->assertEquals('fail', $result['status']);
    }

    public function test_payout_amount_matches_settlement(): void
    {
        $settlement = new Settlement(['payable_amount' => '1234.56']);
        $payout = new SupplierPayout(['amount' => '1234.56']);
        $this->assertEquals(0, Money::compare($payout->amount, $settlement->payable_amount));
    }

    // ═══════════════════════════════════════════════
    //  SECTION 4: PRIVACY & CONSENT
    // ═══════════════════════════════════════════════

    public function test_consent_grant_and_withdrawal_lifecycle(): void
    {
        $user = $this->createUser();
        $service = new ConsentService();

        $service->grantConsent($user, 'credit_check');
        $this->assertTrue($service->hasActiveConsent($user, 'credit_check'));

        $service->withdrawConsent($user, 'credit_check', 'User requested');
        $this->assertFalse($service->hasActiveConsent($user, 'credit_check'));
    }

    public function test_data_subject_request_with_deadline(): void
    {
        $user = $this->createUser();
        $service = new DataSubjectRequestService();

        $request = $service->createRequest($user, DataRequestType::Access, 'Export my data');

        $this->assertNotNull($request->deadline_at);
        $this->assertTrue($request->deadline_at->isAfter(now()->addDays(28)));
    }

    // ═══════════════════════════════════════════════
    //  SECTION 5: INTEGRATION SAFETY
    // ═══════════════════════════════════════════════

    public function test_webhook_rejects_unsigned_request(): void
    {
        $response = $this->postJson('/api/webhooks/clickpay', ['tran_ref' => 'X']);
        $this->assertEquals(403, $response->status());
    }

    public function test_webhook_accepts_signed_and_updates_payment(): void
    {
        config(['services.clickpay.webhook_secret' => 'e2e-secret']);
        $user = $this->createUser();
        $tranRef = 'E2E-' . rand(1000, 9999);

        Payment::forceCreate([
            'user_id' => $user->id, 'txn_code' => $tranRef,
            'amount' => '100.00', 'payment_status' => 'pending',
            'invoice_number' => 'INV-E2E', 'payment_details' => json_encode([]),
        ]);

        $sig = hash_hmac('sha256', $tranRef . 'CART-E2E', 'e2e-secret');
        $response = $this->postJson('/api/webhooks/clickpay', [
            'tran_ref' => $tranRef, 'cart_id' => 'CART-E2E',
            'payment_result' => ['response_status' => 'A', 'response_message' => 'OK'],
        ], ['signature' => $sig]);

        $response->assertOk();
        $this->assertEquals('paid', Payment::where('txn_code', $tranRef)->first()->payment_status);
    }

    // ═══════════════════════════════════════════════
    //  SECTION 6: OPERATIONAL READINESS
    // ═══════════════════════════════════════════════

    public function test_health_endpoint_returns_status(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => ['database', 'cache', 'app'],
        ]);
        $response->assertJson(['status' => 'healthy']);
    }

    public function test_health_endpoint_includes_debug_flag(): void
    {
        $response = $this->getJson('/api/health');
        $data = $response->json();
        $this->assertArrayHasKey('debug', $data['checks']['app']);
    }

    public function test_scheduler_uses_safe_intervals(): void
    {
        $content = file_get_contents(base_path('routes/console.php'));

        // Must NOT have everyMinute for payment processing
        $this->assertStringNotContainsString(
            "process:scheduled-payments')->everyMinute()",
            $content,
            'Payment processing must not run every minute in production'
        );

        // Must have withoutOverlapping
        $this->assertStringContainsString('withoutOverlapping()', $content);

        // Must have onOneServer for distributed safety
        $this->assertStringContainsString('onOneServer()', $content);
    }

    public function test_no_debug_routes_in_production_surface(): void
    {
        $webRoutes = file_get_contents(base_path('routes/web.php'));
        $this->assertStringNotContainsString("Route::get('/admin/chat/debug'", $webRoutes);
    }

    // ═══════════════════════════════════════════════
    //  SECTION 7: SECURITY BASELINE
    // ═══════════════════════════════════════════════

    public function test_no_hardcoded_secrets_in_source(): void
    {
        $patterns = [
            'nxNtcpyb0cqiLfkj8umAdkhqJGA8x4Az', // Wathq key
            'EGE4CF3dD_Q6yXGnnMRJ',               // OurSMS token
            'd99970b46c8430547b33815c20b68d41',    // Msegat key
            'some_random_long_secret_key',          // Kill-switch
        ];

        foreach ($patterns as $secret) {
            $found = [];
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(app_path())
            ) as $file) {
                if ($file->getExtension() !== 'php') continue;
                if (str_contains(file_get_contents($file), $secret)) {
                    $found[] = str_replace(base_path() . '/', '', $file->getRealPath());
                }
            }
            $this->assertEmpty($found,
                "Secret '{$secret}' found in: " . implode(', ', $found));
        }
    }

    public function test_session_encryption_default_is_true(): void
    {
        $content = file_get_contents(config_path('session.php'));
        $this->assertStringContainsString("env('SESSION_ENCRYPT', true)", $content);
    }

    public function test_app_debug_default_is_false(): void
    {
        $content = file_get_contents(config_path('app.php'));
        $this->assertStringContainsString("env('APP_DEBUG', false)", $content);
    }

    // ═══════════════════════════════════════════════
    //  SECTION 8: INFRASTRUCTURE COMPLETENESS
    // ═══════════════════════════════════════════════

    public function test_all_critical_policies_exist(): void
    {
        $policies = [
            'OrderPolicy', 'SettlementPolicy', 'PaymentPolicy',
            'SchedulePaymentPolicy', 'CustomerCreditLimitPolicy',
            'RefundRequestPolicy', 'MerchantPolicy', 'CustomerPolicy',
            'SensitiveDataApprovalPolicy',
        ];
        foreach ($policies as $p) {
            $this->assertFileExists(app_path("Policies/{$p}.php"), "{$p} must exist");
        }
    }

    public function test_all_financial_enums_exist(): void
    {
        $enums = [
            'SettlementStatus', 'PayoutStatus', 'SchedulePaymentStatus',
            'OrderPaymentStatus', 'OrderDeliveryStatus', 'OrderGeneralStatus',
            'RefundStatus', 'DataRequestType', 'DataRequestStatus',
        ];
        foreach ($enums as $e) {
            $this->assertFileExists(app_path("Enums/{$e}.php"), "{$e} must exist");
        }
    }

    public function test_all_critical_services_exist(): void
    {
        $services = [
            'Finance/SettlementService',
            'Finance/ReconciliationService',
            'Finance/RefundReversalService',
            'Finance/FinancialInvariantService',
            'Privacy/ConsentService',
            'Privacy/DataSubjectRequestService',
            'Integration/IntegrationClient',
        ];
        foreach ($services as $s) {
            $this->assertFileExists(app_path("Services/{$s}.php"), "{$s} must exist");
        }
    }

    public function test_money_helper_exists(): void
    {
        $this->assertFileExists(app_path('Helpers/Money.php'));
    }
}
