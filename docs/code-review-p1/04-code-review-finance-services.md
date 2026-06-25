# Code Review — Finance & Approval Services

**Branch:** `feature/core-p1`  
**Scope:** 7 files (2 new, 5 modified) | **Diff:** +212 / −82 lines  
**Date:** 2026-06-24

---

## Summary

| # | Severity | Area | File |
|---|----------|------|------|
| C-1 | CRITICAL | `save()` outside transaction after rollback | ProcessScheduledPayments |
| C-2 | CRITICAL | Double-spend: no Payment lockForUpdate | ProcessScheduledPayments |
| C-3 | CRITICAL | Float arithmetic for SAR amounts | All financial services |
| C-4 | CRITICAL | No DB transaction in 3 SettlementService methods | SettlementService |
| H-1 | HIGH | Hardcoded account IDs, silent failure | SettlementService |
| H-2 | HIGH | Guard fires Log::critical on every scheduled-payment save | ProcessScheduledPayments |
| H-3 | HIGH | No audit log for declined payments | ProcessScheduledPayments |
| H-4 | HIGH | Null pointer risk on account 5000 | ExpenseService |
| M-1 | MEDIUM | No state-transition validation | RefundApprovalService |
| M-2 | MEDIUM | No Gate/Policy authorization | RefundApproval + Settlement |
| M-3 | MEDIUM | Unescaped CSV for bank transfers | BatchPayoutReport |
| M-4 | MEDIUM | Hardcoded Tuesday payout day | SupplierFinanceService |
| M-5 | MEDIUM | Dead code + unused import | ExpenseService |
| M-6 | MEDIUM | batchApprove not atomic | SettlementService |
| M-7 | MEDIUM | Static state persistence risk | WithApprovalContext |
| M-8 | MEDIUM | Missing guard on 3 financial models | Settlement/Refund/SupplierPayout |

---

## CRITICAL Issues

### C-1: ProcessScheduledPayments — `save()` Outside DB Transaction After Rollback

**Location:** `app/Console/Commands/ProcessScheduledPayments.php:218-223`

```php
} catch (\Throwable $e) {
    DB::rollBack();
    $fresh->payment_status = 'failed';
    $fresh->failure_reason = 'Error: '.$e->getMessage();
    $fresh->save();   // ← OUTSIDE transaction — stale $fresh, no atomicity
}
```

After `rollBack()`, the `$fresh` instance holds pre-transaction state plus in-memory mutations. If the connection was dropped, `save()` may silently succeed writing to a stale row — or fail without alerting.

**Fix:** Call `$fresh->save()` *before* `rollBack()`, so the status update commits atomically:
```php
} catch (\Throwable $e) {
    $fresh->payment_status = 'failed';
    $fresh->failure_reason = 'Error: '.$e->getMessage();
    $fresh->save();    // save WITHIN transaction
    DB::commit();       // then commit the status update
    Log::error(...);
}
```

---

### C-2: ProcessScheduledPayments — Double-Spend Window

**Location:** `app/Console/Commands/ProcessScheduledPayments.php:73-163`

The duplicate-protection check queries Payment records **without** `lockForUpdate()`. Two concurrent processes can:
1. Both pass `$existingPayment === false`
2. Both proceed to create Payment records
3. Both call ClickPay — charging the customer **twice**

**Fix:** Use `DB::transaction` with a unique constraint on `(order_id, schedule_payment_id)` in the payments table, OR use a Redis atomic lock keyed by `schedule_payment_id`:
```php
$paymentLock = Cache::lock("payment:{$fresh->id}", 30);
if (! $paymentLock->get()) { DB::commit(); continue; }
// ... process payment ...
$paymentLock->release();
```

---

### C-3: Float Arithmetic for SAR Amounts

**Files:** `ProcessScheduledPayments.php:130`, `SettlementService.php:120-121`, `SupplierFinanceService.php:42,50-52,76,159-160,179-180`

```php
(float) $fresh->instalment_amount       // 99.99 → 99.98999999999999 in memory
(float) $order->grand_total - (float) ($order->commission_amount ?? 0)
```

PHP floats are IEEE 754 binary — SAR amounts with 2 decimal places (halalas) cannot be represented precisely. For high-volume batch settlements, error accumulates.

**Fix:** Use `bcmath` for all financial arithmetic, or represent amounts internally as integer halalas:
```php
$amountHalalas = (int) round($amount * 100);
```

---

### C-4: SettlementService — No DB Transactions

**Location:** `app/Services/Finance/SettlementService.php`

- **`approveSettlement()`** (line 209-221): 3 property updates + `save()` — no `DB::transaction()`
- **`markSettlementAsPaid()`** (line 267-282): Saves status → calls `createPayoutForSettlement()` which creates 4+ records. If `createPayoutForSettlement` fails, the settlement is marked 'paid' with no payout — money vanishes from the ledger
- **`cancelSettlement()`** (line 356-373): Mutates settlement + iterates orders setting `settlement_id = null` — no transaction

