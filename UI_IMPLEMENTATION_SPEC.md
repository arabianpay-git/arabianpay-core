# ArabianPay UI Implementation Spec

**Date:** 2026-04-07
**Verified against:** Branch `arabianpay-br`, 659 routes, 149 permissions, 165 tests passing

---

## 1. Verdict

| Dimension | Status |
|-----------|--------|
| **UI architecture** | READY — component library built, navigation mapped |
| **Admin panel implementation** | READY — all 563 admin routes exist, permissions enforced |
| **Merchant panel implementation** | BLOCKED — no merchant-facing routes/controllers exist |
| **Supplier panel implementation** | BLOCKED — no supplier-facing routes/controllers exist |

**The system has ONE panel today: the Admin panel.** All merchant/supplier data is managed BY admins through admin routes. There are no self-service portals.

---

## 2. Permission Convention

All permissions use `{resource}.{action}` format. Canonical list (149 total, verified from DB):

```
settlement.view | settlement.create | settlement.approve | settlement.cancel | settlement.pay | settlement.export
payout.view | payout.process | payout.complete
payment.view | payment.process
schedule_payment.view | schedule_payment.update | schedule_payment.pay-now
credit-limit.view | credit-limit.create | credit-limit.update
order.view | order.manage | order.accept | order.reject
refund.view | refund.manage | refund.approve
financial.view | financial.manage | financial.export
risk.view | risk.manage | risk.export | risk.update-score
risk-weight.view | risk-weight.manage
collection.view | collection.manage | collection.allocate
compliance.view | compliance.manage
audit.view | audit.export
report.view | report.export
sensitive-data.access | sensitive-data.approve
settings.manage | settings.financial | settings.security
merchant.view | merchant.update
customer.view | customer.manage | customer.update-status
supplier.view | supplier.manage | supplier.update-status
```

---

## 3. Admin Panel — Screen Spec

### 3.1 Dashboard

| Field | Value |
|-------|-------|
| Screen name | Admin Dashboard |
| User type | admin, employee |
| Technology | Blade + existing ApexCharts |
| Route | `GET /admin/dashboard` (name: `dashboard`) |
| Permission | `dashboard.view` |
| Backend status | **EXISTS** — `DashboardController@dashboard` |

**Components used:**
| Component | Backend dependency | Status |
|-----------|-------------------|--------|
| `<x-fintech.kpi-card>` | Settlement/Order/SchedulePayment models | READY |
| `<x-fintech.alert-banner>` | SchedulePayment overdue query, DataSubjectRequest overdue scope | READY |
| Financial KPIs partial | Settlement::count(), Transaction::sum() | READY |

**Data fields:** loanData, financialData, riskData, operationalData, categorySales, categoryStock (all from existing controller)

**Actions:** Date range filter (existing), Request Sensitive Data Access (existing modal)

---

### 3.2 Settlements

| Field | Value |
|-------|-------|
| Screen name | Settlement Management |
| User type | admin, finance |
| Technology | Blade |

**Index screen:**
| Route | Method | Name | Permission | Status |
|-------|--------|------|------------|--------|
| `GET /admin/settlements` | index | `settlements.index` | `settlement.view` | EXISTS |
| `GET /admin/settlements/generate/period` | generateForFinishedPeriod | `settlements.generate` | `settlement.create` | EXISTS |
| `POST /admin/settlements/batch-approve` | batchApprove | `settlements.batch-approve` | `settlement.approve` | EXISTS |
| `POST /admin/settlements/batch-payout` | batchPayout | `settlements.batch-payout` | `settlement.pay` | EXISTS |
| `POST /admin/settlements/batch-cancel` | batchCancel | `settlements.batch-cancel` | `settlement.cancel` | EXISTS |
| `POST /admin/settlements/generate-report` | generateReport | `settlements.generate-report` | `settlement.export` | EXISTS |
| `POST /admin/settlements/bank-transfer-file` | generateBankTransferFile | `settlements.bank-transfer-file` | `settlement.export` | EXISTS |

