# 01 — `core/` (Admin / Backoffice) — Remediation Tasks

**Repo purpose:** Internal admin app — risk, finance, compliance, collections, merchant/customer management.
**Tech:** Laravel, MySQL, Spatie Permission, Fortify, Sanctum.
**Owner team:** Backend (Core).
**Total tasks:** 38 (P0: 11, P1: 13, P2: 8, P3: 6).

> **Read first:** [`00_README.md`](00_README.md) for rules, definition of done, and PR conventions.

---

## P0 — Critical, deploy this week

These are tier-1 audit blockers. Any single open P0 means an external auditor stops the assessment.

### CORE-P0-01 — Register `AuditLogMiddleware` globally
- **Source finding:** FC-01
- **Files:** `core/bootstrap/app.php`
- **What to do:**
  - Inside the `withMiddleware()` closure, append the middleware to the global stack:
    ```php
    $middleware->append(\App\Http\Middleware\AuditLogMiddleware::class);
    ```
  - Verify the middleware's `$skipPaths` excludes `/health`, `/up`, `/_debugbar/*`, and static asset routes so we don't fill the table with noise.
- **Acceptance criteria:**
  - After deploy, hit any admin route — a row appears in `audit_logs` with masked PII.
  - `php artisan route:list` shows `AuditLogMiddleware` on every non-skipped route.
- **Effort:** S
- **Owner:** Backend
- **Tests:** Add a feature test that hits an admin route and asserts an `audit_logs` row was created.

### CORE-P0-02 — Enable mandatory 2FA, remove bypass
- **Source finding:** FC-02
- **Files:** `core/.env` (lines 70–71); `core/app/Providers/FortifyServiceProvider.php` (lines 59–60, 86–107)
- **What to do:**
  - In `.env`: set `MANDATORY_TWO_FACTOR=true` and `BYPASS_TWO_FACTOR_CHALLENGE=false` for **every** environment (dev, staging, prod).
  - In `FortifyServiceProvider`: delete the conditional block that auto-logs-in when `BYPASS_TWO_FACTOR_CHALLENGE` is true. There is no legitimate reason to keep that branch.
  - Document the ops procedure for breaking-glass admin recovery (one-time backup codes, signed by CTO).
- **Acceptance criteria:**
  - Login flow: after correct password, 2FA challenge always appears.
  - Grep confirms no remaining reference to `BYPASS_TWO_FACTOR_CHALLENGE`.
- **Effort:** S
- **Owner:** Backend + DevOps

### CORE-P0-03 — Remove hardcoded DB password
- **Source finding:** C-01
- **Files:** `core/config/database.php` line 121
- **What to do:**
  - Replace `'password' => env('DB_OLD_PASSWORD', 'Asad@123')` with `'password' => env('DB_OLD_PASSWORD', '')`.
  - Confirm the old DB password `Asad@123` is **rotated** in the database server before the deploy. Coordinate with DevOps (CROSS-P0-01).
- **Acceptance criteria:**
  - `git grep -i "Asad@123"` returns no results.
  - App still connects in all environments (env var is set).
- **Effort:** S
- **Owner:** Backend + DevOps

### CORE-P0-04 — Remove `dd()` calls from production controllers
- **Source finding:** C-07, F-SEC-16
- **Files:** `core/app/Http/Controllers/FinancialAccounts.php`, `StaticsController.php`, `AccountController.php`, `AccountController2.php`
- **What to do:**
  - Search and remove every `dd(`, `dump(`, `var_dump(` from the four named controllers and any others returned by `git grep -nE '\bdd\(|\bdump\(|\bvar_dump\('`.
  - Where actual debugging is needed, replace with `Log::debug(...)` (which is suppressed in production).
- **Acceptance criteria:**
  - `git grep -nE '\b(dd|dump|var_dump)\('` returns zero results in `app/`.
  - CI lint job fails any future PR that introduces them.
- **Effort:** S

### CORE-P0-05 — Disable debug mode in all `.env` files
- **Source finding:** FC-09, F-SEC-15
- **Files:** `core/.env` line 4
- **What to do:**
  - Set `APP_DEBUG=false` in every environment except local dev.
  - Verify custom 404 / 500 / 419 / 403 / 401 Blade views exist; if not, create generic ones that do not leak request details.
