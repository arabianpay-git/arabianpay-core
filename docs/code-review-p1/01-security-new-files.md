# Security Review — New Files (FormRequests, Resources, Traits, Services)

**Branch:** `feature/core-p1`  
**Scope:** 34 newly added (untracked) files  
**Date:** 2026-06-24

---

## Summary

| Severity | Count |
|----------|-------|
| **CRITICAL** | 3 |
| **HIGH** | 5 |
| **MEDIUM** | 5 |
| **LOW** | 2 |
| **Total** | **15** |

---

## CRITICAL Issues

### C1. PII (Actor Email) Leaked into Audit Log Summaries

**Location:** `app/Services/Finance/RefundApprovalService.php:29, 44`

The `approve()` and `reject()` methods embed `$actor->email` directly into `action_summary` strings passed to `AuditTrailService::logUpdated()`. Per `app/Models/User.php:66-72`, `email` is an `encryptableAttribute` — it is decrypted on model retrieval via the `EncryptsAttributes` trait. This means the **plaintext email address** is persisted in the audit trail's `action_summary` column.

**Example:**
```php
$this->auditTrail->logUpdated(
    $refund->fresh(),
    $before,
    'Refund request approved by '.$actor->email,  // ← PII in plaintext
);
```

**Remediation:** Use the user's non-PII identifier (`$actor->id` or `$actor->name`) in summaries.

---

### C2. Batch Authorization Bypass — FormRequests Returning `true`

**Location:** Multiple files

While `CheckAdmin` middleware restricts routes to `admin`/`employee` user types, it does **not** check for specific Spatie permissions. The following FormRequests use `return true` for operations that should require granular permission checks:

| File | Operation | Should Require |
|------|-----------|---------------|
| `BatchSettlementRequest.php:11` | Batch-process settlements | `transaction_references` or `settlement_operations` |
| `BatchCancelSettlementRequest.php:11` | Batch-cancel settlements | `transaction_references` |
| `CancelSettlementRequest.php:11` | Cancel a settlement | `transaction_references` |
| `UpdateRefundStatusRequest.php:11` | Approve/reject refunds | `refund_management` or `transaction_references` |
| `StoreRiskWeightRequest.php:11` | Modify risk scoring model | `risk_drivers_aggregated` |
| `UpdateCustomerStatusRequest.php:11` | Blacklist/suspend customers | `credit_decision_output` |
| `UpdateSupplierStatusRequest.php:11` | Change supplier lifecycle | `supplier_management` |
| `UpdateOrderStatusRequest.php:11` | Change order state | `order_management` |
| `StoreTransferRequest.php:11` | Transfer models between users | High-privilege admin only |
| `ApproveSupplierStatusRequest.php:11` | Supplier onboarding with financial terms | `supplier_management` |

**Remediation:** Use the pattern from `UpdateCreditLimitRequest.php:11`:
```php
public function authorize(): bool
{
    return hasSensitivePermission('credit_decision_output');
}
```

---

### C3. Unrestricted `model_type` Enables Arbitrary Eloquent Model Transfer

**Location:** `app/Http/Requests/StoreTransferRequest.php:18`

```php
'model_type' => 'required|string',
```

The `model_type` field accepts **any string** with no whitelist. If the controller uses `$model_type::find($model_id)`, an attacker could supply an arbitrary class name to transfer unrelated or sensitive records between users.

**Remediation:** Whitelist allowed model types:
```php
'model_type' => 'required|in:App\Models\Order,App\Models\Product,App\Models\SupportTicket',
```

---

## HIGH Issues

### H1. SVG Upload Allows Stored XSS

**Location:** `app/Http/Requests/UploadMediaRequest.php:20`

```php
'mimes:jpeg,png,jpg,webp,gif,svg,pdf,mp4,mov,avi,mkv',
```

SVG files are XML documents that can contain embedded `<script>` tags, event handlers, and other JavaScript execution vectors.

**Remediation:** Either remove `svg` from allowed mime types, or serve SVGs via a sanitizer that strips scripts and event handlers.

---

### H2. Missing File Validation on `logo`

**Location:** `app/Http/Requests/StoreBrandRequest.php:18`

```php
'logo' => ['required'],  // No file, image, mimes, or max constraints
```

