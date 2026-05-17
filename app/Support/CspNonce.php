<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Request-scoped CSP nonce generator.
 *
 * SAMA CSF 3.3.7 / OWASP ASVS V14.5.2: Inline scripts must be
 * authenticated via a per-request nonce rather than permitted via
 * blanket `'unsafe-inline'`. This class is bound as a singleton in
 * AppServiceProvider so every `csp_nonce()` call within one request
 * returns the same base64 value, while different requests get fresh
 * cryptographically-random nonces.
 *
 * Phase 1 (CORE-P0-10): plumbing only. The nonce is emitted in markup
 * via `@cspNonce` but is NOT yet added to the CSP header (gated by
 * `CSP_NONCE_ENFORCE`), because turning it on would invalidate the
 * ~174 existing inline scripts inventoried in
 * docs/compliance/CSP_INLINE_SCRIPT_INVENTORY.md.
 *
 * Phase 2 work will migrate those scripts and enable enforcement.
 */
final class CspNonce
{
    private ?string $nonce = null;

    public function value(): string
    {
        if ($this->nonce === null) {
            // 18 random bytes = 24 base64 chars; matches OWASP guidance
            // ("at least 128 bits of entropy") with headroom.
            $this->nonce = base64_encode(random_bytes(18));
        }

        return $this->nonce;
    }
}