**Fix:** Wrap all three methods in `DB::transaction()`.

---

## HIGH Issues

### H-1: SettlementService — Hardcoded Account IDs with No Fallback

**Location:** `app/Services/Finance/SettlementService.php:322,339`

```php
$supplierAccount = FAccounts::where('id', '2400')->first();  // hardcoded
$bankAccount = FAccounts::where('id', '1201')->first();       // hardcoded
```

If accounts 2400/1201 are deleted/renumbered, the method silently skips creating the double-entry — creating unbalanced journal entries (SAMA compliance violation).

**Fix:** Use `findOrFail()` and store account IDs as settings in the `settings` table.

---

### H-2: ProcessScheduledPayments — Guard Fires Log::critical() on Every Save

**Location:** `app/Console/Commands/ProcessScheduledPayments.php:60-224`

`SchedulePayment` has the `WithApprovalContext::isInApprovalContext()` guard. The command never enters approval context, so **every single save** fires `Log::critical()` — flooding logs with false-positive critical alerts.

**Fix:** Wrap the processing loop in `WithApprovalContext::runInApprovalContext()`.

---

### H-3: ProcessScheduledPayments — No Audit Log for Declined Payments

**Location:** `app/Console/Commands/ProcessScheduledPayments.php:183-190`

Audit trail is created only for successful payments. Declined payments have no audit logging, despite being critical regulatory events (SAMA requires tracking all payment attempts).

**Fix:** Add audit logging in the decline path.

---

### H-4: ExpenseService — Null Pointer Risk on Account 5000

**Location:** `app/Services/Finance/ExpenseService.php:54`

```php
$expensAccount = FAccounts::where('id', 5000)->first();
// No null check before accessing $expensAccount->id on line 58
```

**Fix:** Use `findOrFail()` or throw a descriptive exception.

---

## MEDIUM Issues

### M-1: RefundApprovalService — No State-Transition Validation

**Location:** `app/Services/Finance/RefundApprovalService.php:21-31`

`approve()` and `reject()` directly set `refund_status` without checking the current state. A refund could be approved → approved again, or approved → rejected (contradictory).

**Fix:** Add state guard:
```php
if ($refund->refund_status !== 'pending') {
    throw new \InvalidArgumentException("Refund #{$refund->id} is already {$refund->refund_status}");
}
```

### M-2: No Gate/Policy Authorization

**Location:** `RefundApprovalService.php:19`, `SettlementService.php:209,267,357`

Both services accept a `User $actor` parameter but never call `Gate::authorize()` or `$actor->can()`.

### M-3: BatchPayoutReport — Unescaped CSV Data

**Location:** `app/Services/Finance/BatchPayoutReport.php:85`

Supplier names/business names could contain commas or quotes, corrupting the batch file fed to a bank's transfer system. Use `fputcsv()` for proper quoting.

### M-4: SupplierFinanceService — Hardcoded Payout Day

**Location:** `app/Services/Finance/SupplierFinanceService.php:19`

```php
// For now we will hard code the payout date to be every Tuesday,
// until we have a proper setting table for this feature.
```

Violates the CLAUDE.md directive to use the `settings` table for business config.

### M-5: ExpenseService — Dead Code

**Location:** `app/Services/Finance/ExpenseService.php`

- Unused import: `use PhpParser\Node\Expr\Cast\Double;` (line 13)
- Dead assignment: `$amount = 0;` immediately overwritten by `$amount = $expenseSetting->amount;`

### M-6: SettlementService — `batchApprove` Not Atomic

**Location:** `app/Services/Finance/SettlementService.php:223-262`

Each `approveSettlement()` call commits independently. If the 3rd of 10 approvals fails, the first 2 are already committed with no rollback.

### M-7: WithApprovalContext — Static State in Long-Running Processes

**Location:** `app/Traits/WithApprovalContext.php`

The `private static bool $approvalContextActive` persists across requests in Laravel Octane or queue workers. If one request crashes before `exitApprovalContext()`, all subsequent requests run with the guard bypassed.

### M-8: Missing Guard on 3 Financial Models

Models `Settlement`, `RefundRequest`, `SupplierPayout` lack the `WithApprovalContext` guard.

---

## What's Good

| Item | Detail |
|------|--------|
| `RefundApprovalService` | Clean `declare(strict_types=1)`, transaction-wrapped, full audit trail for approve + reject |
| `WithApprovalContext` | Well-designed trait with `finally` cleanup, 8 passing unit tests, used in 5 financial models |
| `SupplierFinanceService` | Audit logging added to `getUpcomingPayout` and `getLedger` |
| `ExpenseService` | New `AuditTrailService` DI, `logCreated()` call added after commit |
| Pint | All 7 files pass PSR-12 |

---

## Verdict: **BLOCK** — 4 CRITICAL issues

The most concerning finding is **C-2** (double-spend window), which could result in customers being charged twice for the same instalment — a direct regulatory liability under SAMA CSF 4.2 (transaction integrity).
