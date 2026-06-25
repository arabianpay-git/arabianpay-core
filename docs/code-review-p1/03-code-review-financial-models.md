# Code Review — Financial Model Guards

**Branch:** `feature/core-p1`  
**Scope:** 14 financial models (FEntry, FTransaction, Payment, SchedulePayment, Transaction, Wallet, Order, Checkout, Claim, RefundRequest, Settlement, SupplierPayout, InvestmentPool, RiskManagement)  
**Date:** 2026-06-24

---

## Summary

| Category | Count |
|----------|-------|
| **CRITICAL** | 3 |
| **HIGH** | 6 |
| **MEDIUM** | 12 |
| **LOW** | 8 |

---

## CRITICAL Issues

### C1. RiskManagement `$fillable` Includes Derived/Sensitive Fields

**Location:** `app/Models/RiskManagement.php:11-39`

The following fields are in `$fillable` but should be computed or set by designated services only:

- `credit_score` — computed by `CreditAssessmentService`
- `risk_score` — derived from credit score + behavioral data
- `risk_level` — derived from `risk_score`
- `manual_review_required` — derived boolean
- `flagged_reasons` — derived/computed
- `simah_api_response` — external API response, should never be user-supplied
- `external_credit_data` — external data
- `kyc_status` — verified by KYC process
- `compliance_status` — compliance review result
- `account_activity_score` — derived from transaction history

**Remediation:** Remove all derived/external fields from `$fillable`. Keep only user-supplied identity fields.

---

### C2. Wallet `balance_after` Mass-Assignable

**Location:** `app/Models/Wallet.php:21`

`balance_after` is a computed column that tracks wallet balance after a transaction. It should NEVER be directly settable — it destroys the audit trail and enables balance manipulation.

**Remediation:** Immediately remove `balance_after` from `$fillable`.

---

### C3. Transaction `getCashFlowData()` — Undefined Array Key

**Location:** `app/Models/Transaction.php:172-178`

`getCashFlowData()` accesses `$base['canceled']` but `getLoanFlowData()` returns `['months', 'disbursed', 'repaid']` — there is NO `'canceled'` key. The `?? []` fallback silently returns empty outflows, masking the bug.

**Remediation:** Either add `canceled` aggregation to `getLoanFlowData()`, or use a different data source for outflows.

---

## HIGH Issues

### H1. SchedulePayment — Multiple Derived/Computed Fields Mass-Assignable

**Location:** `app/Models/SchedulePayment.php:17-41`

`is_late` (derived from `due_date` vs now), `late_days` (computed), and 8 financial amount fields are all mass-assignable.

**Remediation:** Remove `is_late`, `late_days` from `$fillable`. Add `$casts` for financial fields.

---

### H2. SchedulePayment `paid_at` Mass-Assignable

**Location:** `app/Models/SchedulePayment.php:40`

`paid_at` should be set ONLY by a dedicated method that also updates `payment_status` atomically within a transaction.

**Remediation:** Remove `paid_at` from `$fillable`.

---

### H3. Transaction — Loan Amounts and Statuses in `$fillable`

**Location:** `app/Models/Transaction.php:18-41`

`loan_amount`, `collected`, `retrieved`, `canceled`, `credit_limit_at_time`, `remaining_credit_limit`, `payment_status`, `settlement_status`, `general_status` are all mass-assignable.

**Remediation:** Remove status fields from `$fillable`. Add `$casts` for financial fields. Guard `loan_amount` after initial disbursement.

---

### H4. Order `payment_status`, `delivery_status`, `general_status` Mass-Assignable

**Location:** `app/Models/Order.php:35,43,44`

These statuses control the entire order lifecycle. Arbitrary mass assignment bypasses state-machine logic.

**Remediation:** Remove status fields from `$fillable`. Add `transitionStatus(string $from, string $to)` method.

---

### H5. Order Encrypted Financial Fields Used in SUM() Queries

**Location:** `app/Models/Order.php:65-67`

`grand_total` and `shipping_cost` are in `$encryptableAttributes`, but `getRevenueStreams()` queries them via `SUM()` on the database. **This will fail silently** because encrypted values are gibberish to MySQL aggregation functions.

**Remediation:** Revenue reporting data will be incorrect. Either remove these fields from `$encryptableAttributes` (they are not PII), or maintain a `grand_total_raw` unencrypted column for reporting.

---

### H6. RiskManagement — PII Fields Unencrypted

**Location:** `app/Models/RiskManagement.php:16-17`

`contact_email` and `contact_phone` are PII but stored in plaintext. Unlike `User.email` (which uses encryption).

**Remediation:** Add `EncryptsAttributes` trait and encrypt these fields.

---

## Cross-Cutting Issues

### CC-1: `WithApprovalContext` is Detection-Only

The guard logs + audits but does NOT prevent direct `update()` calls. An attacker or bug that directly calls `$model->update(['amount' => 0])` will succeed — it will just be logged.

**Recommendation:** Consider a P2 hard guard implementation (throw `RuntimeException`). Ensure real-time alerting on `Log::critical()` events.

### CC-2: `WithApprovalContext` Only Covers `updating`

A direct `$model->create([...])` or `$model->delete()` bypasses the guard entirely.

**Recommendation:** Add listeners for `creating`, `deleting`, and `saving` events.

### CC-3: Inconsistent Coverage

The following financial models are NOT protected but contain equally sensitive data:
- `Wallet`, `Settlement`, `SupplierPayout`, `RefundRequest`, `InvestmentPool`, `RiskManagement`

## MEDIUM Issues (Summary)

| # | Finding | Models Affected |
|---|---------|-----------------|
| M1 | Missing `$casts` for decimal/date columns | FEntry, FTransaction, Payment, SchedulePayment, Transaction, Wallet, Order, RiskManagement |
| M2 | `status` fields mass-assignable | FEntry, FTransaction, Payment, Wallet, Claim, Settlement, SupplierPayout, InvestmentPool |
| M3 | `SupplierPayout::amount` cast as `float` (should be `decimal:2`) | SupplierPayout |
| M4 | `InvestmentPool` computed metrics (`total_disbursed`, `collection_rate`, etc.) in `$fillable` | InvestmentPool |
| M5 | `Claim::claim_status` and `priority` in `$fillable` bypass state-machine methods | Claim |
| M6 | `Order::commission_amount`, `commission_percent`, `grand_total` mass-assignable after creation | Order |
| M7 | `Checkout::pool_id` mass-assignable after creation | Checkout |
| M8 | No return type hints on relationship methods | All 14 models |
| M9 | No `declare(strict_types=1)` on financial models | All 14 models |
| M10 | `Payment::invoice_number` potentially contains PII but is not encrypted | Payment |
| M11 | `RiskManagement::getAverageCreditScores()` swallows all exceptions silently | RiskManagement |
| M12 | Unused variable in `Transaction::getLoanFlowData()` | Transaction |

---

## Approval Verdict: **BLOCK**

Minimum unblocking steps:
1. Fix `RiskManagement::$fillable` — remove all derived fields
2. Remove `balance_after` from `Wallet::$fillable`
3. Fix `Transaction::getCashFlowData()` undefined `canceled` key
4. Remove `paid_at` from `SchedulePayment::$fillable`
5. Add `$casts` for decimal fields to Payment, SchedulePayment, Transaction, FEntry, FTransaction
6. Add encrypted fields to `RiskManagement` for `contact_email` and `contact_phone`