**Detail screen:**
| Route | Method | Name | Permission | Status |
|-------|--------|------|------------|--------|
| `GET /admin/settlements/{settlement}` | show | `settlements.show` | `settlement.view` | EXISTS |
| `POST /admin/settlements/{settlement}/approve` | approve | `settlements.approve` | `settlement.approve` | EXISTS |
| `POST /admin/settlements/{settlement}/pay` | markAsPaid | `settlements.pay` | `settlement.pay` | EXISTS |
| `POST /admin/settlements/{settlement}/cancel` | cancel | `settlements.cancel` | `settlement.cancel` | EXISTS |

**Components used:**
| Component | Backend dependency | Status |
|-----------|-------------------|--------|
| `<x-fintech.status-badge>` | `SettlementStatus` enum | READY |
| `<x-fintech.money>` | `Money::normalize()` | READY |
| `<x-fintech.lifecycle-timeline>` | `created_by`, `approved_by`, `paid_by` fields | READY |
| `<x-fintech.maker-checker-info>` | Settlement `creator`, `approver`, `payer` relations | READY |
| `<x-fintech.approval-modal>` | SettlementPolicy maker-checker | READY |
| `@can('settlement.approve')` | Spatie permission middleware | READY |

**Maker-checker enforcement:**
- Policy layer: `SettlementPolicy::approve()` / `SettlementPolicy::pay()`
- Service layer: `SettlementService::approveSettlement()` / `markSettlementAsPaid()`
- UI layer: `@can` guards + `<x-fintech.approval-modal>` warnings

---

### 3.3 Payouts

| Field | Value |
|-------|-------|
| Screen name | Supplier Payouts Portal |
| User type | admin, finance |
| Technology | Blade |

| Route | Permission | Status |
|-------|------------|--------|
| `GET /admin/payouts` | `payout.view` | EXISTS |
| `POST /admin/payouts` | `payout.process` | EXISTS |
| `POST /admin/payouts/{payout}/complete` | `payout.complete` | EXISTS |
| `POST /admin/payouts/{payout}/fail` | `payout.complete` | EXISTS |
| `GET /admin/payouts/{payout}/details-modal-data` | `payout.view` | EXISTS |

**Components:** `<x-fintech.money>`, `<x-fintech.status-badge>` (PayoutStatus enum), `@can('payout.process')`

---

### 3.4 Financial Accounting

| Route | Permission | Status |
|-------|------------|--------|
| `GET /admin/financial` | `financial.view` | EXISTS |
| `GET /admin/financial/trial-balance` | `financial.view` | EXISTS |
| `GET /admin/financial/trial-balance/export` | `financial.export` | EXISTS |
| `GET /admin/financial/accounts` (CRUD) | `financial.view` | EXISTS |
| `GET /admin/financial/transactions` (CRUD) | `financial.view` | EXISTS |

---

### 3.5 Schedule Payments

| Route | Permission | Status |
|-------|------------|--------|
| `GET /admin/schedule-payments` | `schedule_payment.view` | EXISTS |
| `GET /admin/schedule-payments/{status}` | `schedule_payment.view` | EXISTS |
| `PUT /admin/schedule-payments/{id}` | `schedule_payment.update` | EXISTS |
| `POST /admin/schedule-payments/pay-now` | `schedule_payment.pay-now` | EXISTS |

**Components:** `<x-fintech.status-badge>` (SchedulePaymentStatus enum), `<x-fintech.money>`

---

### 3.6 Credit Management

| Route | Permission | Status |
|-------|------------|--------|
| `GET /admin/credit/credit-profiles` | `credit-limit.view` | EXISTS |
| `GET /admin/credit/credit-limit` | `credit-limit.view` | EXISTS |
| `GET /admin/credit/repayment-schedule` | `credit-limit.view` | EXISTS |
| `GET /admin/credit/export/csv` | `report.export` | EXISTS |

**Components:** `<x-fintech.credit-gauge>`, `<x-fintech.money>`

---

### 3.7 Risk & Compliance

| Route | Permission | Status |
|-------|------------|--------|
| `GET /admin/risk/dashboard` | `risk.view` | EXISTS |
| `GET /admin/risk/merchant-score` | `risk.view` | EXISTS |
| `POST /admin/risk/score-update` | `risk.update-score` | EXISTS |
| `GET /admin/risk/export/csv` | `risk.export` | EXISTS |
| `POST /admin/risk-weights` | `risk-weight.manage` | EXISTS |
| `GET /admin/collections` | `collection.view` | EXISTS |

