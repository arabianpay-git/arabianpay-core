<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Services\Integration\IntegrationClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 5 Integration & Environment Hardening Tests
 *
 * Validates: env() removal, webhook signature verification,
 * integration resilience, and production safety.
 */
class Phase5IntegrationHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────
    // env() usage elimination
    // ─────────────────────────────────────────────

    public function test_no_env_calls_in_controllers(): void
    {
        $controllerDir = app_path('Http/Controllers');
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($controllerDir)
        );

        $violations = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;
            $content = file_get_contents($file->getRealPath());
            // Match env( but exclude comments
            if (preg_match_all('/^\s*[^\/\*].*\benv\s*\(/m', $content, $matches)) {
                $relativePath = str_replace(base_path() . '/', '', $file->getRealPath());
                $violations[] = $relativePath;
            }
        }

        $this->assertEmpty(
            $violations,
            "Controllers should not use env() directly. Found in:\n" . implode("\n", $violations)
        );
    }

    public function test_no_env_calls_in_services(): void
    {
        $servicesDir = app_path('Services');
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($servicesDir)
        );

        $violations = [];
        $allowedFiles = ['SettlementService.php']; // comment only

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;
            $content = file_get_contents($file->getRealPath());
            $lines = explode("\n", $content);

            foreach ($lines as $i => $line) {
                $trimmed = ltrim($line);
                // Skip comments and blank lines
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) continue;
                if (preg_match('/\benv\s*\(/', $trimmed)) {
                    $relativePath = str_replace(base_path() . '/', '', $file->getRealPath());
                    $violations[] = "{$relativePath}:" . ($i + 1);
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Services should not use env() directly. Found:\n" . implode("\n", $violations)
        );
    }

    public function test_no_env_calls_in_middleware(): void
    {
        $middlewareDir = app_path('Http/Middleware');
        if (! is_dir($middlewareDir)) {
            $this->markTestSkipped('No middleware directory');
        }

        $violations = [];
        foreach (glob($middlewareDir . '/*.php') as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            foreach ($lines as $i => $line) {
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*')) continue;
                if (preg_match('/\benv\s*\(/', $trimmed)) {
                    $violations[] = basename($file) . ':' . ($i + 1);
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Middleware should not use env() directly. Found:\n" . implode("\n", $violations)
        );
    }

    // ─────────────────────────────────────────────
    // Config entries exist for migrated env() calls
    // ─────────────────────────────────────────────

    public function test_integration_config_entries_exist(): void
    {
        // These were previously env() calls, now must be in config
        $this->assertNotNull(config('services.wathq'));
        $this->assertNotNull(config('services.google'));
        $this->assertNotNull(config('services.nafith'));
        $this->assertArrayHasKey('places_api_key', config('services.google'));
        $this->assertArrayHasKey('reviews_api_key', config('services.google'));
        $this->assertArrayHasKey('max_amount', config('services.nafith'));
    }

    public function test_clickpay_webhook_config_exists(): void
    {
        $this->assertArrayHasKey('webhook_secret', config('services.clickpay'));
    }

    // ─────────────────────────────────────────────
    // ClickPay Webhook Tests
    // ─────────────────────────────────────────────

    public function test_clickpay_webhook_rejects_missing_signature(): void
    {
        $response = $this->postJson('/api/webhooks/clickpay', [
            'tran_ref' => 'TST123',
            'cart_id' => 'CART-001',
            'payment_result' => ['response_status' => 'A'],
        ]);

        $response->assertStatus(403);
    }

    public function test_clickpay_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/webhooks/clickpay', [
            'tran_ref' => 'TST123',
            'cart_id' => 'CART-001',
            'payment_result' => ['response_status' => 'A'],
        ], [
            'signature' => 'invalid_signature_here',
        ]);

        $response->assertStatus(403);
    }

    public function test_clickpay_webhook_accepts_valid_signature(): void
    {
        // Set a known webhook secret for testing
        config(['services.clickpay.webhook_secret' => 'test-secret-key']);
        config(['services.clickpay.server_key' => 'test-server-key']);

        $tranRef = 'TST-VALID-001';
        $cartId = 'CART-VALID-001';
        $signature = hash_hmac('sha256', $tranRef . $cartId, 'test-secret-key');

        // Create a payment record to be found
        $user = User::forceCreate([
            'first_name' => 'Test', 'last_name' => 'User',
            'email' => fake()->unique()->safeEmail(),
            'business_name' => fake()->unique()->company(),
            'phone_number' => fake()->unique()->numerify('05########'),
            'user_type' => 'admin', 'password' => bcrypt('pw'),
            'email_verified_at' => now(),
        ]);

        Payment::forceCreate([
            'user_id' => $user->id,
            'txn_code' => $tranRef,
            'amount' => '500.00',
            'invoice_number' => 'INV-TEST-' . $tranRef,
            'payment_status' => 'pending',
            'payment_details' => json_encode([]),
        ]);

        $response = $this->postJson('/api/webhooks/clickpay', [
            'tran_ref' => $tranRef,
            'cart_id' => $cartId,
            'payment_result' => [
                'response_status' => 'A',
                'response_message' => 'Approved',
            ],
        ], [
            'signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        // Verify payment status updated
        $payment = Payment::where('txn_code', $tranRef)->first();
        $this->assertEquals('paid', $payment->payment_status);
    }

    public function test_clickpay_webhook_idempotent_on_resubmission(): void
    {
        config(['services.clickpay.webhook_secret' => 'test-secret']);

        $tranRef = 'TST-IDEMP-001';
        $cartId = 'CART-IDEMP-001';
        $signature = hash_hmac('sha256', $tranRef . $cartId, 'test-secret');

        $user = User::forceCreate([
            'first_name' => 'Test', 'last_name' => 'User',
            'email' => fake()->unique()->safeEmail(),
            'business_name' => fake()->unique()->company(),
            'phone_number' => fake()->unique()->numerify('05########'),
            'user_type' => 'admin', 'password' => bcrypt('pw'),
            'email_verified_at' => now(),
        ]);

        Payment::forceCreate([
            'user_id' => $user->id,
            'txn_code' => $tranRef,
            'amount' => '500.00',
            'invoice_number' => 'INV-TEST-' . $tranRef,
            'payment_status' => 'paid', // Already paid
            'payment_details' => json_encode([]),
        ]);

        $response = $this->postJson('/api/webhooks/clickpay', [
            'tran_ref' => $tranRef,
            'cart_id' => $cartId,
            'payment_result' => ['response_status' => 'A'],
        ], ['signature' => $signature]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'already_processed']);
    }

    public function test_clickpay_webhook_wont_downgrade_paid_to_failed(): void
    {
        config(['services.clickpay.webhook_secret' => 'test-secret']);

        $tranRef = 'TST-NODOWNGRADE';
        $cartId = 'CART-NODOWNGRADE';
        $signature = hash_hmac('sha256', $tranRef . $cartId, 'test-secret');

        $user = User::forceCreate([
            'first_name' => 'Test', 'last_name' => 'User',
            'email' => fake()->unique()->safeEmail(),
            'business_name' => fake()->unique()->company(),
            'phone_number' => fake()->unique()->numerify('05########'),
            'user_type' => 'admin', 'password' => bcrypt('pw'),
            'email_verified_at' => now(),
        ]);

        Payment::forceCreate([
            'user_id' => $user->id,
            'txn_code' => $tranRef,
            'amount' => '500.00',
            'invoice_number' => 'INV-TEST-' . $tranRef,
            'payment_status' => 'paid',
            'payment_details' => json_encode([]),
        ]);

        $response = $this->postJson('/api/webhooks/clickpay', [
            'tran_ref' => $tranRef,
            'cart_id' => $cartId,
            'payment_result' => ['response_status' => 'D'], // Declined
        ], ['signature' => $signature]);

        $response->assertStatus(409); // Conflict

        // Verify payment is still paid
        $this->assertEquals('paid', Payment::where('txn_code', $tranRef)->first()->payment_status);
    }

    // ─────────────────────────────────────────────
    // Integration Resilience Tests
    // ─────────────────────────────────────────────

    public function test_integration_client_exists(): void
    {
        $this->assertFileExists(app_path('Services/Integration/IntegrationClient.php'));
    }

    public function test_integration_client_sanitizes_urls(): void
    {
        $client = new IntegrationClient('test-service');

        // Use reflection to test private method
        $method = new \ReflectionMethod($client, 'sanitizeUrl');
        $method->setAccessible(true);

        $result = $method->invoke($client, 'https://api.example.com/v1/users?token=secret&id=123');
        $this->assertEquals('https://api.example.com/v1/users', $result);
        $this->assertStringNotContainsString('secret', $result);
    }

    // ─────────────────────────────────────────────
    // Webhook Controller / Route Existence
    // ─────────────────────────────────────────────

    public function test_clickpay_webhook_controller_exists(): void
    {
        $this->assertFileExists(app_path('Http/Controllers/Webhooks/ClickPayWebhookController.php'));
    }

    public function test_clickpay_webhook_route_registered(): void
    {
        $response = $this->postJson('/api/webhooks/clickpay', []);
        // Should get 403 (no signature) not 404 (route not found)
        $this->assertNotEquals(404, $response->status());
    }

    // ─────────────────────────────────────────────
    // Local-only route protection
    // ─────────────────────────────────────────────

    public function test_dev_login_routes_not_registered_outside_local(): void
    {
        // In testing env (not local), dev-login routes should not exist
        $response = $this->get('/dev-login');
        $this->assertTrue(
            in_array($response->status(), [302, 404]),
            "dev-login must not be accessible outside local env"
        );
    }

    public function test_api_test_routes_not_accessible(): void
    {
        $response = $this->getJson('/api/singleview/token');
        $this->assertTrue(in_array($response->status(), [401, 404]));

        $response = $this->getJson('/api/nafith/auth-token');
        $this->assertTrue(in_array($response->status(), [401, 404]));
    }
}
