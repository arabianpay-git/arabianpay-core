# Secret Rotation Checklist

> **Scope:** ArabianPay Core (arabianpay-admin) — application secrets that
> must be rotated as part of SAMA compliance remediation (CORE-P0-09).
>
> **Trigger:** this checklist must be executed before the sandbox
> submission cutover and any time a member with prior credential access
> leaves the team.
>
> **Owner:** DevOps Lead, with sign-off from Security Lead and Finance
> Lead for payment-integration secrets.
>
> **Evidence:** each row in the checklist is evidence-producing — record
> the new secret's fingerprint (not the value) and the rotation timestamp
> in the `secret-rotations` ticket.

## Hard Commitments

1. No secret value is ever pasted into Slack, Jira, Notion, email, or
   git. Share via 1Password or the secret manager; destroy ephemeral
   copies.
2. All secrets are kept out of git HEAD. `.env`, `.env.backup`, and
   `.env.production` are gitignored (verified in `.gitignore` line 14-16).
3. Rotated values must be active in staging for ≥24 h before promotion
   to production. Any error budget breach blocks the promotion.
4. Previous credentials must be revoked at the issuer within 24 h of
   rotation. A revocation that cannot be completed is logged as a
   compliance incident.

## Rotation Matrix

| Secret                    | Env Var                         | Owner          | Issuer / Location                 | Rotation Trigger                                  | Priority |
| ------------------------- | ------------------------------- | -------------- | --------------------------------- | ------------------------------------------------- | -------- |
| Laravel app key           | `APP_KEY`                       | DevOps         | Generated (`php artisan key:generate`) | Exposed once in any committed `.env` — rotate now | **P0**   |
| DB password (primary)     | `DB_PASSWORD`                   | DevOps / DBA   | MySQL user                        | Default `Asad@123` was hardcoded — rotate now     | **P0**   |
| DB legacy password        | `DB_OLD_PASSWORD`               | DevOps / DBA   | MySQL user                        | Retire the fallback; remove user after cutover    | **P0**   |
| Microsoft SSO secret      | `MICROSOFT_CLIENT_SECRET`       | Security Lead  | Azure AD app registration         | Was displayed via `dd()` in commits — rotate now  | **P0**   |
| SIMAH login credentials   | `SIMAH_USERNAME`, `SIMAH_PASSWORD` | Compliance   | SIMAH partner portal              | Hardcoded default in `config/simah.php` removed — rotate now | **P0**   |
| Sanctum token prefix      | `SANCTUM_TOKEN_PREFIX`          | Security Lead  | Generated prefix                  | Rotate with every APP_KEY rotation                | P1       |
| Nafith client secret      | `NAFITH_CLIENT_SECRET`          | Compliance     | Nafith partner portal             | Annual cycle or on offboarding                    | P1       |
| Nafith sign secret        | `NAFITH_SIGN_SECRET`            | Compliance     | Nafith partner portal             | Annual cycle or on offboarding                    | P1       |
| Nafith basic auth token   | `NAFITH_AUTH_BASIC_TOKEN`       | Compliance     | Nafith partner portal             | Annual cycle or on offboarding                    | P1       |
| Lean client secret        | `LEAN_CLIENT_SECRET`            | Compliance     | Lean dashboard                    | Annual cycle or on offboarding                    | P1       |
| AWS keys                  | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | DevOps | AWS IAM                           | Quarterly + on offboarding                        | P1       |
| Pusher / Reverb secrets   | `PUSHER_APP_SECRET`, `REVERB_APP_SECRET` | DevOps | Broadcasting provider             | Annual + on offboarding                           | P1       |
| Mail credentials          | `MAIL_PASSWORD`                 | DevOps         | SMTP provider                     | Annual + on offboarding                           | P2       |
| Redis password            | `REDIS_PASSWORD`                | DevOps         | Redis ACL                         | Quarterly                                         | P2       |
| Memcached password        | `MEMCACHED_PASSWORD`            | DevOps         | Memcached                         | Quarterly (if used)                               | P2       |
| Ably key                  | `ABLY_KEY`                      | DevOps         | Ably dashboard                    | Annual (if used)                                  | P2       |
| Firebase push credentials | `kreait/laravel-firebase` JSON  | DevOps         | Firebase console                  | Annual + on offboarding                           | P1       |
| ClickPay server key       | `CLICKPAY_SERVER_KEY`           | Finance Lead   | ClickPay dashboard                | Annual + on offboarding                           | P1       |
| Google Translate API key  | `GOOGLE_TRANSLATE_API_KEY`      | DevOps         | GCP project                       | Annual                                            | P2       |
| Resend key                | `RESEND_KEY`                    | DevOps         | Resend dashboard                  | Annual                                            | P2       |
| Postmark token            | `POSTMARK_TOKEN`                | DevOps         | Postmark                          | Annual                                            | P2       |
| Slack bot OAuth token     | `SLACK_BOT_USER_OAUTH_TOKEN`    | DevOps         | Slack app                         | Annual + on offboarding                           | P2       |
| OTP / Fortify internals   | `TWO_FACTOR_CONFIRM_PASSWORD`   | Security Lead  | Laravel config                    | Hardcoded to `true`; no secret to rotate          | —        |

## P0 Rotation Runbook (single secret)

1. **Notify stakeholders** in #arabianpay-compliance with 30-minute
   window before rotation.
2. **Generate new secret** at the issuer. Record fingerprint only.
3. **Stage** the new value in the staging secret manager. Redeploy
   staging. Smoke-test the dependent flow (login / SIMAH report /
   payment / etc.).
4. **Rotate in production**: update the production secret manager,
   trigger rolling restart.
5. **Revoke the old value** at the issuer within 24 h.
6. **Evidence**: attach to the secret-rotations ticket — new
   fingerprint, rotation timestamp, revocation timestamp, and the
   staging smoke-test logs.
7. **Post-mortem**: if the rotation exposed any hardcoded fallback or
   undocumented consumer, open a follow-up ticket under
   `compliance/secrets-debt`.

## Git History Remediation

`.env` is currently gitignored (`/.gitignore:14`). A `git log --all -- .env`
run on 2026-04-15 returned no tracked history for the file, confirming it
has never been committed in this repo. The `Asad@123` hardcoded DB
password fallback in `config/database.php` was committed — it has been
removed in commit 256427e but rotation at the DB layer is still
required (see table above). **Full git history rewrite is NOT required
for this repo**; it is required for the infrastructure repo tracked
under CROSS-P0-02.

## Verification

- Run `php artisan config:show simah` on staging and production after
  rotation — both `username` and `password` must resolve to the new
  values and must not be empty.
- Run `php artisan config:show database.connections.mysql` — confirm
  the `password` key is not the historical hardcoded default.
- Run `grep -rE "env\\('[A-Z_]+',\\s*'[^']{6,}'\\)" config/` — must
  return zero matches for any env var containing `PASS`, `SECRET`,
  `TOKEN`, `KEY`, or `CREDENTIAL`.

## Sign-Off

| Role            | Name | Date | Signature |
| --------------- | ---- | ---- | --------- |
| DevOps Lead     |      |      |           |
| Security Lead   |      |      |           |
| Compliance Lead |      |      |           |
| Finance Lead    |      |      |           |