**Remediation:**
```php
'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
```

---

### H3. Missing Foreign Key Validation on `bank_id`

**Location:** `app/Http/Requests/UpdateSupplierDocumentRequest.php:27`

```php
'bank_id' => 'required',  // No exists:banks,id
```

**Remediation:**
```php
'bank_id' => 'required|exists:banks,id',
```

---

### H4. No Validation for `amount_type = percent` Capping

**Location:** `app/Http/Requests/StoreExpenseRequest.php:18-20`

When `amount_type` is `percent`, the `amount` is not capped at 100.

**Remediation:** Use conditional validation:
```php
'amount' => [
    'required',
    'numeric',
    'min:0',
    $this->input('amount_type') === 'percent' ? 'max:100' : '',
],
```

---

### H5. `AcceptOrderRequest` Authorization Logic Potentially Bypassable

**Location:** `app/Http/Requests/AcceptOrderRequest.php:17-18`

```php
return $user->user_type === 'admin'
    || ($user->user_type === 'employee' && $user->is_manager);
```

The `$user->is_manager` check uses truthy evaluation. If `is_manager` is stored as a string (`'0'`, `'false'`), the check may pass unexpectedly.

**Remediation:** Use the `hasSensitivePermission()` pattern or explicit boolean cast:
```php
return hasSensitivePermission('order_acceptance');
```

---

## MEDIUM Issues

### M1. `EncryptsAttributes::attributeIsEncrypted()` — Silent Failure Masks Errors

**Location:** `app/Traits/EncryptsAttributes.php:86-89`

Catching `\Exception` broadly means any decryption failure is treated as "this attribute is NOT encrypted." The ciphertext could then be serialized to JSON responses, logs, or views.

---

### M2. `NotificationResource` Exposes Raw `data` Payload

**Location:** `app/Http/Resources/NotificationResource.php:14`

```php
'data' => $this->data,  // Passed through unfiltered
```

---

### M3. Missing `NoHtml` for Bulk Product Names/Descriptions

**Location:** `app/Http/Requests/StoreBulkProductRequest.php:18,20`

Unlike `StoreShopSettingsRequest` which uses the `NoHtml` custom rule, the bulk product request does not prevent HTML injection.

---

### M4. `UpdateSupplierDocumentRequest` — Unrestricted `document_type`

**Location:** `app/Http/Requests/UpdateSupplierDocumentRequest.php:19`

```php
'document_type' => 'required|string',  // No whitelist
```

---

### M5. `StoreRiskWeightRequest` — No Weight Sum Validation

**Location:** `app/Http/Requests/StoreRiskWeightRequest.php:20-25`

The five main weights are individually bounded 0-100 but are not validated to sum to 100.

---

## LOW Issues

### L1. `StoreShopSettingsRequest` — Slider URLs Not Sanitized

**Location:** `app/Http/Requests/StoreShopSettingsRequest.php:20-21`

While `name` and `address` use `new NoHtml`, the `sliders.*` values do not.

---

### L2. `StoreBrandRequest` — `order_level` Missing Integer Constraint

**Location:** `app/Http/Requests/StoreBrandRequest.php:19`

```php
'order_level' => ['required', 'numeric'],  // Should be integer
```

---

## Files With No Issues Found

| File | Notes |
|------|-------|
| `StoreInvestmentPoolRequest.php` | Valid dates, proper numeric constraints |
| `UpdateInvestmentPoolRequest.php` | `sometimes` modifiers correct for partial updates |
| `StorePermissionRequest.php` | Simple name field with `unique` |
| `StoreRoleRequest.php` | Same pattern as permissions |
| `UpdateCategoryRequest.php` | Proper `Rule::unique()->ignore()`, `exists` check for `parent_id` |
| `UpdateCreditLimitRequest.php` | **Only FormRequest using `hasSensitivePermission()`** — exemplary |
| `UpdatePermissionRequest.php` | Proper unique-ignore pattern |
| `UpdateRoleRequest.php` | Same as above |
| `UpgradeCustomerPackageRequest.php` | Proper `exists:packages,id` |
| `WithApprovalContext.php` | Clean trait design; `finally` block ensures cleanup on exceptions |
| `WithApprovalContextTest.php` | Well-structured tests; tearDown resets state |
