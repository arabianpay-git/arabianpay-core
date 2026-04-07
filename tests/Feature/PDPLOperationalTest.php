<?php

namespace Tests\Feature;

use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use App\Models\DataSubjectRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * PDPL operational layer tests — controller, routes, views.
 */
class PDPLOperationalTest extends TestCase
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
        if ($permissions) $user->givePermissionTo($permissions);
        return $user->fresh();
    }

    private function createDSR(User $user, string $status = 'pending', ?int $daysAgo = null): DataSubjectRequest
    {
        return DataSubjectRequest::forceCreate([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'requested_by' => $user->id,
            'request_type' => DataRequestType::Access->value,
            'status' => $status,
            'description' => 'Test request',
            'deadline_at' => $daysAgo ? now()->subDays($daysAgo) : now()->addDays(30),
        ]);
    }

    // ─────────────────────────────────────────
    // Access Control
    // ─────────────────────────────────────────

    public function test_unauthorized_user_cannot_access_pdpl_index(): void
    {
        $user = $this->createUser('admin', []);
        $response = $this->actingAs($user)->get('/admin/pdpl/requests');
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    public function test_authorized_user_can_access_pdpl_index(): void
    {
        $user = $this->createUser('admin', ['sensitive-data.access']);
        $response = $this->actingAs($user)->get('/admin/pdpl/requests');
        $this->assertEquals(200, $response->status());
    }

    public function test_unauthorized_user_cannot_approve(): void
    {
        $user = $this->createUser('admin', ['sensitive-data.access']);
        $subject = $this->createUser('merchant');
        $dsr = $this->createDSR($subject);

        $response = $this->actingAs($user)->post("/admin/pdpl/requests/{$dsr->id}/approve");
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    // ─────────────────────────────────────────
    // Request Workflow
    // ─────────────────────────────────────────

    public function test_can_view_request_detail(): void
    {
        $user = $this->createUser('admin', ['sensitive-data.access']);
        $subject = $this->createUser('merchant');
        $dsr = $this->createDSR($subject);

        $response = $this->actingAs($user)->get("/admin/pdpl/requests/{$dsr->id}");
        $response->assertStatus(200);
        $response->assertSee($dsr->uuid);
    }

    public function test_approve_request_changes_status(): void
    {
        $user = $this->createUser('admin', ['sensitive-data.access', 'sensitive-data.approve']);
        $subject = $this->createUser('merchant');
        $dsr = $this->createDSR($subject);

        $response = $this->actingAs($user)->post("/admin/pdpl/requests/{$dsr->id}/approve", [
            'admin_notes' => 'Verified identity',
        ]);
        $response->assertRedirect();

        $dsr->refresh();
        $statusVal = $dsr->status instanceof DataRequestStatus ? $dsr->status->value : $dsr->status;
        $this->assertEquals('approved', $statusVal);
    }

    public function test_reject_request_changes_status(): void
    {
        $user = $this->createUser('admin', ['sensitive-data.access', 'sensitive-data.approve']);
        $subject = $this->createUser('merchant');
        $dsr = $this->createDSR($subject);

        $response = $this->actingAs($user)->post("/admin/pdpl/requests/{$dsr->id}/reject", [
            'admin_notes' => 'Insufficient information provided',
        ]);
        $response->assertRedirect();

        $dsr->refresh();
        $statusVal = $dsr->status instanceof DataRequestStatus ? $dsr->status->value : $dsr->status;
        $this->assertEquals('rejected', $statusVal);
        $this->assertNotNull($dsr->completed_at);
    }

    public function test_complete_request_changes_status(): void
    {
        $user = $this->createUser('admin', ['sensitive-data.access', 'sensitive-data.approve']);
        $subject = $this->createUser('merchant');
        $dsr = $this->createDSR($subject, 'approved');

        $response = $this->actingAs($user)->post("/admin/pdpl/requests/{$dsr->id}/complete", [
            'admin_notes' => 'Data exported and sent to user',
        ]);
        $response->assertRedirect();

        $dsr->refresh();
        $statusVal = $dsr->status instanceof DataRequestStatus ? $dsr->status->value : $dsr->status;
        $this->assertEquals('completed', $statusVal);
        $this->assertNotNull($dsr->completed_at);
    }

    public function test_create_request_via_admin(): void
    {
        $admin = $this->createUser('admin', ['sensitive-data.access', 'sensitive-data.approve']);
        $subject = $this->createUser('merchant');

        $response = $this->actingAs($admin)->post('/admin/pdpl/requests', [
            'user_id' => $subject->id,
            'request_type' => 'access',
            'description' => 'User called and requested data export',
        ]);
        $response->assertRedirect();

        $dsr = DataSubjectRequest::where('user_id', $subject->id)->first();
        $this->assertNotNull($dsr);
        $this->assertEquals('access', $dsr->request_type instanceof DataRequestType ? $dsr->request_type->value : $dsr->request_type);
        $this->assertEquals($admin->id, $dsr->requested_by);
    }

    // ─────────────────────────────────────────
    // Overdue Detection
    // ─────────────────────────────────────────

    public function test_overdue_request_shown_in_index(): void
    {
        $user = $this->createUser('admin', ['sensitive-data.access']);
        $subject = $this->createUser('merchant');
        $this->createDSR($subject, 'pending', 35); // 35 days ago = overdue

        $response = $this->actingAs($user)->get('/admin/pdpl/requests');
        $response->assertStatus(200);
        $response->assertSee('overdue');
    }

    public function test_overdue_filter_works(): void
    {
        $user = $this->createUser('admin', ['sensitive-data.access']);
        $subject = $this->createUser('merchant');
        $this->createDSR($subject, 'pending', 35);
        $this->createDSR($subject, 'pending', 0); // not overdue — deadline in 30 days

        $response = $this->actingAs($user)->get('/admin/pdpl/requests?overdue=1');
        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────
    // Route & File Existence
    // ─────────────────────────────────────────

    public function test_pdpl_controller_exists(): void
    {
        $this->assertFileExists(app_path('Http/Controllers/Admin/PDPLController.php'));
    }

    public function test_pdpl_views_exist(): void
    {
        $this->assertFileExists(resource_path('views/admin/pdpl/index.blade.php'));
        $this->assertFileExists(resource_path('views/admin/pdpl/show.blade.php'));
    }
}
