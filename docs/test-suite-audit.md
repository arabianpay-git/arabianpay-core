# Test Suite Audit Report — ArabianPay Admin

**Date:** 2026-06-25  
**Score:** 2/10

---

## I. Executive Summary

The test suite has severe gaps. Out of 25 test files, only **3** test project-custom code. The remaining 22 are Laravel/Jetstream scaffolding tests, of which **7 are broken** (wrong inputs, testing disabled features), **3 are correctly skipped**, and **12 are valid but test only framework-provided features**. The entire BNPL business domain — payments, orders, settlements, credit, collections, integrations — has **zero coverage**.

---

## II. Existing Tests Review

### Custom Tests — Keep

| Test File | Tests | Action |
|---|---|---|
| `tests/Feature/AuditLogMiddlewareTest.php` | PII masking in audit logs, exclusion paths (health-check, debugbar, telescope), method/path capture, POST auditing | **Keep** |
| `tests/Unit/CspNonceTest.php` | CSP nonce stability, randomness, singleton binding, helper function | **Keep** |
| `tests/Unit/WithApprovalContextTest.php` | Approval context activation/deactivation, exception safety, return-value passthrough, static sharing | **Keep** |

### Jetstream Scaffolding — Delete (Feature Disabled)

| Test File | Reason |
|---|---|
| `tests/Feature/CreateTeamTest.php` | Teams feature disabled in `config/jetstream.php` |
| `tests/Feature/DeleteTeamTest.php` | Same |
| `tests/Feature/UpdateTeamNameTest.php` | Same |
| `tests/Feature/RemoveTeamMemberTest.php` | Same |
| `tests/Feature/LeaveTeamTest.php` | Same |
| `tests/Feature/UpdateTeamMemberRoleTest.php` | Same |
| `tests/Feature/InviteTeamMemberTest.php` | Same (correctly skipped but feature will never be enabled) |
| `tests/Feature/CreateApiTokenTest.php` | API tokens feature disabled |
| `tests/Feature/DeleteApiTokenTest.php` | Same |
| `tests/Feature/ApiTokenPermissionsTest.php` | Same |

### Jetstream Scaffolding — Delete (Broken or Trivial)

| Test File | Reason |
|---|---|
| `tests/Feature/PasswordResetTest.php` | Password broker uses `User::where('email', ...)` — encrypted at rest, so the ResetPassword notification is never sent. Feature is fundamentally broken with encrypted emails. |
| `tests/Feature/ExampleTest.php` | Trivial — asserts `GET /` returns 200 |
| `tests/Unit/ExampleTest.php` | Trivial — asserts `assertTrue(true)` |

### Jetstream Scaffolding — Rewrite

| Test File | Reason |
|---|---|
| `tests/Feature/RegistrationTest.php` | Test sends `name` field, but `CreateNewUser` action validates `first_name`, `last_name`. Missing 5 required fields (`business_name`, `phone_number`, `country_id`, `state_id`, `city_id`). POST will always fail validation. |

### Jetstream Scaffolding — Keep (Valid but Low-Value for This App)

| Test File | Notes |
|---|---|
| `tests/Feature/AuthenticationTest.php` | Tests standard Fortify login, not the primary SSO flow. Marginally useful. |
| `tests/Feature/PasswordConfirmationTest.php` | Valid — custom `confirmPasswordsUsing()` tested. |
| `tests/Feature/EmailVerificationTest.php` | Technically valid — signed URLs avoid encrypted lookup. Architecturally odd for an employee-only admin panel. |
| `tests/Feature/UpdatePasswordTest.php` | Valid — Livewire component. |
| `tests/Feature/ProfileInformationTest.php` | Valid — verify it uses `first_name` not `name`. |
| `tests/Feature/TwoFactorAuthenticationSettingsTest.php` | Valid — critical security feature. |
| `tests/Feature/BrowserSessionsTest.php` | Valid. |
| `tests/Feature/DeleteAccountTest.php` | Valid. |

---

## III. High-Risk Untested Areas

