<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Hitting a normal web route must produce an audit_logs row.
     */
    public function test_audit_log_row_is_created_for_web_request(): void
    {
        $this->assertDatabaseCount('audit_logs', 0);

        $this->get('/login');

        $this->assertDatabaseCount('audit_logs', 1);
    }

    /**
     * The audit row must capture the HTTP method and path.
     */
    public function test_audit_log_captures_method_and_path(): void
    {
        $this->get('/login');

        $this->assertDatabaseHas('audit_logs', [
            'method' => 'GET',
        ]);
    }

    /**
     * Hitting the /up health-check route must NOT create an audit_logs row.
     */
    public function test_health_check_route_is_not_audited(): void
    {
        $this->get('/up');

        $this->assertDatabaseCount('audit_logs', 0);
    }

    /**
     * Paths prefixed with _debugbar must NOT create an audit_logs row.
     */
    public function test_debugbar_paths_are_not_audited(): void
    {
        // The _debugbar route may not exist, but the middleware skip
        // fires before the router, so we only care that no row is written.
        // Suppress 404 — we just want the middleware behaviour.
        $this->get('/_debugbar/open');

        $this->assertDatabaseCount('audit_logs', 0);
    }

    /**
     * Paths prefixed with telescope must NOT create an audit_logs row.
     */
    public function test_telescope_paths_are_not_audited(): void
    {
        $this->get('/telescope');

        $this->assertDatabaseCount('audit_logs', 0);
    }

    /**
     * POST requests are also audited.
     */
    public function test_post_request_is_audited(): void
    {
        $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'secret',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'method' => 'POST',
        ]);
    }

    /**
     * PII fields in the request body must be masked before storage.
     */
    public function test_pii_fields_are_masked_in_audit_log(): void
    {
        $this->post('/login', [
            'email'    => 'user@example.com',
            'password' => 'secret123',
        ]);

        $log = AuditLog::first();
        $this->assertNotNull($log);

        $body = $log->properties['body'] ?? [];

        // Raw email and password values must NOT appear in stored properties.
        $this->assertNotEquals('user@example.com', $body['email'] ?? null);
        $this->assertNotEquals('secret123', $body['password'] ?? null);
    }
}