- **Acceptance criteria:**
  - Triggering a 500 in staging shows a generic page, not a stack trace.
- **Effort:** S

### CORE-P0-06 — Enable session encryption
- **Source finding:** FC-04, F-SEC-13
- **Files:** `core/.env` line 34
- **What to do:**
  - Set `SESSION_ENCRYPT=true`.
  - Truncate `sessions` table in non-prod first to flush any incompatible records, then deploy.
- **Acceptance criteria:**
  - New rows in `sessions.payload` are encrypted (not human-readable JSON).
  - Login flow continues to work after change.
- **Effort:** S

### CORE-P0-07 — Restrict CORS — no wildcards with credentials
- **Source finding:** FC-06, F-SEC-11
- **Files:** `core/config/cors.php` lines 20, 28
- **What to do:**
  - Replace `'allowed_origins' => ['*']` with an explicit list driven by env:
    ```php
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),
    'supports_credentials' => true,
    ```
  - Set `CORS_ALLOWED_ORIGINS=https://core.arabianpay.net,https://partners.arabianpay.net,https://api.arabianpay.net` per environment.
- **Acceptance criteria:**
  - Cross-origin request from an unlisted origin is blocked by browser.
- **Effort:** S

### CORE-P0-08 — Set Sanctum token expiration
- **Source finding:** FC / C-06, F-SEC-12
- **Files:** `core/config/sanctum.php`
- **What to do:**
  - Change `'expiration' => null` to `'expiration' => env('SANCTUM_EXPIRATION', 60)` (minutes).
  - Implement a refresh endpoint that requires the current valid token.
  - Coordinate with `api/` (API-P0-09) and Flutter (FLUTTER-P1-08) for refresh-token plumbing.
- **Acceptance criteria:**
  - A token issued more than 60 minutes ago is rejected with 401.
  - Refresh flow returns a fresh token without requiring re-login.
- **Effort:** S (config) + M (refresh endpoint)

### CORE-P0-09 — Remove `Asad@123`, secrets, and SQL dump from git history
- **Source finding:** SEC-03, C-08, F-SEC-19
- **Files:** `core/.env`, `arabianpaydb (1).sql`, anything matched by secrets scan
- **What to do:**
  - `git rm --cached core/.env`; add `.env` to `.gitignore`.
  - `git rm` the SQL dump.
  - Coordinate full history rewrite with DevOps (CROSS-P0-02). Alone, removing from `HEAD` does not undo exposure — secrets must be **rotated** as well.
- **Acceptance criteria:**
  - `git log --all -- core/.env` returns nothing for the rewritten history.
  - Secret-scanning CI job (CROSS-P1-02) is green.
- **Effort:** M

### CORE-P0-10 — Remove `unsafe-inline` / `unsafe-eval` from CSP (inline-script audit phase)
- **Source finding:** FC-05, C-09 / F-SEC-20
- **Files:** `core/config/csp.php` lines 12–13; all Blade files using inline `<script>` / `<style>`
- **What to do — phase 1 only this sprint:**
  - Inventory every inline `<script>` in Blade. Add a CSP nonce helper (e.g., `@cspNonce` directive).
  - Replace inline scripts with external `.js` files where possible. For unavoidable inline, use `nonce="{{ csp_nonce() }}"`.
  - Phase 2 (CORE-P3-04) actually flips the policy.
- **Acceptance criteria:**
  - Blade audit report attached to the PR listing every inline script and its remediation status.
- **Effort:** L (this is a multi-PR effort; ship the audit + nonce helper this sprint)

### CORE-P0-11 — Schedule `ReconciliationService` to run daily
- **Source finding:** C-14
- **Files:** `core/app/Console/Kernel.php` (create if missing)
- **What to do:**
  - Add:
    ```php
    $schedule->call(fn () => app(\App\Services\Finance\ReconciliationService::class)->reconcileAllPaid())
             ->dailyAt('02:30')
             ->onOneServer()
             ->withoutOverlapping();
    ```
  - Verify cron `* * * * * php artisan schedule:run` is configured on the production scheduler host.
