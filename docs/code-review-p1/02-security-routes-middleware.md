# Security Review — Routes, Middleware & Config

**Branch:** `feature/core-p1`  
**Scope:** `routes/web.php`, `routes/setting.php`, `routes/channels.php`, `config/auth.php`, 4 middleware files  
**Date:** 2026-06-24

---

## Summary

- **Critical Issues:** 4
- **High Issues:** 6
- **Medium Issues:** 8
- **Low Issues:** 3

The diff itself represents a net positive security improvement (adding Spatie permission middleware to previously unprotected routes), but several pre-existing issues remain.

---

## CRITICAL Issues

### C1. Routes Outside ALL Protection

The following routes are defined **outside** both the `LaravelLocalization::setLocale()` wrapper AND the admin middleware group:

| Lines | Route | Issue |
|---|---|---|
| 790-791 | `POST /send-fcm` | **No auth middleware!** Anyone can trigger push notifications |
| 800-801 | `/google-reviews` (GET + POST) | **No auth middleware!** Exposed report endpoints |
| 815-816 | `/dev-login` (GET + POST) | **No middleware at all.** Should be guarded |
| 821-824 | `/send-email` (GET + POST), `/send-sms` (GET + POST) | **No auth middleware!** Arbitrary email/SMS sending |

---

### C2. OTP Middleware — Hardcoded Phone Number

**Location:** `app/Http/Middleware/EnsureOtpVerified.php:19-20`

```php
// $phone = OtpVerificationController::PHONE;
$phone = '0545232968';  // Hardcoded!
```

The OTP middleware **always verifies OTP for the hardcoded number `0545232968`**, regardless of which user is logged in. OTP-based step-up authentication is completely broken. This was a dev/testing shortcut never reverted.

---

### C3. Audit Log Middleware — No Rate Limiting

**Location:** `app/Http/Middleware/AuditLogMiddleware.php`

Creates an `AuditLog::create()` on EVERY non-skipped request. In a DDoS scenario, this would flood the `audit_logs` table and exhaust database connections.

---

### C4. Audit Log Middleware — Sensitive Data in `failure_reason`

**Location:** `app/Http/Middleware/AuditLogMiddleware.php:136-139`

```php
if ($status >= 400) {
    $content = (string) $response->getContent();
    $failureReason = Str::limit($this->stripBinary($content), 1000);
}
```

Error responses may contain PII in validation error messages. The `stripBinary` function only removes binary/control characters — it does NOT mask PII.

---

## HIGH Issues

### H1. Routes Inside Admin Middleware but WITHOUT Spatie Permission Checks

These routes are behind `auth:sanctum` + `CheckAdmin` but have **no granular permission middleware**:

| Lines | Routes | Risk |
|---|---|---|
| 241-243 | Sensitive data **approvals** (approve/deny PII access) | **CRITICAL** |
| 274-284 | Financial dashboard, ledger, accounts, transactions | **CRITICAL** |
| 469-482 | Risk analytics (score engine, merchant scores, export) | HIGH |
| 500-512 | Collections (installments, penalties, allocations) | HIGH |
| 567-573 | Credit management (profiles, limits, schedules) | **CRITICAL** |
| 578-600 | Orders (CRUD + status changes + shipping) | HIGH |
| 636-646 | Transactions (history, payments, wallets) | HIGH |
| 651-655 | Refund requests (status updates) | HIGH |
| 670-686 | **Settlements** (generate, approve, batch payouts) | **CRITICAL** |
| 691-698 | **Payout Portal** (store, complete, fail payouts) | **CRITICAL** |
| 728-749 | Reports (15+ report endpoints) | MEDIUM |
| 768-775 | Audit logs | HIGH |

---

### H2. Settings Routes — Compliance/AML/KYC Unprotected

**Location:** `routes/setting.php:118-121`

The risk-weight store route now has `permission:risk.config.manage`, but the remaining routes (`compliance-rules`, `fraud-detection`, `aml`, `kyc`) are outside the permission group and remain unprotected.

---

### H3. Full URL Logged with Query Parameters

**Location:** `app/Http/Middleware/AuditLogMiddleware.php:144`

```php
'url' => $request->fullUrl(),
```

If URLs contain sensitive tokens in query strings (e.g., `?token=abc123`), they would be logged in plaintext.

---

### H4. Audit Log Skip Prefixes Too Broad

**Location:** `app/Http/Middleware/AuditLogMiddleware.php`

```php
protected array $skipPrefixes = [
    'assets',    // Could match admin routes with 'assets' in path
    'static',    // Very broad prefix
    'docs',      // Could match admin doc routes
];
```

---

### H5. Substring-Based PII Detection in Audit Masking

**Location:** `app/Http/Middleware/AuditLogMiddleware.php:284`

```php
if (Str::contains($lower, strtolower($piiKey))) {
```

A field named `notes` would be masked because `name` is a substring of `notes` (false positive).

---

### H6. CheckAdmin Middleware Depends on Middleware Order

**Location:** `app/Http/Middleware/CheckAdmin.php`

```php
if (Auth::check() && ! in_array(Auth::user()->user_type, ['admin', 'employee'])) {
```

If `auth:sanctum` middleware order ever changes, unauthenticated users would pass through (due to `&&` short-circuiting).

---

## MEDIUM Issues

1. **Route collision risk** — `/{id}` at root of prefixes (singleview, lean) greedily match any path segment
2. **Missing `EnsureOtpVerified`** on other sensitive routes beyond `score-update`
3. **`conversation.{userIds}` channel** uses loose comparison in `in_array()`
4. **OTP middleware** force-sets `expires_at` to `now()` after one use — too aggressive for AJAX follow-up calls
5. **CSP config** still includes `'unsafe-inline'` and `'unsafe-eval'` as fallbacks
6. **Audit log** `query` field saved without PII masking

---

## ✅ Positive Changes

| Change | Impact |
|--------|--------|
| Spatie permission middleware added to 13 route groups | Closes major authorization gaps |
| SingleView routes now behind `permission:openbanking.use` | Previously open to any admin |
| `password_timeout` reduced 10800 → 900 seconds | 15-minute window, SAMA-aligned |
| `Referrer-Policy` hardened: `strict-origin-when-cross-origin` | Prevents URL parameter leakage |
| CSP nonce infrastructure in place | Phased rollout approach |

---

## Remediation Priority

### Immediate
1. Add `auth:sanctum` to `/send-fcm`, `/send-email`, `/send-sms` routes
2. Remove hardcoded phone from `EnsureOtpVerified.php`
3. Guard `/dev-login` with environment check or remove

### This Sprint
4. Add `permission:finance.*` to settlements and payouts
5. Add `permission:credit.*` to credit management routes
6. Add `permission:approvals.*` to sensitive data approval routes
7. Add PII masking to `AuditLogMiddleware` error body capture