| Area | Risk | What's Missing |
|---|---|---|
| **Payment Processing** | Critical | Schedule payment creation (3-installment split), partial payment logic, pay-now flow, payment status transitions, installment calculations |
| **Order Lifecycle** | Critical | Order acceptance triggers payment creation, status transitions, rejection/cancellation impact |
| **Settlement & Payout** | Critical | Generation for periods, amount calculation (total, commission, payable), status flow, batch operations |
| **Credit/Risk Assessment** | Critical | OMRS scoring (6 components), weighted calculation, credit assessment, risk weight configuration |
| **Authorization (RBAC)** | Critical | Permission gates on all routes, Spatie Permission, role hierarchy, sensitive data access |
| **Sensitive Data Access** | Critical | Approval workflow (request/approve/reject/revoke), time-bounded access, justification |
| **External Integrations** | High | Simah, Nafith, SingleView, Lean, ClickPay, Wathq — no integration tests |
| **Financial Accounting** | High | FAccounts/FEntry/FTransaction CRUD, expense management, trial balance |
| **Collections** | High | DPD tracking, installment calendar, promise-to-pay, penalties, dunning templates |
| **Merchant Onboarding** | High | Profile, compliance docs, status approval, Wathiq, commission |
| **Refund Processing** | High | Refund lifecycle, approval service, notification triggers |
| **Authentication (non-Fortify)** | High | Microsoft SSO, OTP verification, passkeys, 2FA enforcement middleware |
| **Audit Trail (service)** | High | AuditTrailService events/categories/justifications — only middleware tested |
| **API Endpoints** | High | Chat API, third-party test endpoints, token refresh |
| **Console Commands** | Medium | `process:scheduled-payments`, `send:reminders`, `reconciliation:daily`, `notify-low-stock` |
| **Exports** | Medium | Excel, PDF, CSV — output correctness |
| **Validation Rules** | Medium | 31 Form Request classes, custom `NoHtml` rule |
| **Notifications** | Medium | Firebase, SMS, email triggers on status changes |
| **Chat** | Medium | Real-time messaging, file upload, broadcasting |
| **Encrypted Fields** | Medium | PII encryption/decryption correctness |
| **Sanad** | Medium | Promissory note creation, cancellation, closing |

---

## IV. Recommended Test Suite Structure

```
tests/
├── Unit/
│   ├── Support/
│   │   └── CspNonceTest.php                    # [KEEP]
│   ├── Traits/
│   │   └── WithApprovalContextTest.php          # [KEEP]
│   ├── Services/
│   │   ├── RiskServiceTest.php                  # NEW — OMRS scoring algo
│   │   ├── CreditAssessmentServiceTest.php      # NEW
│   │   ├── SettlementServiceTest.php            # NEW — amount calc
│   │   └── PortfolioPerformanceServiceTest.php  # NEW
│   └── Rules/
│       └── NoHtmlTest.php                       # NEW
│
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php                        # NEW — SSO flow
│   │   ├── TwoFactorTest.php                    # [KEEP]
│   │   ├── PasskeyTest.php                      # NEW
│   │   ├── OtpVerificationTest.php              # NEW
│   │   └── DevLoginTest.php                     # NEW
│   ├── Orders/
│   │   ├── OrderLifecycleTest.php               # NEW
│   │   └── OrderValidationTest.php              # NEW
│   ├── Payments/
│   │   ├── SchedulePaymentTest.php              # NEW
│   │   ├── PartialPaymentTest.php               # NEW
│   │   └── PaymentCalculationTest.php           # NEW
│   ├── Settlements/
│   │   ├── SettlementGenerationTest.php         # NEW
│   │   ├── SettlementApprovalTest.php           # NEW
│   │   └── PayoutTest.php                       # NEW
│   ├── Risk/
│   │   ├── RiskScoreTest.php                    # NEW
│   │   ├── RiskWeightConfigTest.php             # NEW
│   │   └── RiskAnalyticsTest.php                # NEW
│   ├── Authorization/
│   │   ├── PermissionGatesTest.php              # NEW
│   │   ├── RoleManagementTest.php               # NEW
│   │   └── SensitiveDataAccessTest.php          # NEW
│   ├── Audit/
│   │   ├── AuditLogMiddlewareTest.php           # [KEEP]
│   │   └── AuditTrailServiceTest.php            # NEW
│   ├── Merchants/
│   │   ├── MerchantOnboardingTest.php           # NEW
│   │   ├── ComplianceDocumentsTest.php          # NEW
│   │   └── WathqIntegrationTest.php             # NEW
│   ├── Customers/
│   │   ├── CustomerManagementTest.php           # NEW
│   │   └── CreditLimitTest.php                  # NEW
│   ├── Collections/
│   │   ├── PromiseToPayTest.php                 # NEW
│   │   └── CollectionDashboardTest.php          # NEW
│   ├── Financial/
│   │   ├── FinancialAccountsTest.php            # NEW
│   │   ├── TransactionPostingTest.php           # NEW
│   │   └── ExpenseManagementTest.php            # NEW
│   ├── Exports/
│   │   ├── SupplierExportTest.php               # NEW
│   │   └── AuditExportTest.php                  # NEW
│   └── Console/
│       ├── ProcessScheduledPaymentsTest.php     # NEW
│       ├── SendPaymentRemindersTest.php         # NEW
│       └── DailyReconciliationTest.php          # NEW
│
└── Integration/
    ├── OrderToPaymentFlowTest.php               # NEW
    ├── PaymentToSettlementFlowTest.php          # NEW
    └── ExternalServiceIntegrationTest.php       # NEW
```

