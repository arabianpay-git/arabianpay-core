<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 0 Emergency Containment Tests
 *
 * Validates that all critical security vulnerabilities identified in the
 * 2026-04-06 audit have been contained.
 */
class Phase0SecurityTest extends TestCase
{
    // ─────────────────────────────────────────────
    // F-001: Kill-switch backdoor removed
    // ─────────────────────────────────────────────

    public function test_killswitch_handler_file_does_not_exist(): void
    {
        $this->assertFileDoesNotExist(
            base_path('bootstrap/cache/vendor/assets/.bin/x9/Handler.php'),
            'Kill-switch Handler.php must be deleted (F-001)'
        );
    }

    public function test_index_php_does_not_load_handler(): void
    {
        $indexContents = file_get_contents(public_path('index.php'));

        $this->assertStringNotContainsString(
            'require_once base_path(\'bootstrap/cache/vendor',
            $indexContents,
            'public/index.php must not require the backdoor handler'
        );

        $this->assertStringNotContainsString(
            'X9\\Handler',
            $indexContents,
            'public/index.php must not reference X9\\Handler class'
        );
    }

    public function test_kill_route_does_not_exist(): void
    {
        $response = $this->get('/kill/some_random_long_secret_key/destroy');
        $response->assertStatus(404);
    }

    public function test_killswitch_lock_file_does_not_exist(): void
    {
        // The lock file is what actually blocks the application.
        // The directory may require sudo to remove (daemon-owned).
        $this->assertFileDoesNotExist(
            storage_path('framework/.sys/.cache/.xcr9z.lock'),
            'Kill-switch lock file must not exist'
        );
    }

    // ─────────────────────────────────────────────
    // F-003: SSRF proxy (API tester) removed
    // ─────────────────────────────────────────────

    public function test_api_tester_controller_deleted(): void
    {
        $this->assertFileDoesNotExist(
            app_path('Http/Controllers/ApiTesterController.php'),
            'ApiTesterController must be deleted (F-003)'
        );
    }

    public function test_api_tester_route_returns_404(): void
    {
        $response = $this->get('/api-tester');
        $response->assertStatus(404);
    }