---

### 3.8 Orders

| Route | Permission | Status |
|-------|------------|--------|
| `GET /admin/orders` | `order.view` | EXISTS |
| `POST /admin/orders/accept` | `order.accept` | EXISTS |
| `POST /admin/orders/reject` | `order.reject` | EXISTS |

---

### 3.9 Refund Requests

| Route | Permission | Status |
|-------|------------|--------|
| `GET /admin/refund-requests` | `refund.view` | EXISTS |
| `PATCH /admin/refund-requests/{id}/status` | `refund.manage` | EXISTS |

---

### 3.10 Audit & Privacy

| Route | Permission | Status |
|-------|------------|--------|
| `GET /admin/audit/trails` | `audit.view` | EXISTS |
| `GET /admin/audit/logs` | `audit.view` | EXISTS |
| `GET /admin/logs/export` | `audit.export` | EXISTS |
| `GET /admin/approvals` | `sensitive-data.access` | EXISTS |
| `POST /admin/approvals/{approval}/decision` | `sensitive-data.approve` | EXISTS |

**Components:** `<x-fintech.audit-viewer>`

---

### 3.11 Settings

| Route | Permission | Status |
|-------|------------|--------|
| `GET /admin/settings` | `settings.manage` | EXISTS |
| All setting sub-routes (60+) | `settings.manage` | EXISTS |

---

## 4. Merchant Panel — Contract Spec

**STATUS: NOT IMPLEMENTED. All routes below must be CREATED.**

There are ZERO merchant-facing routes today. Merchants are managed BY admins via `/admin/` routes. For a self-service merchant portal, the following backend work is required:

### Required backend work

| # | Item | Type | Priority |
|---|------|------|----------|
| 1 | `routes/merchant.php` | New route file | P0 |
| 2 | `MerchantDashboardController` | New controller | P0 |
| 3 | `MerchantOrderController` | New controller | P0 |
| 4 | `MerchantPaymentController` | New controller | P0 |
| 5 | `MerchantProfileController` | New controller | P1 |
| 6 | `MerchantStatementController` | New controller | P1 |
| 7 | Merchant auth middleware (restrict to `user_type=merchant`) | New middleware | P0 |
| 8 | Merchant layout (`layouts/merchant.blade.php`) | New layout | P0 |
| 9 | API endpoints for Flutter mobile | New API routes | P1 |

### Proposed routes (to be created)

```
GET  /merchant/dashboard        → MerchantDashboardController@index
GET  /merchant/orders           → MerchantOrderController@index
GET  /merchant/orders/{order}   → MerchantOrderController@show
GET  /merchant/credit           → MerchantCreditController@index
GET  /merchant/payments         → MerchantPaymentController@index
POST /merchant/payments/pay-now → MerchantPaymentController@payNow
GET  /merchant/statements       → MerchantStatementController@index
GET  /merchant/profile          → MerchantProfileController@index
```

### Permissions needed
Merchant users should NOT use Spatie permissions. Access is controlled by `user_type = 'merchant'` middleware + OrderPolicy/PaymentPolicy object-level scoping (already implemented — merchants see only own records via `seller_id`).

---

## 5. Supplier Panel — Contract Spec

**STATUS: NOT IMPLEMENTED. All routes below must be CREATED.**

Same situation as merchant. Suppliers are managed by admins.

### Required backend work

| # | Item | Type | Priority |
|---|------|------|----------|
| 1 | `routes/supplier.php` | New route file | P0 |
| 2 | `SupplierDashboardController` | New controller | P0 |
| 3 | `SupplierOrderController` | New controller | P0 |
| 4 | `SupplierSettlementController` (read-only) | New controller | P0 |
| 5 | `SupplierPayoutController` (read-only) | New controller | P0 |
| 6 | `SupplierProductController` | New controller | P1 |
| 7 | Supplier auth middleware | New middleware | P0 |
| 8 | Supplier layout (`layouts/supplier.blade.php`) | New layout | P0 |

### Proposed routes (to be created)