- **Acceptance criteria:**
  - Reconciliation log entry appears in `audit_trails` every day after 02:30.
- **Effort:** S

---

## P1 — High, this sprint

### CORE-P1-01 — Route `RefundRequestController` through `RefundApprovalService`
- **Source finding:** BYP-01
- **Files:** `core/app/Http/Controllers/RefundRequestController.php` line 24
- **What to do:**
  - Replace direct `$refundRequest->update(...)` calls with `app(RefundApprovalService::class)->approve($refundRequest, $actor)` (or `->reject()`).
  - Add `$this->authorize('approve', $refundRequest)` at controller entry.
  - Wrap the change in `DB::transaction()`.
- **Acceptance criteria:**
  - Direct `update()` no longer present in the controller.
  - Refund changes produce an `approval_requests` row and an `audit_trails` row.
- **Effort:** M

### CORE-P1-02 — Add `AuditTrailService` to `ClaimsController`
- **Source finding:** BYP-02
- **Files:** `core/app/Http/Controllers/ClaimsController.php`
- **What to do:**
  - Inject `AuditTrailService`. Call `logUpdated($claim, $before, $after, $actor)` from `updateAttempt()`, `resolve()`, and `escalate()`.
  - Capture before/after state explicitly (clone before update).
- **Acceptance criteria:**
  - Each lifecycle action produces an `audit_trails` row with both states.
- **Effort:** M

### CORE-P1-03 — Wrap approval services in `DB::transaction()`
- **Source finding:** Section 4.4 (auditor)
- **Files:** `core/app/Services/RefundApprovalService.php` (`approve`); `core/app/Services/RiskOverrideApprovalService.php` (`approveAndApply`)
- **What to do:**
  - Wrap the entire approve flow in `DB::transaction(function () use (...) { ... })`.
  - Inside the transaction: write the approval, write the audit trail, mutate the target entity. All-or-nothing.
- **Acceptance criteria:**
  - Forced exception during the apply step rolls back the approval.
- **Effort:** S

### CORE-P1-04 — Add granular permissions to ungated admin routes
- **Source finding:** Section 3.1 (auditor)
- **Files:** `core/routes/web.php` (lines 127–417 contain the ungated routes); `core/database/seeders/PermissionSeeder.php`
- **What to do:**
  - For each high-risk route group listed below, add `->middleware('permission:<perm>')`:
    | Route group | Permission |
    |---|---|
    | `/admin/messages/*` | `messages.manage` |
    | `/admin/roles*`, `/admin/permissions*`, `/admin/user-roles*` | `rbac.manage` |
    | `/admin/transfer-requests*` | `finance.transfers.manage` |
    | `/admin/accounts/*` (customers) | `customers.manage` |
    | `/admin/suppliers/*` | `merchants.manage` |
    | `/admin/investment-pools*` | `pools.manage` |
    | `/admin/claims*` | `collections.manage` |
    | `/admin/checkouts*` | `checkouts.manage` |
    | `/admin/branches*` | `branches.manage` |
    | `/admin/lean/*`, `/admin/singleview/*` | `openbanking.use` |
    | `settings/risk-weight*` | `risk.config.manage` |
  - Add the new permissions to `PermissionSeeder` and run it.
- **Acceptance criteria:**
  - `php artisan route:list --columns=uri,middleware` shows `permission:` on every listed route.
  - User without the permission gets 403.
- **Effort:** L (many routes; split into two PRs by area)

### CORE-P1-05 — Add audit logging to `SupplierFinanceService`
- **Source finding:** F-GOV-11
- **Files:** `core/app/Services/Finance/SupplierFinanceService.php`
- **What to do:**
  - Inject `AuditTrailService`. In `getUpcomingPayout()` and `getLedger()`, after computing the result, call `logAccess()` with: actor, supplier_id, period, record count, request id.
- **Acceptance criteria:**
  - Every read of supplier financial data leaves an `audit_trails` row.
- **Effort:** M

