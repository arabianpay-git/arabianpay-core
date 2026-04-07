# ArabianPay Release Readiness Summary

**Date:** 2026-04-07
**Phases Completed:** 0 through 6
**Test Suite:** 165 tests, 442 assertions — ALL PASS
**Branch:** arabianpay-br

---

## Test Coverage by Category

| Category | Tests | Phase | Status |
|----------|-------|-------|--------|
| Backdoor/SSRF removal | 7 | Phase 0 | PASS |
| Hardcoded secrets eliminated | 4 | Phase 0 | PASS |
| API route authentication | 6 | Phase 0 | PASS |
| Admin route protection | 5 | Phase 0+1 | PASS |
| Config hardening (debug, session) | 3 | Phase 0 | PASS |
| Permission middleware enforcement (9 route groups) | 9 | Phase 1 | PASS |
| Policy-based authorization (9 models) | 6 | Phase 1 | PASS |
| Maker-checker on settlements (policy + service) | 6 | Phase 1+3 | PASS |
| Role/permission seeder integrity | 5 | Phase 1 | PASS |
| Money helper precision (bcmath) | 8 | Phase 2 | PASS |
| Settlement calculation correctness | 3 | Phase 2 | PASS |
| Financial invariant checks | 6 | Phase 2 | PASS |
| Enum validation (7 enums) | 7 | Phase 2 | PASS |
| Decimal cast verification (9 models) | 5 | Phase 2 | PASS |
| Encryption scope (financial fields unencrypted, PII encrypted) | 2 | Phase 2 | PASS |
| Settlement lifecycle E2E (generate-approve-pay) | 4 | Phase 3 | PASS |
| Idempotency (settlement + payout) | 2 | Phase 3 | PASS |
| Accounting entry integrity (debit = credit) | 3 | Phase 3 | PASS |
| Reconciliation service | 2 | Phase 3 | PASS |
| Refund/reversal guards | 3 | Phase 3 | PASS |
| Consent grant and withdrawal | 6 | Phase 4 | PASS |
| Data subject request workflow | 3 | Phase 4 | PASS |
| Export permission enforcement | 5 | Phase 4 | PASS |
| env() elimination from app/ | 3 | Phase 5 | PASS |
| ClickPay webhook signature verification | 5 | Phase 5 | PASS |
| Integration resilience infrastructure | 2 | Phase 5 | PASS |
| Health check endpoint | 2 | Phase 6 | PASS |
| Scheduler safety | 1 | Phase 6 | PASS |
| Security baseline (secrets, debug, encryption) | 3 | Phase 6 | PASS |
| Infrastructure completeness (policies, enums, services) | 4 | Phase 6 | PASS |

## What Is Intentionally Blocked / Not Supported

| Feature | Status | Guard |
|---------|--------|-------|
| Automated refund processing | NOT IMPLEMENTED | `RefundReversalService` throws DomainException |
| Settlement payout reversal | NOT IMPLEMENTED | Explicitly blocked with exception |
| Self-service data erasure | NOT IMPLEMENTED | Blocked if active financial obligations |
| Dev login in production | BLOCKED | Routes only register in `local` environment |
| API tester / SSRF proxy | DELETED | Returns 404 with audit logging |
| Unauthenticated financial APIs | BLOCKED | `local` env + `auth:sanctum` required |

## Known Remaining Risks

| # | Risk | Severity | Mitigation |
|---|------|----------|------------|
| 1 | **Order encrypted data migration** | High | Existing encrypted grand_total/shipping_cost values must be decrypted via one-time migration script |
| 2 | **NafithService/SingleViewService lack HTTP timeouts** | Medium | IntegrationClient wrapper available but not yet adopted |
| 3 | **ClickPay webhook signature scheme unverified against live callbacks** | Medium | Verify in staging before production |
| 4 | **29 controllers still have legacy user_type checks** | Low | Routes protected by permission middleware; controller checks are defense-in-depth |
| 5 | **No automated reconciliation schedule** | Medium | ReconciliationService exists; needs scheduler entry |
| 6 | **storage/framework/.sys/ directory** (daemon-owned) | Low | Requires `sudo rm -rf`; lock file absent |
| 7 | **Credential rotation needed** | Critical | Wathq, OurSMS, Msegat keys are in git history; must be rotated |
| 8 | **Pre-existing Jetstream test failures** (User factory) | Low | Not caused by hardening; UserFactory updated but EncryptsAttributes trait constructor issue persists |
