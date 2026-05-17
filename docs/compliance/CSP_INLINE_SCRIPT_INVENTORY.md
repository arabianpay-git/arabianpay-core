# CSP Inline-Script Inventory

> **Scope:** CORE-P0-10 Phase 1. This document inventories every Blade
> template that contains an inline `<script>` block so that Phase 2
> (migration) and Phase 3 (enforcement cutover) can track progress.
>
> **Audit date:** 2026-04-15 (`git rev HEAD`).
>
> **Counting rule:** a file is counted once, regardless of how many
> inline script blocks it contains. Empty `<script src="…"></script>`
> tags (external scripts) do NOT count — only blocks with an inline
> body do.

## Phase Map

| Phase | Deliverable                                                                                   | Status |
| ----- | --------------------------------------------------------------------------------------------- | ------ |
| 1     | Nonce plumbing: `App\Support\CspNonce`, `csp_nonce()`, `@cspNonce`, middleware attach        | ✅ Done |
| 2     | Migrate inline scripts: extract to static assets where possible, add `@cspNonce` elsewhere   | ⏳ Open |
| 3     | Flip `CSP_NONCE_ENFORCE=true` per environment; remove `'unsafe-inline'`/`'unsafe-eval'`      | ⏳ Open |

Phase 1 does NOT enable enforcement. The nonce is emitted in headers and
markup, but `'unsafe-inline'` remains in `config/csp.php` as a fallback
so the 174 inventoried files do not break in production.

## How to Migrate a File (Phase 2)

1. If the script can be moved to `resources/js/…` and compiled via Vite,
   do that. This is the preferred outcome — no nonce needed, browser
   cacheable, testable.
2. If the script cannot be extracted (e.g. it uses Blade-rendered data
   or is tightly coupled to the component), add `@cspNonce` to the
   opening tag:
   ```blade
   <script @cspNonce>
     const userId = {{ json_encode($user->id) }};
     // …
   </script>
   ```
3. If the script is third-party copy-paste (analytics, pixels, etc.),
   either host it locally and use `@cspNonce`, or move it behind a
   feature flag and only load from an allowlisted host — do NOT use
   `'unsafe-inline'`.
4. Remove the file from the "Remaining" section below and move it to
   "Migrated".

## Remaining (174 files)

Counts by directory, high to low:

| Directory                                             | Count |
| ----------------------------------------------------- | ----- |
| `admin/accounts/*`                                    | 23    |
| `admin/collections/*`                                 | 19    |
| `admin/risk-management/*`                             | 14    |
| `chat/*`                                              |  9    |
| `admin/orders/partials`                               |  8    |
| `admin/financial/*`                                   |  8    |
| `admin/products`                                      |  3    |
| `admin/reports/*`                                     |  7    |
| `media`                                               |  5    |
| `layouts/*` + `layouts/includes/*`                    |  6    |
| `admin/components`                                    |  4    |
| `admin/employees`                                     |  4    |
| `admin/categories`                                    |  3    |
| `admin/case-management`                               |  3    |
| `admin/settings`                                      |  3    |
| `admin/supplier-and-sales`                            |  2    |
| `admin/supplier-roles`                                |  2    |
| `admin/schedule-payment`                              |  2    |
| `admin/support-ticket`                                |  2    |
| `passkeys`                                            |  2    |
| `auth`                                                |  2    |
| Other singletons                                      | 44    |

### Full File List