### CORE-P1-06 — Add audit logging to `ExpenseService` and `ProcessScheduledPayments`
- **Source finding:** Section 6.3 (technical)
- **Files:** `core/app/Services/ExpenseService.php`; `core/app/Jobs/ProcessScheduledPayments.php`
- **What to do:**
  - Log expense creation/edit with before/after state.
  - In `ProcessScheduledPayments`, log success **and** failure paths (currently only failures logged).
- **Effort:** M

### CORE-P1-07 — Add `$fillable` / `$guarded` to unprotected models
- **Source finding:** F-SEC-24
- **Files:** `core/app/Models/SchedulePaymentReminder.php`, `Membership.php`, `RiskManagement.php`
- **What to do:**
  - Add `protected $fillable = [...]` listing only the safe columns. Never use `$guarded = []`.
- **Acceptance criteria:**
  - Test with `Model::create($maliciousArray)` confirms protected columns ignored.
- **Effort:** S

### CORE-P1-08 — Tighten password confirmation timeout
- **Source finding:** F-SEC-25
- **Files:** `core/config/auth.php` (`password_timeout`)
- **What to do:**
  - Reduce from 10800 to 900 (15 min). For sensitive screens (financial config, risk weights, RBAC), force re-confirmation regardless.
- **Effort:** S

