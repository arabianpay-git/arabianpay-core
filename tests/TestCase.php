<?php

namespace Tests;

use App\Models\Settlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Jetstream/Fortify views reference Vite assets. Avoid manifest errors.
        $this->withoutVite();

        // Many models define $fillable but leave the default $guarded = ['*'].
        // Unguard during tests so factories can mass-assign attributes.
        Model::unguard();

        // Admin routes require 2FA by default. Disable that in tests so we can
        // focus on permission-gate behaviour.
        config(['fortify.mandatory_two_factor' => false]);
    }

    protected function tearDown(): void
    {
        Model::reguard();

        parent::tearDown();
    }

    /**
     * Authenticate as an admin user with the given permissions.
     */
    protected function actingAsAdmin(array $permissions = []): static
    {
        $user = User::factory()->create(['user_type' => 'admin']);

        foreach ($permissions as $permission) {
            $this->ensurePermission($user, $permission);
        }

        return $this->actingAs($user);
    }

    /**
     * Authenticate as an admin user with a single permission.
     */
    protected function actingAsWithPermission(string $permission): static
    {
        return $this->actingAsAdmin([$permission]);
    }

    /**
     * Ensure a permission exists and assign it to the user.
     */
    protected function ensurePermission(User $user, string $permission): void
    {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    /**
     * Activate the financial-mutation approval context.
     *
     * Models that use WithApprovalContext will otherwise log critical warnings
     * and attempt audit-trail writes when mutated outside this context.
     */
    protected function enterApprovalContext(): void
    {
        Settlement::enterApprovalContext();
    }

    /**
     * Deactivate the financial-mutation approval context.
     */
    protected function exitApprovalContext(): void
    {
        Settlement::exitApprovalContext();
    }

    /**
     * Run a callback inside the approval context.
     */
    protected function runInApprovalContext(callable $callback): mixed
    {
        return Settlement::runInApprovalContext($callback);
    }
}
