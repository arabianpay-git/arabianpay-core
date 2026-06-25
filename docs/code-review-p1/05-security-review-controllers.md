# Security Review — High-Risk Controllers (XSS, RBAC, Authorization)

**Branch:** `feature/core-p1`  
**Scope:** 13 high-risk controller files | **Diff:** ~1,558 lines changed  
**Date:** 2026-06-24

---

## Summary

| Severity | Count |
|----------|-------|
| **Critical** | 2 |
| **High** | 3 |
| **Medium** | 6 |
| **Low** | 4 |
| **Risk Level** | **HIGH** |

---

## CRITICAL Issues

### C-1: RefundRequestController — Missing Authorization Gate

**Location:** `app/Http/Controllers/RefundRequestController.php:16-28` — `updateRefundStatus()`  
**Related:** `app/Http/Requests/UpdateRefundStatusRequest.php` — `authorize()` returns `true`

The refund approval/rejection flow has **no authorization check**. The code itself contains a TODO:
```php
// TODO: add $this->authorize('approve', $refundRequest) once RefundRequestPolicy is created
```

The `UpdateRefundStatusRequest::authorize()` returns `true` unconditionally. The route has no Spatie permission middleware. The new `RefundApprovalService::approve()/reject()` is called without any guard.

**Impact:** Any authenticated admin/employee can approve or reject **any** refund request regardless of assignment — direct financial loss.

**Remediation:**
1. Create `RefundRequestPolicy`
2. Add `$this->authorize('approve', $refundRequest)` before the `match()`
3. Wrap the route group with `middleware('permission:refunds.manage')`

---

### C-2: OrderController::updateStatus — Missing Assignment Check

**Location:** `app/Http/Controllers/OrderController.php:367` — `updateStatus()`  
**Related:** `app/Http/Requests/UpdateOrderStatusRequest.php` — `authorize()` returns `true`

The `updateStatus()` method accepts any order by ID and changes its delivery/general status with zero assignment-based authorization. Compare with `orderDetails()` which correctly scopes queries: `->when($user->user_type !== 'admin', fn($q) => $q->where('assigned_to', $user->id))`.

**Impact:** Any employee can arbitrarily change the status of any order — marking unfulfilled orders as "delivered" or "cancelling" active orders. This triggers incorrect payment schedules and premature settlement payouts.

**Remediation:**
```php
if ($user->user_type !== 'admin' && $order->assigned_to !== $user->id) {
    return back()->with('error', 'You are not authorized to update this order.');
}
```

---

## HIGH Issues

### H-1: Missing Spatie Permission Middleware on Financial Route Groups

**Location:** `routes/web.php`

| Route Group | Operations | Risk |
|---|---|---|
| `collections.*` | View/update installments, promises, penalties | HIGH |
| `orders.*` | Accept/reject orders, update status | HIGH |
| `refund-requests.*` | View/approve/reject refunds | HIGH |
| `partial-payments.*` | Create/edit/delete partial payments | HIGH |
| `credit.*` | View credit profiles, update credit limits | CRITICAL |
| `schedule-payments.*` | View/update payments, process pay-now | HIGH |
| `transactions.*` | View transactions, generate invoices | HIGH |
| `audit.*` | View audit trails/logs, export | HIGH |

Only `CheckAdmin` middleware (checks `user_type`) gates these, but there is no granular Spatie permission enforcement.

---

### H-2: Exception Messages Leaked to End Users

**Location:** Multiple files

Six methods expose raw `$e->getMessage()` to end users:

| File | Method | Location |
|---|---|---|
| `TransferRequestController` | `store()` | `'Something went wrong: '.$e->getMessage()` |
| `TransferRequestController` | `bulkStore()` | `__('Bulk transfer failed: ').$e->getMessage()` |
| `CreditManagmentController` | `customerCreditAssessment()` | `'Failed to perform credit assessment: '.$e->getMessage()` |
| `CreditManagmentController` | `updateCreditLimit()` | `'Failed to update credit limit: '.$e->getMessage()` |
| `AuditController` | `exportLogs()` | `'Export failed: '.$e->getMessage()` |
| `AuditController` | `exportTrails()` | `'Export failed: '.$e->getMessage()` |

**Impact:** Exception messages can leak database table names, file paths, SQL fragments, and internal service configuration.

**Remediation:**
```php
Log::error('Export failed', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
return back()->with('error', 'Export failed. Please try again or contact support.');
```

---

### H-3: UpdateRefundStatusRequest::authorize() Returns `true`

