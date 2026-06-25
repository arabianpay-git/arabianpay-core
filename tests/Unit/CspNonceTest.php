<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\CspNonce;
use Tests\TestCase;

/**
 * Unit tests for the request-scoped CSP nonce generator.
 *
 * CORE-P0-10 / SAMA CSF 3.3.7 / OWASP ASVS V14.5.2
 */
class CspNonceTest extends TestCase
{
    public function test_value_is_stable_within_instance(): void
    {
        $nonce = new CspNonce;

        $first = $nonce->value();
        $second = $nonce->value();

        $this->assertSame($first, $second, 'Nonce must be stable within a single request scope.');
    }

    public function test_value_is_valid_base64(): void
    {
        $nonce = new CspNonce;
        $value = $nonce->value();

        // Should be decodable without strict mode (base64url chars only)
        $decoded = base64_decode($value, strict: true);

        $this->assertNotFalse($decoded, 'Nonce must be valid base64.');
        $this->assertGreaterThanOrEqual(18, strlen($decoded), 'Decoded nonce must be at least 18 bytes (144 bits).');
    }

    public function test_fresh_instance_returns_different_value(): void
    {
        $a = (new CspNonce)->value();
        $b = (new CspNonce)->value();

        // Statistically certain to differ; collision probability is negligible at 144 bits
        $this->assertNotSame($a, $b, 'Separate CspNonce instances must produce different nonces.');
    }

    public function test_singleton_binding_returns_same_value_per_request(): void
    {
        // In real requests the app container is fresh per request.
        // Within a single test invocation the singleton must be stable.
        $first = app(CspNonce::class)->value();
        $second = app(CspNonce::class)->value();

        $this->assertSame($first, $second, 'app(CspNonce::class) must resolve the same instance within one request.');
    }

    public function test_csp_nonce_helper_returns_same_value_as_class(): void
    {
        $fromClass = app(CspNonce::class)->value();
        $fromHelper = csp_nonce();

        $this->assertSame($fromClass, $fromHelper, 'csp_nonce() helper must mirror app(CspNonce::class)->value().');
    }
}
