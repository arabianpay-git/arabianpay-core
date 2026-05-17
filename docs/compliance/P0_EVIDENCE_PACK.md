# SAMA P0 Evidence Pack — ArabianPay Core

> **Branch:** `security-audit-base`
> **Verification date:** 2026-04-15
> **Verifier:** Technical Lead (orchestrated remediation run)
> **Scan script:** `scripts/sama-p0-verify.sh` — exit 0 on success
> **Scan result:** PASS = 25, FAIL = 0, WARN = 0

## Scope

All 11 P0 compliance controls for the core repository
(arabianpay-admin) identified in
`SAMA-Compliance/01_core_tasks.md` and
`SAMA-Compliance/ARABIANPAY_AUDITOR_GRADE_COMPLIANCE_REPORT.md`.

DevOps-layer follow-ups (DB password rotation, git-history rewrite in
infra repo, production env updates) are tracked separately under
CROSS-P0-01 / CROSS-P0-02. The rotation matrix lives in
`docs/compliance/SECRET_ROTATION_CHECKLIST.md`.

## Control Status

| ID          | Control                                              | Evidence                                                                          | Commit       | Status |
| ----------- | ---------------------------------------------------- | --------------------------------------------------------------------------------- | ------------ | ------ |
| CORE-P0-01  | Register `AuditLogMiddleware` globally               | `bootstrap/app.php` appends the middleware; 7 feature tests cover request/path/PII masking | 256427e | ✅     |
| CORE-P0-02  | Mandatory 2FA, bypass removed                        | `config/fortify.php` hardcodes `mandatory_two_factor = true`; no bypass tokens anywhere in `app/` | d1729ea | ✅     |
| CORE-P0-03  | Remove hardcoded DB password                         | `config/database.php` `DB_OLD_PASSWORD` default emptied; actual rotation tracked in `SECRET_ROTATION_CHECKLIST.md` | 256427e | ✅ (code), ⏳ (DevOps rotation) |
| CORE-P0-04  | Remove `dd()`/`dump()` from production controllers   | 0 uncommented debug calls in `app/Http/Controllers/` (scanner check)              | 256427e      | ✅     |
| CORE-P0-05  | Disable debug; populate error views                  | `.env.example` `APP_DEBUG=false`; views `401/405/408/504` created/populated; null-safe in `error.blade.php` | 256427e | ✅     |
| CORE-P0-06  | Enable session encryption                            | `config/session.php` default `env('SESSION_ENCRYPT', true)`; `.env.example` `SESSION_ENCRYPT=true` | 256427e | ✅     |
| CORE-P0-07  | CORS strict allow-list                               | `config/cors.php` wildcard removed; origin list via `CORS_ALLOWED_ORIGINS`        | 256427e      | ✅     |
| CORE-P0-08  | Sanctum token expiration + refresh endpoint          | `config/sanctum.php` default expiration 60 min; `POST /api/auth/refresh-token` registered, rate-limited, audit-logged | 50a4922 | ✅     |
| CORE-P0-09  | Secret hardening                                     | `config/simah.php` SIMAH default credentials removed; `.env*` gitignored; rotation checklist created | 50a4922 | ✅ (code), ⏳ (DevOps rotation) |
| CORE-P0-10  | CSP nonce helper + inline script inventory (Phase 1) | `App\Support\CspNonce`, `csp_nonce()` helper, `@cspNonce` Blade directive; header now emits nonce; 174-file Phase 2 inventory | b82faeb | ✅ (Phase 1), ⏳ (Phase 2 migration) |
| CORE-P0-11  | Schedule daily reconciliation                        | `app/Services/Finance/ReconciliationService.php`; `reconciliation:daily` scheduled at 02:30 with `onOneServer`+`withoutOverlapping` | 50a4922 | ✅     |

## Commit Trail

```
b82faeb fix(security): SAMA P0 Wave 3 Phase 1 — CSP nonce plumbing and inventory
50a4922 fix(security): SAMA P0 Wave 2 — token lifecycle, reconciliation, secret hardening
d1729ea fix(security): enforce mandatory 2FA, remove bypass path [CORE-P0-02]
256427e fix(security): SAMA P0 Wave 1 — critical compliance remediation
fac231d chore: add laravel/boost dev dependency for AI-assisted development  (baseline)
```

## Open Follow-Ups (Out of P0 Scope)

These items are **not** blockers for the P0 sign-off, but must be
completed before the SAMA sandbox submission. They are handled by
DevOps / Infra teams, not by application code changes.

1. Execute the rotation matrix in `docs/compliance/SECRET_ROTATION_CHECKLIST.md`:
   - DB password (CROSS-P0-01)
   - `MICROSOFT_CLIENT_SECRET`
   - `SIMAH_USERNAME` / `SIMAH_PASSWORD`
   - FCM and Firebase credentials
   - AWS keys
   - Broadcasting / Pusher / Reverb secrets
2. Full infra repo git-history rewrite (CROSS-P0-02). No secret-bearing
   commits exist in **this** core repo (verified: `git log --all -- .env`
   returns empty).
3. Production `.env` updates to match `.env.example`:
   - `APP_DEBUG=false`
   - `SESSION_ENCRYPT=true`
   - `SANCTUM_EXPIRATION=60`
   - `CORS_ALLOWED_ORIGINS=…` real list
   - `MANDATORY_TWO_FACTOR` removal (config is now hardcoded)
4. Flip `CSP_NONCE_ENFORCE=true` only after the Phase 2 migration of
   all 174 inline-script files listed in
   `docs/compliance/CSP_INLINE_SCRIPT_INVENTORY.md`.

## How to Re-Verify

Any time after this date:

```bash
bash scripts/sama-p0-verify.sh
```

The script is idempotent, offline-safe (no network, no DB writes), and
prints a per-control PASS/FAIL table. Exit code 0 = ready to submit for
compliance review; non-zero = regression, investigate before continuing.

## Sign-Off

| Role                | Name | Date | Signature |
| ------------------- | ---- | ---- | --------- |
| Technical Lead      |      |      |           |
| Security Lead       |      |      |           |
| Compliance Lead     |      |      |           |
| DevOps Lead         |      |      |           |