---

## V. Quick Wins (1–2 hours each)

| Test | Effort | Value | How |
|---|---|---|---|
| Payment Calculation | 1h | Very High | Unit test `createSchedulePayments()` — given `grand_total=X`, verify 3 installments of `X/3` at 30/60/90 days |
| Permission Gate Smoke | 2h | Very High | One test per gate — `actingAs` with/without permission → assert 200/403 |
| Settlement Amount | 1h | High | Unit test `SettlementService` — verify `payable = total - commission` |
| Risk Score Calculation | 1.5h | High | Unit test `calculateOMRS()` with known inputs → assert weighted output |
| AuditTrailService | 1.5h | High | Test data access, create, update, delete events with correct categories and masking |
| Order → Payment Creation | 1h | High | Accept order, assert 3 `SchedulePayment` records with correct amounts and due dates |
| Form Request Validation | 2h | Medium | One test per Form Request — required fields, format, custom rules |

---

## VI. Critical Missing Tests — Ranked by Business Risk

| Priority | Area | Risk | Recommendation |
|---|---|---|---|
| **P0** | Payment schedule creation from order acceptance | Data corruption — incorrect installments = unrecoverable | Unit test calculation. Feature test full order→payment flow. |
| **P0** | Settlement amount calculation | Financial loss — wrong supplier payouts = legal | Unit test `SettlementService`. Integration test generation. |
| **P0** | Authorization/permission gates | PII/financial data exposure | Smoke test every route with/without permission. Test `hasSensitivePermission()`. |
| **P1** | Risk score (OMRS) | Wrong credit decisions = default losses | Unit test all 6 components. Test weighted composite. |
| **P1** | Refund processing | Incorrect refunds = loss + regulatory | Feature test request→approval→notification. Test financial reversal. |
| **P1** | Sensitive data access | PII exposure = SAMA/PDPL violation | Test approval workflow. Test time-bounded expiry. |
| **P1** | Payout processing | Suppliers unpaid = platform failure | Integration test settlement→payout. Test bank file generator. |
| **P2** | Console commands | Silent failure = missed payments | Test each command with mock data. Test error handling. |
| **P2** | External integrations | Failed credit checks, SANAD, payments | Mock HTTP. Test retry/error. Test token refresh. |
| **P2** | Microsoft SSO | All users locked out | Mock Socialite. Test redirect, callback, encrypted email lookup, 2FA redirect. |
| **P2** | Collections/promise-to-pay | Revenue leakage | Test promise CRUD. Test payment sync on fulfillment. |
| **P3** | Exports (Excel, PDF, CSV) | Wrong data to regulators/banks | Test content matches query. Test PDF invoice. |
| **P3** | Real-time chat | Customer service failure | Test send/receive, read receipts, file upload, broadcasting. |
| **P3** | Notifications (Firebase, SMS, email) | Poor communication | Test triggers on order/payment/refund status changes. |

---

## VII. Architectural Issues Affecting Testability

1. **Encrypted emails break password reset** — `PasswordBroker::where('email', ...)` cannot match encrypted values. Needs blind index or SSO-only recovery.

2. **No migrations in repo** — `database/migrations/` is empty. `RefreshDatabase` depends on pre-existing MySQL schema. Migrations are critical for CI.

3. **MySQL dependency in tests** — `.env.testing` uses MySQL on `127.0.0.1`. SQLite in-memory is preferred for most tests and CI.

4. **No factories for domain models** — Only `UserFactory` and `TeamFactory` exist. Need factories for `Order`, `Payment`, `Merchant`, `Customer`, `Settlement`, `SchedulePayment`, etc.

5. **Direct model usage in controllers** — No repository layer. Extracting logic into services/actions would improve unit-testability.

6. **Encrypted attributes in factories** — Models with `EncryptsAttributes` trait need special handling in factory definitions.