```text
resources/views/admin/accounts/components/business-summary.blade.php
resources/views/admin/accounts/components/cr-data-modal.blade.php
resources/views/admin/accounts/components/financial-snapshot.blade.php
resources/views/admin/accounts/components/merchant-approve-modal.blade.php
resources/views/admin/accounts/components/merchant-status-dropdown.blade.php
resources/views/admin/accounts/customer-credit.blade.php
resources/views/admin/accounts/customer-finance.blade.php
resources/views/admin/accounts/customer-log.blade.php
resources/views/admin/accounts/customer-profile.blade.php
resources/views/admin/accounts/customer.blade.php
resources/views/admin/accounts/includes/customer-header.blade.php
resources/views/admin/accounts/includes/header.blade.php
resources/views/admin/accounts/lean/components/bank-statement.blade.php
resources/views/admin/accounts/lean/components/banks.blade.php
resources/views/admin/accounts/lean/components/entities.blade.php
resources/views/admin/accounts/lean/index.blade.php
resources/views/admin/accounts/nafath.blade.php
resources/views/admin/accounts/partials/commission-update-modal.blade.php
resources/views/admin/accounts/partials/suppliers-table.blade.php
resources/views/admin/accounts/partials/suppliers-trashed-table.blade.php
resources/views/admin/accounts/simah-components/consumer-score.blade.php
resources/views/admin/accounts/simah-components/silver-report.blade.php
resources/views/admin/accounts/singleview-components/accounts.blade.php
resources/views/admin/accounts/singleview-components/all-accounts-balance.blade.php
resources/views/admin/accounts/singleview-components/credit-check-basic.blade.php
resources/views/admin/accounts/singleview-components/income-check-advanced.blade.php
resources/views/admin/accounts/supplier-compliance.blade.php
resources/views/admin/accounts/supplier-finance.blade.php
resources/views/admin/accounts/supplier-profile2.blade.php
resources/views/admin/accounts/suppliers.blade.php
resources/views/admin/approvals/index.blade.php
resources/views/admin/attributes/attribute-value-create.blade.php
resources/views/admin/attributes/attribute-value-edit.blade.php
resources/views/admin/audit/logs.blade.php
resources/views/admin/audit/trails.blade.php
resources/views/admin/brands/index.blade.php
resources/views/admin/case-management/create.blade.php
resources/views/admin/case-management/edit.blade.php
resources/views/admin/case-management/index.blade.php
resources/views/admin/categories/create.blade.php
resources/views/admin/categories/edit.blade.php
resources/views/admin/categories/index.blade.php
resources/views/admin/checkouts/partials/orders.blade.php
resources/views/admin/checkouts/partials/schedule-payments.blade.php
resources/views/admin/checkouts/show.blade.php
resources/views/admin/collections/alerts.blade.php
resources/views/admin/collections/allocations.blade.php
resources/views/admin/collections/components/dashboard-collection-reports.blade.php
resources/views/admin/collections/components/dashboard-installments.blade.php
resources/views/admin/collections/components/dunning-template-create-edit-modal.blade.php
resources/views/admin/collections/components/header-modals/note.blade.php
resources/views/admin/collections/components/header-modals/partial-payment.blade.php
resources/views/admin/collections/components/header-modals/promise.blade.php
resources/views/admin/collections/components/header-modals/send-reminder.blade.php
resources/views/admin/collections/components/installment-notes.blade.php
resources/views/admin/collections/components/installment-promises.blade.php
resources/views/admin/collections/components/promise-to-pay-modal.blade.php
resources/views/admin/collections/dunning.blade.php
resources/views/admin/collections/flags.blade.php
resources/views/admin/collections/index.blade.php
resources/views/admin/collections/installment-calander.blade.php
resources/views/admin/collections/installments.blade.php
resources/views/admin/collections/partial-payments.blade.php
resources/views/admin/collections/promisetopay.blade.php
resources/views/admin/components/payout-create.blade.php
resources/views/admin/components/transfer-detail.blade.php
resources/views/admin/components/transfer-request-bulk.blade.php
resources/views/admin/components/transfer-request.blade.php
resources/views/admin/coupons/create.blade.php
resources/views/admin/coupons/edit.blade.php
resources/views/admin/credit-managment/limits.blade.php
resources/views/admin/credit-managment/repayment.blade.php
resources/views/admin/customer-and-sales/detailed-customer-debt.blade.php
resources/views/admin/dashboard/includes/data-approval-modal.blade.php
resources/views/admin/dashboard/index.blade.php
resources/views/admin/departments/create.blade.php
resources/views/admin/departments/edit.blade.php
resources/views/admin/employees/create.blade.php
resources/views/admin/employees/edit.blade.php
resources/views/admin/employees/index.blade.php
resources/views/admin/employees/permissions.blade.php
resources/views/admin/financial/dashboard-scripts.blade.php
resources/views/admin/financial/dashboard-simple.blade.php
resources/views/admin/financial/dashboard.blade.php
resources/views/admin/financial/expense-settings/create.blade.php
resources/views/admin/financial/expense-settings/edit.blade.php
resources/views/admin/financial/transactions/modal.blade.php
resources/views/admin/financial/trial-balance.blade.php
resources/views/admin/internel-tickets/create.blade.php
resources/views/admin/internel-tickets/show.blade.php
resources/views/admin/investment-pools/calendar.blade.php
resources/views/admin/investment-pools/show.blade.php
resources/views/admin/logs/index.blade.php
resources/views/admin/merchants/index.blade.php
resources/views/admin/orders/index.blade.php
resources/views/admin/orders/partials/installment-update-modal.blade.php
resources/views/admin/orders/partials/order-accept-modal.blade.php
resources/views/admin/orders/partials/order-header-buttons.blade.php
resources/views/admin/orders/partials/order-partials.blade.php
resources/views/admin/orders/partials/order-reject-modal.blade.php
resources/views/admin/orders/partials/order-status.blade.php
resources/views/admin/orders/partials/paynow-modal.blade.php
resources/views/admin/orders/partials/sanad-detail-modal.blade.php
resources/views/admin/payouts/index.blade.php
resources/views/admin/products/bulk-upload.blade.php
resources/views/admin/products/create.blade.php
resources/views/admin/products/edit.blade.php
resources/views/admin/refund-requests/index.blade.php
resources/views/admin/reports/financial_summary_report.blade.php
resources/views/admin/reports/includes/customer-filter.blade.php
resources/views/admin/reports/includes/filter.blade.php
resources/views/admin/reports/onboarding_funnel_report.blade.php
resources/views/admin/reports/portfolio_performance_report.blade.php
resources/views/admin/reports/risk_exposure_analysis.blade.php
resources/views/admin/reports/system_activity_audit_report.blade.php
resources/views/admin/risk-management/alerts-index.blade.php
resources/views/admin/risk-management/components/early-warning-system.blade.php
resources/views/admin/risk-management/components/merchant-score-table.blade.php
resources/views/admin/risk-management/components/pipeline-analysis.blade.php
resources/views/admin/risk-management/components/portfolio-overview.blade.php
resources/views/admin/risk-management/components/risk-data-alerts.blade.php
resources/views/admin/risk-management/components/risk-flags.blade.php
resources/views/admin/risk-management/components/risk-weight-modal.blade.php
resources/views/admin/risk-management/components/score-table.blade.php
resources/views/admin/risk-management/components/weight-modal.blade.php
resources/views/admin/risk-management/dashboard.blade.php
resources/views/admin/risk-management/details.blade.php
resources/views/admin/risk-management/score.blade.php
resources/views/admin/risk-management/verify_otp.blade.php
resources/views/admin/role_permissions/create.blade.php
resources/views/admin/role_permissions/edit.blade.php
resources/views/admin/schedule-payment/index.blade.php
resources/views/admin/schedule-payment/show.blade.php
resources/views/admin/settlements/index.blade.php
resources/views/admin/supplier-and-sales/detailed-supplier-debt.blade.php
resources/views/admin/supplier-and-sales/supplier-entitlement.blade.php
resources/views/admin/supplier-roles/create.blade.php
resources/views/admin/supplier-roles/edit.blade.php
resources/views/admin/support-ticket/create.blade.php
resources/views/admin/support-ticket/show.blade.php
resources/views/admin/transactions/index.blade.php
resources/views/admin/transfer_requests/index.blade.php
resources/views/api-tester/index.blade.php
resources/views/auth/login.blade.php
resources/views/auth/register.blade.php
resources/views/chat/chat.blade.php
resources/views/chat/components/chat-form.blade.php
resources/views/chat/components/connection-status.blade.php
resources/views/chat/components/debug-panel.blade.php
resources/views/chat/components/main-chat.blade.php
resources/views/chat/components/messages-list.blade.php
resources/views/chat/components/realtime-service.blade.php
resources/views/chat/components/sidebar.blade.php
resources/views/chat/index.blade.php
resources/views/fcm.blade.php
resources/views/layouts/auth.blade.php
resources/views/layouts/base.blade.php
resources/views/layouts/includes/notifications.blade.php
resources/views/layouts/includes/passkey-modal.blade.php
resources/views/layouts/includes/scripts.blade.php
resources/views/layouts/sidebar.blade.php
resources/views/media/index.blade.php
resources/views/media/multi-media-picker.blade.php
resources/views/media/multiple.blade.php
resources/views/media/picker.blade.php
resources/views/media/single.blade.php
resources/views/passkeys/login.blade.php
resources/views/passkeys/register.blade.php
resources/views/send-fcm.blade.php
resources/views/settings/email.blade.php
resources/views/settings/general.blade.php
resources/views/settings/index.blade.php
resources/views/settings/risk-weight/index.blade.php
resources/views/sms/send.blade.php
```

## Migrated

_None yet. Phase 2 migrations move files from the list above into this
section with a brief note on whether they were extracted to Vite or
kept inline with `@cspNonce`._

## Verification (Phase 3 cutover)

After all entries are migrated and before flipping
`CSP_NONCE_ENFORCE=true`:

1. Run a smoke pass on every primary admin page with `CSP_NONCE_ENFORCE=true`
   set in staging.
2. Watch browser console for `Refused to execute inline script because…`
   errors. Any such error must resolve to a missed file in the list above.
3. Grep `resources/views/` for `<script` without `@cspNonce` or `src=`:
   ```bash
   grep -rEn "<script(\\s+[^>]*)?>" resources/views/ \
     | grep -vE "src=|@cspNonce|</script>\\s*$"
   ```
   Zero matches required.
4. Remove `'unsafe-inline'` and `'unsafe-eval'` from
   `config/csp.php:script-src` and confirm enforcement in staging for
   ≥48 h before production promotion.