    public function test_api_tester_send_route_returns_404(): void
    {
        $response = $this->post('/api-tester/send', [
            'endpoint' => 'http://169.254.169.254/latest/meta-data/',
            'method' => 'GET',
        ]);
        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // F-002: Financial API routes require auth in non-local
    // ─────────────────────────────────────────────

    public function test_singleview_api_routes_not_accessible_without_auth(): void
    {
        // In test environment (non-local), these should 404
        $routes = [
            '/api/singleview/token',
            '/api/singleview/consent/create',
            '/api/singleview/kyc/SVMOB1/test123',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $this->assertTrue(
                in_array($response->status(), [401, 404]),
                "Route {$route} should return 401 or 404 without auth, got {$response->status()}"
            );
        }
    }

    public function test_nafith_api_routes_not_accessible_without_auth(): void
    {
        $routes = [
            '/api/nafith/auth-token',
            '/api/nafith/create-sanad',
            '/api/nafith/create-multiple-sanads',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $this->assertTrue(
                in_array($response->status(), [401, 404]),
                "Route {$route} should return 401 or 404 without auth, got {$response->status()}"
            );
        }
    }

    public function test_simah_api_routes_not_accessible_without_auth(): void
    {
        $routes = [
            '/api/test-silver-report',
            '/api/test-consumer-score',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $this->assertTrue(
                in_array($response->status(), [401, 404]),
                "Route {$route} should return 401 or 404 without auth, got {$response->status()}"
            );
        }
    }

    // ─────────────────────────────────────────────
    // F-004: No hardcoded API keys in source
    // ─────────────────────────────────────────────

    public function test_no_hardcoded_wathq_api_key(): void
    {
        $contents = file_get_contents(app_path('Http/Controllers/SupplierController.php'));
        $this->assertStringNotContainsString(
            'nxNtcpyb0cqiLfkj8umAdkhqJGA8x4Az',
            $contents,
            'Wathq API key must not be hardcoded in SupplierController (F-004)'
        );
    }

    public function test_no_hardcoded_sms_credentials(): void
    {
        $files = [
            app_path('Traits/OtpSenderTrait.php'),
            app_path('Traits/SmsSender.php'),
            app_path('Traits/SmsTrait.php'),
            app_path('Traits/SendReminderTrait.php'),
            app_path('Http/Controllers/OtpVerificationController.php'),
        ];

        foreach ($files as $file) {
            if (! file_exists($file)) {
                continue;
            }
            $contents = file_get_contents($file);
            $this->assertStringNotContainsString(
                'EGE4CF3dD_Q6yXGnnMRJ',
                $contents,
                "OurSMS token must not be hardcoded in {$file}"
            );
            $this->assertStringNotContainsString(
                'd99970b46c8430547b33815c20b68d41',
                $contents,
                "Msegat API key must not be hardcoded in {$file}"
            );
        }
    }

    // ─────────────────────────────────────────────
    // F-026: OTP bypass phone numbers removed
    // ─────────────────────────────────────────────

    public function test_no_hardcoded_otp_phone_in_middleware(): void
    {
        $contents = file_get_contents(app_path('Http/Middleware/EnsureOtpVerified.php'));
        $this->assertStringNotContainsString(
            '0545232968',
            $contents,
            'Hardcoded OTP bypass phone must be removed from EnsureOtpVerified (F-026)'
        );
    }

    public function test_no_hardcoded_otp_phone_in_controller(): void
    {
        $contents = file_get_contents(app_path('Http/Controllers/OtpVerificationController.php'));
        $this->assertStringNotContainsString(
            '0506879195',
            $contents,
            'Hardcoded OTP phone must be removed from OtpVerificationController (F-026)'
        );
        // Must not have a PHONE constant anymore
        $this->assertStringNotContainsString(
            "const PHONE",
            $contents,
            'OtpVerificationController must not have a hardcoded PHONE constant'
        );
    }

    // ─────────────────────────────────────────────
    // F-007: Admin routes protected
    // ─────────────────────────────────────────────

    public function test_send_email_requires_auth(): void
    {
        $response = $this->get('/send-email');
        $this->assertTrue(
            in_array($response->status(), [302, 401, 403, 404]),
            "send-email must require auth, got {$response->status()}"
        );
    }

    public function test_send_sms_requires_auth(): void
    {
        $response = $this->get('/send-sms');
        $this->assertTrue(
            in_array($response->status(), [302, 401, 403, 404]),
            "send-sms must require auth, got {$response->status()}"
        );
    }

    public function test_send_fcm_post_requires_auth(): void
    {
        $response = $this->postJson('/send-fcm', []);
        $this->assertTrue(
            in_array($response->status(), [302, 401, 403, 404, 405]),
            "POST /send-fcm must require auth, got {$response->status()}"
        );
    }

    // ─────────────────────────────────────────────
    // F-008: Dev login disabled outside local
    // ─────────────────────────────────────────────

    public function test_dev_login_not_accessible_in_test_env(): void
    {
        // Test environment is not 'local', so dev-login routes should not exist
        $response = $this->get('/dev-login');
        $this->assertTrue(
            in_array($response->status(), [302, 404]),
            "dev-login must return 404 outside local env, got {$response->status()}"
        );
    }

    // ─────────────────────────────────────────────
    // F-023: Debug route removed
    // ─────────────────────────────────────────────

    public function test_chat_debug_route_removed(): void
    {
        $webRoutes = file_get_contents(base_path('routes/web.php'));
        // Ensure no Route::get for the debug endpoint exists (comments are ok)
        $this->assertStringNotContainsString(
            "Route::get('/admin/chat/debug'",
            $webRoutes,
            'Debug route definition must be removed from web.php (F-023)'
        );
    }

    // ─────────────────────────────────────────────
    // F-027/F-028: Config hardening
    // ─────────────────────────────────────────────

    public function test_session_encryption_defaults_to_true(): void
    {
        // config/session.php should default to true when SESSION_ENCRYPT env var is missing
        $configContents = file_get_contents(config_path('session.php'));
        $this->assertStringContainsString(
            "env('SESSION_ENCRYPT', true)",
            $configContents,
            'Session encryption must default to true in config/session.php (F-028)'
        );
    }

    public function test_app_debug_defaults_to_false(): void
    {
        // In test env without explicit APP_DEBUG=true, should be false
        // config/app.php defaults to false
        $configContents = file_get_contents(config_path('app.php'));
        $this->assertStringContainsString(
            "env('APP_DEBUG', false)",
            $configContents,
            'APP_DEBUG must default to false in config/app.php (F-027)'
        );
    }

    // ─────────────────────────────────────────────
    // SMS config moved to services.php
    // ─────────────────────────────────────────────

    public function test_sms_config_in_services(): void
    {
        $this->assertNotNull(
            config('services.oursms'),
            'OurSMS config must exist in config/services.php'
        );
        $this->assertNotNull(
            config('services.msegat'),
            'Msegat config must exist in config/services.php'
        );
        $this->assertNotNull(
            config('services.wathq'),
            'Wathq config must exist in config/services.php'
        );
    }
}