**Location:** `app/Http/Requests/UpdateRefundStatusRequest.php:9-12`

The Form Request's `authorize()` method — the ideal place for authorization — is a no-op.

**Remediation:**
```php
public function authorize(): bool
{
    return $this->user()->can('approve', RefundRequest::find($this->route('id')));
}
```

---

## MEDIUM Issues

### M-1: UpdateOrderStatusRequest::authorize() Missing Assignment Check
The Form Request has `authorize(): true` but should validate that the employee is assigned to the order or is an admin.

### M-2: Several Controllers Still Use Inline Validation
The following methods use `$request->validate()` or `Validator::make()` inline instead of dedicated Form Request classes:

| Controller | Methods | Concern |
|---|---|---|
| `ClaimsController` | `store()`, `escalate()` | Claim operations |
| `SchedulePaymentController` | `update()`, `payNow()` | **Financial** |
| `PartialPaymentController` | `store()`, `update()` | **Financial** |
| `CollectionController` | `updatePartialPaymentStatus()` | **Financial** |
| `TransferRequestController` | `bulkStore()`, `fetch()` | Assignment transfers |
| `OrderController` | `rejectOrder()` | Order rejection |

### M-3: TransferRequestController::bulkStore() — Dynamic Model Type Not Whitelisted

`model_type` accepts any string and is passed directly to `updateModelAssignedTo()`. No whitelist of allowed model classes.

### M-4: CollectionController::updatePartialPaymentStatus — No Assignment Check
Any authenticated user can approve/reject any partial payment by ID. No check whether the payment's schedule belongs to an order assigned to the user.

### M-5: OrderController::rejectOrder() — Still Uses Inline Validation
While `acceptOrder()` now uses `AcceptOrderRequest`, `rejectOrder()` still uses inline `$request->validate()` for a financial operation.

### M-6: RefundRequestController — Route Parameter Not Validated
The `$id` in `updateRefundStatus()` is not validated for authorization (Laravel's route model binding on `findOrFail` mitigates SQL injection, but there's no authorization on which refund request can be modified).

---

## LOW Issues

### L-1: CollectionController — buildAlerts/buildFlags HTML Injection Surface
These methods construct HTML strings with inline `<a>` tags. Currently safe (uses `e()` escaping), but the pattern is fragile. Consider building alert data as structured arrays.

### L-2: SchedulePaymentController::payNow() — File Extension From Client
Extension comes from `$file->getClientOriginalExtension()`. While validated via mime type, there's no explicit extension whitelist. Low risk due to mime validation.

### L-3: AuditController Mask Functions Purely Cosmetic
Masking functions (`maskEmail()`, `maskIp()`) are client-side cosmetic obfuscation. The full unmasked data (`full_email` field) is also included in the JSON response.

### L-4: SingleViewController — Bank Code Hardcoded as Fallback
Fallback `'SVMOB1'` should live in `config/services.php` or the `settings` table.

---

## Positive Findings

1. **AuditTrailService integration** — Properly injected via constructor promotion and used for state-changing operations across all reviewed controllers
2. **Form Request migration** — 6 new Form Request classes created
3. **Route permission middleware added** — Claims, RBAC, Transfers now protected
4. **`UpdateCreditLimitRequest::authorize()`** — Properly uses `hasSensitivePermission('credit_decision_output')` — the gold standard
5. **No mass-assignment vulnerabilities** — All `create()` and `update()` calls use curated arrays
6. **No `where()` queries on encrypted fields** — Email/phone reads use model accessors correctly
7. **XSS protection** — User-generated content in alert/flags strings uses `e()` escaping
8. **Authorization checks maintained** — `authorizeUser()` in OrderController, `checkUser()` in SingleViewController/ComplianceController

---

## Security Checklist

| Check | Status |
|---|---|
| Spatie Permission on routes (Claims, RBAC, Transfers) | Partial |
| Spatie Permission on routes (Orders, Collections, Refunds, Payments, Credit, Audit) | Missing |
| Refund approval authorization | Missing |
| Order updateStatus assignment check | Missing |
| Form Request classes for new actions | 6 created |
| Form Request `authorize()` returning `true` | 4 of 6 need hardening |
| PII not exposed without permission | OK |
| No mass-assignment vulnerabilities | OK |
| Route helper with locale prefix | All correct |
| XSS protection via `e()` escaping | OK |
| Encrypted field access via model | OK |
| Exception messages in user-facing flash | 6 occurrences |
| Audit trail logging | Comprehensive |
| `model_type` whitelist in bulkStore | Missing |