```
GET  /supplier/dashboard              → SupplierDashboardController@index
GET  /supplier/orders                 → SupplierOrderController@index
GET  /supplier/orders/{order}         → SupplierOrderController@show
POST /supplier/orders/{order}/accept  → SupplierOrderController@accept
POST /supplier/orders/{order}/reject  → SupplierOrderController@reject
GET  /supplier/settlements            → SupplierSettlementController@index
GET  /supplier/settlements/{id}       → SupplierSettlementController@show
GET  /supplier/payouts                → SupplierPayoutController@index
GET  /supplier/products               → SupplierProductController@index
GET  /supplier/reports                → SupplierReportController@index
```

### Security constraint
Suppliers must ONLY see their own data. The existing policies already enforce this:
- `OrderPolicy::view()` — checks `$order->seller_id === $user->id`
- `SettlementPolicy::view()` — admin/employee only (supplier controller must add own scoping)
- `RefundRequestPolicy::view()` — checks `$refundRequest->seller_id === $user->id`

---

## 6. Component Backend Dependencies

| Component | Dependencies | Status |
|-----------|-------------|--------|
| `<x-fintech.money>` | `App\Helpers\Money::normalize()` | READY |
| `<x-fintech.status-badge>` | All 9 BackedEnum classes in `App\Enums\` | READY |
| `<x-fintech.kpi-card>` | None (pure display) | READY |
| `<x-fintech.lifecycle-timeline>` | Settlement `creator`, `approver`, `payer` relations | READY |
| `<x-fintech.approval-modal>` | `Money::compare()` for high-value detection | READY |
| `<x-fintech.alert-banner>` | None (pure display) | READY |
| `<x-fintech.credit-gauge>` | `Money::normalize()`, `Money::subtract()` | READY |
| `<x-fintech.audit-viewer>` | `AuditTrail` model query by entity | READY |
| `<x-fintech.maker-checker-info>` | Settlement `creator`, `approver`, `payer` relations | READY |

---

## 7. Implementation Priorities

### Phase A: Admin Panel Enhancement (READY NOW)

Use existing routes and controllers. Apply fintech components to existing Blade views.

| Screen | Effort | Blocked? |
|--------|--------|----------|
| Dashboard KPI strip | Done | No |
| Settlement index (components) | Done (partial) | No |
| Settlement show (maker-checker, money, modal) | Done | No |
| Payout index (money, status badges) | Remaining | No |
| Schedule payments (status badges, money) | Remaining | No |
| Credit management (credit gauge) | Remaining | No |
| Refund requests (status badges) | Remaining | No |
| Audit viewer integration | Remaining | No |

### Phase B: Merchant Portal (BLOCKED — needs backend)

| Dependency | Type | Status |
|-----------|------|--------|
| Merchant routes | Route file | NOT CREATED |
| Merchant controllers | Controllers | NOT CREATED |
| Merchant middleware | Middleware | NOT CREATED |
| Merchant layout | Blade layout | NOT CREATED |
| Object-level scoping | Policies | READY (OrderPolicy, PaymentPolicy) |

### Phase C: Supplier Portal (BLOCKED — needs backend)

| Dependency | Type | Status |
|-----------|------|--------|
| Supplier routes | Route file | NOT CREATED |
| Supplier controllers | Controllers | NOT CREATED |
| Supplier middleware | Middleware | NOT CREATED |
| Supplier layout | Blade layout | NOT CREATED |
| Object-level scoping | Policies | PARTIALLY READY |

### Phase D: Mobile (Flutter) API

| Dependency | Type | Status |
|-----------|------|--------|
| Sanctum token auth | Package | INSTALLED |
| API routes for merchant/supplier | Routes | NOT CREATED |
| JSON response controllers | Controllers | NOT CREATED |

---

## 8. Insecure Endpoints — DO NOT USE

| Route | Why | Status |
|-------|-----|--------|
| `/api/singleview/*` | Financial service test routes | LOCAL-ONLY gated |
| `/api/nafith/*` | SANAD test routes | LOCAL-ONLY gated |
| `/api/test-silver-report` | SIMAH test route | LOCAL-ONLY gated |
| `/api/test-consumer-score` | SIMAH test route | LOCAL-ONLY gated |
| `/api-tester` | SSRF proxy (deleted) | Returns 404 |
| `/dev-login` | Dev auth bypass | LOCAL-ONLY gated |

These routes MUST NEVER be used as data sources for UI screens.
