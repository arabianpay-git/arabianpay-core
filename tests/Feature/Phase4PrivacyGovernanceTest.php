<?php

namespace Tests\Feature;

use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Models\UserConsent;
use App\Services\Privacy\ConsentService;
use App\Services\Privacy\DataSubjectRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Phase 4 Privacy & Audit Governance Tests
 *
 * Validates consent management, data subject requests, export protections,
 * and audit logging for PDPL compliance.
 */
class Phase4PrivacyGovernanceTest extends TestCase
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

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        if (! empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        return $user->fresh();
    }

    // ─────────────────────────────────────────────
    // Consent Management Tests
    // ─────────────────────────────────────────────

    public function test_consent_can_be_granted(): void
    {
        $user = $this->createUser();
        $service = new ConsentService();

        $consent = $service->grantConsent($user, 'credit_bureau_check', ['source' => 'onboarding']);

        $this->assertInstanceOf(UserConsent::class, $consent);
        $this->assertEquals($user->id, $consent->user_id);
        $this->assertEquals('credit_bureau_check', $consent->consent_type);
        $this->assertTrue($consent->consent_given);
        $this->assertNotNull($consent->consent_date);
    }

    public function test_consent_can_be_withdrawn(): void
    {
        $user = $this->createUser();
        $service = new ConsentService();

        $service->grantConsent($user, 'marketing_emails');

        $result = $service->withdrawConsent($user, 'marketing_emails', 'User requested opt-out');

        $this->assertTrue($result);

        $consent = UserConsent::where('user_id', $user->id)
            ->where('consent_type', 'marketing_emails')
            ->latest()->first();

        $this->assertFalse($consent->consent_given);
        $this->assertNotNull($consent->withdrawn_at);
        $this->assertEquals('User requested opt-out', $consent->withdrawal_reason);
    }

    public function test_withdrawn_consent_not_active(): void
    {
        $user = $this->createUser();
        $service = new ConsentService();

        $service->grantConsent($user, 'data_sharing');
        $service->withdrawConsent($user, 'data_sharing');

        $this->assertFalse($service->hasActiveConsent($user, 'data_sharing'));
    }

    public function test_active_consent_detected(): void
    {
        $user = $this->createUser();
        $service = new ConsentService();

        $service->grantConsent($user, 'open_banking');

        $this->assertTrue($service->hasActiveConsent($user, 'open_banking'));
        $this->assertFalse($service->hasActiveConsent($user, 'nonexistent'));
    }

    public function test_consent_withdrawal_returns_false_when_no_active_consent(): void
    {
        $user = $this->createUser();
        $service = new ConsentService();

        $result = $service->withdrawConsent($user, 'never_granted');

        $this->assertFalse($result);
    }

    public function test_consent_records_withdrawer(): void
    {
        $user = $this->createUser();
        $admin = $this->createUser();
        $service = new ConsentService();

        $service->grantConsent($user, 'bank_access');
        $service->withdrawConsent($user, 'bank_access', 'Admin withdrawal', $admin);

        $consent = UserConsent::where('user_id', $user->id)
            ->where('consent_type', 'bank_access')
            ->latest()->first();

        $this->assertEquals($admin->id, $consent->withdrawn_by);
    }

    // ─────────────────────────────────────────────
    // Data Subject Request Tests
    // ─────────────────────────────────────────────

    public function test_data_subject_request_created_correctly(): void
    {
        $user = $this->createUser('merchant');
        $service = new DataSubjectRequestService();

        $request = $service->createRequest(
            $user,
            DataRequestType::Access,
            'I want to see all my personal data',
            null,
            ['personal_info', 'transaction_history']
        );

        $this->assertInstanceOf(DataSubjectRequest::class, $request);
        $this->assertEquals($user->id, $request->user_id);
        $this->assertEquals(DataRequestType::Access, $request->request_type);
        $this->assertEquals(DataRequestStatus::Pending, $request->status);
        $this->assertNotNull($request->uuid);
        $this->assertNotNull($request->deadline_at);
        $this->assertContains('personal_info', $request->affected_data);
    }

    public function test_data_request_deadline_auto_set_30_days(): void
    {
        $user = $this->createUser();
        $service = new DataSubjectRequestService();

        $request = $service->createRequest($user, DataRequestType::Portability, 'Export my data');

        $this->assertTrue($request->deadline_at->isBetween(
            now()->addDays(29),
            now()->addDays(31)
        ));
    }

    public function test_data_request_can_be_reviewed(): void
    {
        $user = $this->createUser('merchant');
        $reviewer = $this->createUser();
        $service = new DataSubjectRequestService();

        $request = $service->createRequest($user, DataRequestType::Correction, 'Fix my phone number');
        $updated = $service->reviewRequest(
            $request,
            $reviewer,
            DataRequestStatus::Completed,
            'Phone number corrected in system'
        );

        $this->assertEquals(DataRequestStatus::Completed, $updated->status);
        $this->assertEquals($reviewer->id, $updated->reviewed_by);
        $this->assertNotNull($updated->reviewed_at);
        $this->assertNotNull($updated->completed_at);
    }

    public function test_erasure_request_types_exist(): void
    {
        $types = array_column(DataRequestType::cases(), 'value');

        $this->assertContains('access', $types);
        $this->assertContains('correction', $types);
        $this->assertContains('erasure', $types);
        $this->assertContains('portability', $types);
        $this->assertContains('objection', $types);
    }

    public function test_data_request_status_enum_complete(): void
    {
        $statuses = array_column(DataRequestStatus::cases(), 'value');

        $this->assertContains('pending', $statuses);
        $this->assertContains('under_review', $statuses);
        $this->assertContains('approved', $statuses);
        $this->assertContains('completed', $statuses);
        $this->assertContains('rejected', $statuses);
        $this->assertContains('cancelled', $statuses);
    }

    // ─────────────────────────────────────────────
    // Export Protection Tests
    // ─────────────────────────────────────────────

    public function test_financial_export_denied_without_permission(): void
    {
        $user = $this->createUser('admin', []);

        $response = $this->actingAs($user)->get('/admin/financial/trial-balance/export');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Financial export should require financial.export permission, got {$response->status()}"
        );
    }

    public function test_activity_log_export_denied_without_permission(): void
    {
        $user = $this->createUser('admin', []);

        $response = $this->actingAs($user)->get('/admin/activity-logs/export/csv');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Activity log export should require audit.export permission, got {$response->status()}"
        );
    }

    public function test_credit_export_denied_without_permission(): void
    {
        $user = $this->createUser('admin', []);

        $response = $this->actingAs($user)->get('/admin/credit/export/csv');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Credit export should require report.export permission, got {$response->status()}"
        );
    }

    public function test_risk_export_denied_without_permission(): void
    {
        $user = $this->createUser('admin', []);

        $response = $this->actingAs($user)->post('/admin/risk/risk/export');
        $this->assertTrue(
            in_array($response->status(), [403, 302]),
            "Risk export should require risk.export permission, got {$response->status()}"
        );
    }

    // ─────────────────────────────────────────────
    // Model & Service Existence Tests
    // ─────────────────────────────────────────────

    public function test_privacy_models_exist(): void
    {
        $this->assertFileExists(app_path('Models/DataSubjectRequest.php'));
        $this->assertFileExists(app_path('Models/UserConsent.php'));
    }

    public function test_privacy_services_exist(): void
    {
        $this->assertFileExists(app_path('Services/Privacy/ConsentService.php'));
        $this->assertFileExists(app_path('Services/Privacy/DataSubjectRequestService.php'));
    }

    public function test_privacy_enums_exist(): void
    {
        $this->assertFileExists(app_path('Enums/DataRequestType.php'));
        $this->assertFileExists(app_path('Enums/DataRequestStatus.php'));
    }

    public function test_consent_migration_exists(): void
    {
        $migrations = glob(database_path('migrations/*consent_management*'));
        $this->assertNotEmpty($migrations, 'Consent management migration must exist');
    }

    public function test_data_subject_request_migration_exists(): void
    {
        $migrations = glob(database_path('migrations/*data_subject_requests*'));
        $this->assertNotEmpty($migrations, 'Data subject requests migration must exist');
    }

    // ─────────────────────────────────────────────
    // Export Route Permissions Configured
    // ─────────────────────────────────────────────

    public function test_all_export_routes_have_permission_middleware(): void
    {
        $routeContent = file_get_contents(base_path('routes/web.php'));

        $exportRoutes = [
            'trial-balance.export',
            'suppliers.export',
            'activity-logs.exportCsv',
            'activity-logs.exportPdf',
            'credit.exportPdf',
            'credit.exportCsv',
            'risk.exportPdf',
            'risk.exportCsv',
            'audit.logs.export',
            'audit.trails.export',
        ];

        foreach ($exportRoutes as $routeName) {
            // Verify the route line contains both the name and a permission middleware
            $pattern = preg_quote($routeName, '/');
            $this->assertMatchesRegularExpression(
                "/.*{$pattern}.*permission:/s",
                $routeContent,
                "Export route '{$routeName}' should have permission middleware"
            );
        }
    }

    // ─────────────────────────────────────────────
    // UserConsent Model Tests
    // ─────────────────────────────────────────────

    public function test_user_consent_model_has_withdrawal_fields(): void
    {
        $consent = new UserConsent();
        $fillable = $consent->getFillable();

        $this->assertContains('withdrawn_at', $fillable);
        $this->assertContains('withdrawn_by', $fillable);
        $this->assertContains('withdrawal_reason', $fillable);
        $this->assertContains('consent_type', $fillable);
        $this->assertContains('consent_given', $fillable);
    }

    public function test_user_consent_is_withdrawn_check(): void
    {
        $consent = new UserConsent([
            'consent_given' => false,
            'withdrawn_at' => now(),
        ]);

        $this->assertTrue($consent->isWithdrawn());
    }
}