### CORE-P1-09 — Add HSTS, X-Frame-Options, Permissions-Policy headers consistently
- **Source finding:** Section 6.5 (auditor)
- **Files:** `core/app/Http/Middleware/SecureHeaders.php` (or new middleware)
- **What to do:** Ensure header set is identical in core and partners and is enforced on every response.
  - HSTS: `max-age=31536000; includeSubDomains; preload`
  - `X-Frame-Options: DENY`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: geolocation=(), microphone=(), camera=()`
- **Acceptance criteria:** `curl -I` against any route returns all five headers.
- **Effort:** S

### CORE-P1-10 — Block direct mutations on financial models from controllers
- **Source finding:** Section 4 (auditor)
- **Files:** `core/app/Models/Payment.php`, `SchedulePayment.php`, `Transaction.php`, `FEntry.php`, `FTransaction.php`
- **What to do:**
  - Add a `protected static function booted()` that forbids `update()` outside an approved service. Easiest: introduce an internal `WithApprovalContext` trait that controllers/services explicitly enter; otherwise a model observer throws.
- **Acceptance criteria:** A test that calls `$payment->update(['amount' => 1])` directly throws.
- **Effort:** M

### CORE-P1-11 — Implement API Resource classes for 10 admin endpoints
- **Source finding:** F-API-FORMAT
- **What to do:** Create `App\Http\Resources\*` for the endpoints below to enforce a stable response shape. Strip internal columns (timestamps, soft-delete flags, fk noise) by default.
- **Priority order** (ranked by traffic from `audit_logs` + PII sensitivity — measured 2026-04-20):

  | # | Endpoint | Hits | Notes |
  |---|---|---|---|
  | 1 | `GET /admin/suppliers` | 15,211 | Merchant list — likely over-exposes raw model |
  | 2 | `GET /admin/notifications` | 19,731 | Polled constantly |
  | 3 | `GET /admin/supplier/{id}` | 3,624 | Merchant detail with PII |
  | 4 | `GET /admin/dashboard` | 1,912 | Aggregated — no stable shape today |
  | 5 | `GET /admin/risk/risk-analysis/{id}/user` | 633 | Sensitive — exposes credit scores |
  | 6 | `GET /admin/supplier-compliance/{id}` | 630 | KYB documents, PII |
  | 7 | `GET /admin/products` | 765 | High volume |
  | 8 | `GET /admin/supplier-products/{id}` | 433 | Related to suppliers |
  | 9 | `GET /admin/categories` | 396 | Referenced everywhere |
  | 10 | `GET /admin/supplier-shop-settings/{id}` | 370 | Settings data |

- **Effort:** M

### CORE-P1-12 — Form Request validation for 20 write endpoints
- **Source finding:** Validation coverage 10%
- **What to do:** Replace inline `$request->validate(...)` with `App\Http\Requests\*`. Authorize within the request via `authorize()`.
- **Priority order** (traffic-first, then risk-filled gaps — measured 2026-04-20):

  | # | Endpoint | Hits | Risk |
  |---|---|---|---|
  | 1 | `POST /admin/transfer-requests` | 363 | 🔴 Financial |
  | 2 | `POST /admin/media/upload` | 306 | File upload |
  | 3 | `PUT /admin/products/{id}` | 180 | High volume |
  | 4 | `PUT /admin/supplier-status/{id}` | 156 | Merchant lifecycle |
  | 5 | `POST /admin/brands` | 126 | |
  | 6 | `POST /admin/shop-settings` | 98 | |
  | 7 | `POST /admin/supplier/{id}/update-document` | 89 | KYB docs |
  | 8 | `PUT /admin/categories/{id}` | 66 | |
  | 9 | `PUT /admin/supplier-status/approve/{id}` | 65 | Merchant approval |
  | 10 | `POST /admin/products/bulk-upload` | 39 | |
  | 11 | `PUT /admin/orders/orders/{id}/status` | 18 | Order lifecycle |
  | 12 | `POST /admin/orders/orders/accept` | 19 | |
  | 13 | Refund status update (`POST`/`PUT`) | low | 🔴 Financial |
  | 14 | Settlement approval routes (`POST`/`PUT`) | low | 🔴 Financial |
  | 15 | Risk-weight settings (`POST`/`PUT`) | low | 🔴 Risk config |
  | 16 | RBAC roles/permissions (`POST`/`PUT`) | low | 🔴 Privilege escalation |
  | 17 | Customer account create/update | low | 🔴 PII |
  | 18 | Credit limit update | low | 🔴 Financial |
  | 19 | Expense creation | low | 🔴 Financial |
  | 20 | Investment pool actions | low | 🔴 Financial |

- **Effort:** M

### CORE-P1-13 — Verify `EncryptsAttributes` works after framework upgrades
- **Source finding:** RELEASE_READINESS — Risk #8
- **What to do:** Fix the Jetstream test failures caused by the trait. Either harden the trait against null-byte attributes during model boot, or document the breaking interaction and patch the test bootstrap.
- **Acceptance criteria:** Test suite runs against MySQL (not SQLite — see CROSS-P1-04) with zero pre-existing failures.
- **Effort:** M

---

## P2 — PDPL & Privacy (week 5–6)

### CORE-P2-01 — Encrypt remaining PII columns
- **Source finding:** F-PDPL-02, Section 5.2 (auditor)
- **Tables / columns:**
  - `customers.cr_data` (JSON), `customers.nafath_data` (JSON), `customers.date_of_birth`
  - `merchants.goverment_data` (JSON)
  - `nafath_verifications.national_id`, `nafath_verifications.phone_number`
  - `otps.phone`
  - `login_attempts.phone_number`, `login_attempts.ip_address`
  - `audit_logs.ip_address` (consider hashing instead, for analytics)
  - `audit_trails.actor_email`, `audit_trails.ip_address`
- **What to do:**
  - Add `EncryptsAttributes` trait + `$encryptable` array to each model.
  - Write a backfill artisan command that re-saves existing rows under the encryption key.
  - Update any aggregation queries that touched these columns.
- **Acceptance criteria:** New rows store ciphertext; backfill command run in staging produces no plaintext PII left.
- **Effort:** L

### CORE-P2-02 — Implement consent versioning
- **Source finding:** F-PDPL-01
- **Files:** `core/app/Services/Privacy/ConsentService.php`; consent migration
- **What to do:**
  - Add columns `version`, `text_hash`, `text_locale`, `granted_via` to the consent records table.
  - Whenever consent text changes, bump `version` and require re-grant on next login.
- **Acceptance criteria:** Consent records show the exact text version a user agreed to.
- **Effort:** M

### CORE-P2-03 — Implement automated data retention
- **Source finding:** F-PDPL-03
- **What to do:**
  - Define retention class per data category in `config/pdpl.php` (e.g., `otps` 30d, `login_attempts` 90d, closed accounts 7y).
  - Create scheduled job per category (`PurgeOldOtps`, `PurgeOldLoginAttempts`, `AnonymizeClosedAccounts`).
  - Schedule in `Console\Kernel`.
- **Acceptance criteria:** A row older than its retention is gone (or anonymized) after the job runs.
- **Effort:** L

### CORE-P2-04 — Make `DataSubjectRequestService` actually delete
- **Source finding:** FC-13, Section 5.5 (auditor)
- **Files:** `core/app/Services/Privacy/DataSubjectRequestService.php` lines 110–127
- **What to do:**
  - Define which tables and columns are erasable vs. anonymizable vs. legally-blocked.
  - Implement an `executeErasure()` method that, in a transaction:
    - `forceDelete()` rows the subject is the primary owner of and that are not legally blocked.
    - Anonymizes rows that must be retained for accounting/SAMA (replace name/email/phone/national_id with hashes).
  - Mark the request `completed` only after execution and write an `audit_trails` row recording every table touched and row counts.
- **Acceptance criteria:** A test creates a user with PII across 5 tables, fires an erasure, then asserts the data is gone or anonymized.
- **Effort:** L

### CORE-P2-05 — Implement DSR access export
- **Source finding:** F-PDPL right-of-access
- **What to do:** Service method that produces a JSON bundle of every row containing the subject's PII across the platform; signed and emailed to the verified subject email; retention 30 days.
- **Effort:** M

### CORE-P2-06 — Add masking for admin views of customer PII
- **Source finding:** Section 5.3 (auditor)
- **What to do:** Default-mask PII fields in admin Blade views (last 4 digits / domain only); require a `SensitiveDataApprovalController` time-bounded grant (already exists) to unmask.
- **Effort:** M

### CORE-P2-07 — PII-mask `ip_address` in `audit_logs` at write time
- **Source finding:** Section 5.2 (auditor)
- **What to do:** Hash IP with a per-tenant salt before persisting. Store the hash, not the raw IP. Adjust the existing `maskIp()` (only used for display) to be the persistence path.
- **Effort:** S

### CORE-P2-08 — Wire `ConsentService` into all PII-collecting flows
- **Source finding:** FC-12 (Core side — Backoffice-initiated PII)
- **What to do:** Any backoffice-initiated PII capture (manual customer add, manual merchant add, manual KYB upload) calls `ConsentService::grant()` with the agent acting on behalf of the subject and a written consent reference.
- **Effort:** M

---

## P3 — Quality, Maintainability, Audit Packaging (week 7+)

### CORE-P3-01 — Refactor 15 fat controllers (>500 lines)
- **Files:** `OrderController.php` (1828 lines), `AccountController.php` (1123), `AccountController2.php` (1367), and 12 others.
- **What to do:** Extract domain operations into services. Controllers become thin (≤200 lines, delegating to services).
- **Effort:** L (multi-sprint)

### CORE-P3-02 — Merge `AccountController` + `AccountController2`
- **Source finding:** C-11
- **What to do:** Diff the two; extract overlap into shared service; collapse to one controller.
- **Effort:** M

### CORE-P3-03 — Form Requests for remaining ~70 controllers
- **Effort:** L

### CORE-P3-04 — Flip CSP to nonce-only (drop `unsafe-inline` / `unsafe-eval`)
- **Pre-req:** CORE-P0-10 finished.
- **Files:** `core/config/csp.php`
- **Effort:** M

### CORE-P3-05 — SIEM integration for audit logs
- **What to do:** Ship `audit_logs` and `audit_trails` to a central SIEM (Splunk/ELK/Wazuh) with a 7-year retention bucket.
- **Effort:** L

### CORE-P3-06 — Custom exception hierarchy
- **What to do:** Create `App\Exceptions\Domain\*` (e.g., `MakerCheckerViolationException`, `InsufficientLimitException`) and convert `throw new \Exception(...)` calls in services.
- **Effort:** M

---

## Definition of Done — repo-specific

In addition to the cross-cutting DoD in `00_README.md`:

- [ ] `php artisan route:list --columns=uri,middleware | grep -v 'permission:'` returns only routes that are intentionally ungated (login, password reset, public health).
- [ ] Running the test suite against MySQL produces 165+ tests passing with zero pre-existing failures (resolves RELEASE_READINESS Risk #8).
- [ ] `git grep -nE '\b(dd|dump|var_dump)\(' app/` returns no results.
- [ ] No `env()` call exists outside `config/`.
