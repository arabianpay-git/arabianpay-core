# INCIDENT-2026-05 — ArabianPay Admin Backdoor Removal Runbook

**Date:** June 7, 2026
**Lead:** Ghazi Tom
**Suspect:** `Asad Mahmood <asad.mahmood@arabianpay.net>` (also controlled `Xcode <secureflowofficial@proton.me>`)
**Status:** Active incident — follow phases in order

## Summary

A destructive backdoor (kill-switch wiper + .env exfiltration) was planted in this repository by `Asad Mahmood` starting June 3, 2025. The backdoor is currently live on `main` and in every branch descended from `main` after that date. The last 3 commits (May 17–18, 2026) added an `.env` exfiltration endpoint at `/pandaxcode`. Disk artifacts confirm the backdoor has executed in a real environment.

### Decisions made

| Question | Decision |
|---|---|
| Git history strategy | **Revert** — preserve history for audit, do not rewrite |
| `.env` status | **Treat as leaked** — rotate everything |
| `worktree-agent-*` branches | **Delete** — they are local-only and derived from compromised `main` |
| Execution | **Internal team executes**; automation stops after this runbook |

### Backdoor payloads in git (2 files)

| File | Added | Purpose |
|---|---|---|
| `pandaxcode.php` | 2026-05-17 (`8db5e17`, Asad) | `.env` exfil — returns file via `GET /pandaxcode` |
| `bootstrap/cache/vendor/assets/.bin/x9/Handler.php` | 2025-06-03 (`3310598`, Asad) | Kill-switch wiper — `GET /kill/{secret}/destroy` deletes `.env`, routes, controllers, models, DB |

### Modified legitimate files

| File | Unsuspected changed by |
|---|---|
| `public/index.php` | Commits `5da85a6`, `7d37402`, `2c68dd3`, `8dcc9bd` — wired both backdoors into every request |
| `.gitignore` | Commit `fa8cbfa` — removed ignore for `/.env`, added explicit whitelist for `bootstrap/cache/vendor/assets/.bin/x9/**` |

---

## Phase 0 — Evidence preservation (~30 min)

> Do this first. Do not skip. Do not merge/rebase/delete anything until Phase 3.

```bash
# 0.1 Mirror the entire repo (every ref, every branch, every tag) offline
mkdir -p /secure/forensic/2026-05-arabianpay
cd /secure/forensic/2026-05-arabianpay
git clone --mirror /Users/gazitom/Documents/my_project/arabianpay-admin ./repo.git
shasum -a 256 repo.git > repo.git.sha256

# 0.2 Snapshot the working tree
cd /Users/gazitom/Documents/my_project/arabianpay-admin
tar --exclude=vendor --exclude=node_modules -czf \
    /secure/forensic/2026-05-arabianpay/working-tree.$(date +%s).tar.gz .
shasum -a 256 /secure/forensic/2026-05-arabianpay/working-tree.$(date +%s).tar.gz

# 0.3 Capture runtime artifacts (proof the Handler booted)
cp -a storage/framework/.sys  /secure/forensic/2026-05-arabianpay/.sys.$(date +%s)
cp -a bootstrap/cache/vendor /secure/forensic/2026-05-arabianpay/.bootstrap-cache-vendor.$(date +%s)

# 0.4 Make the writable working tree read-only for the rest of the response
chmod -R 444 /Users/gazitom/Documents/my_project/arabianpay-admin/{public/index.php,bootstrap/cache/vendor,storage/framework/.sys,pandaxcode.php}
# (revert with: chmod -R u+w <path>)
```

### Out-of-band (on the prod host, NOT on this dev box)

- Pull Nginx/Apache access logs for the window 2025-06-03 → today. Grep for `/pandaxcode`, `/kill/`, `/revive/`.
- Pull `storage/logs/laravel*.log`, `php-fpm` error log, MySQL general log + binlog.
- Pull `auth.log` / `secure` and `last`/`lastb`.
- Copy (do not move) the production `.env` to a forensic share for the secret inventory.

---

## Phase 1 — Containment (within 1 hour, before any code work)

### 1.1 Bitbucket (as workspace admin, in the Bitbucket UI — order matters)

1. Remove `asad.mahmood@arabianpay.net` from the `arabian-pay` workspace.
2. Revoke ALL personal access tokens and OAuth grants for that account.
3. Disable the workspace API token used by `.git/config` (the `ATCTT3xFfGN0…` one).
4. Disable all repository webhooks.

### 1.2 SSO (Microsoft Entra / Azure AD)

1. Disable `asad.mahmood@arabianpay.net`.
2. Revoke all refresh tokens and active sessions globally.
3. Review sign-in logs for the account for the last 12 months.
4. Check for any service principals / app registrations the user owned.

### 1.3 Mailbox

1. Suspend `asad.mahmood@arabianpay.net` (do not delete; legal hold).
2. Forward a copy to `legal-hold@arabianpay.net`.
3. Revoke any OAuth grants from the mailbox to third parties.

### 1.4 Production app

1. At the load balancer / Nginx level: return static 503 for everything. **DO NOT** use `php artisan down` (the wiper calls it).
2. Block these URL patterns at the edge immediately:

```nginx
location ~ ^/(pandaxcode|kill/.+/destroy|revive/.+)$ { return 404; }
```

3. Take a consistent point-in-time snapshot of the DB and file system (the last known good-ish state for comparison).
4. Do NOT delete `storage/framework/.sys/.cache/.xcr9z.lock` if present — capture it for forensics.

### 1.5 Bitbucket audit log export

Export the full workspace audit log (Repository settings → Audit log). Save to `/secure/forensic/`.

> **Stop and check:** Confirm `asad.mahmood@arabianpay.net` can no longer reach Bitbucket, Microsoft SSO, email, or any VPN before proceeding.

---

## Phase 2 — Eradication (code removal via revert) — 1–3 days

**Strategy:** one new branch per base branch, apply reverts, get peer review, merge.

### 2.1 Create the cleanup branch from current `main`

```bash
cd /Users/gazitom/Documents/my_project/arabianpay-admin
chmod -R u+w .   # undo the read-only lockdown from Phase 0.3
git checkout main
git pull --ff-only
git checkout -b security/incident-2026-05-backdoor-removal
```

### 2.2 Apply reverts in this exact order

```bash
# --- Kill-switch wiper (in every branch it lives in) ---
git revert --no-edit 58e0df5   # "some updates" (15:14)
git revert --no-edit 8e0e989   # "some updates"
git revert --no-edit d99d30a   # "Working"
git revert --no-edit dfed56b   # "Working"
git revert --no-edit 3c93375   # "Working"
git revert --no-edit fa8cbfa   # CRITICAL: restore .gitignore to ignore /bootstrap/cache/
git revert --no-edit 375ef0e   # remove .gitkeep from the now-ignored path
git revert --no-edit 3310598   # delete Handler.php
git revert --no-edit 2c68dd3   # unwire Handler from public/index.php
git revert --no-edit 8dcc9bd   # undo the re-indent of public/index.php

# --- pandaxcode .env exfiltration (main only) ---
git revert --no-edit 8db5e17   # delete pandaxcode.php
git revert --no-edit 7d37402   # unwire /pandaxcode route
git revert --no-edit 5da85a6   # undo the Xcode path fix
```

**Resolve conflicts:** The first revert (`58e0df5`) may conflict with later changes that depend on the `use` line it adds. If `git revert` reports a conflict, open the file, keep the `use` line in the pre-attack state (i.e. remove it), and `git add` the file.

### 2.3 Post-revert validation (run after every single revert)

```bash
# Tree-level: backdoor must be gone
grep -rE "pandaxcode|some_random_long_secret_key|revive/|x9|xcr9z" \
     app/ bootstrap/ public/ config/ routes/ 2>/dev/null
# Expected: no output.

# .gitignore: must re-ignore .env and /bootstrap/cache/
grep -E '^\.env$|^/bootstrap/cache/|^/bootstrap/\*$' .gitignore
# Expected: at least these three lines are present.

# .gitignore: must NOT whitelist the backdoor path
grep -E '\.bin/x9|!bootstrap/cache/vendor/assets' .gitignore
# Expected: no output.

# public/index.php: no hand-rolled REQUEST_URI short-circuits
grep -E "REQUEST_URI\s*===" public/index.php
# Expected: no output.

# PHP syntax
php -l public/index.php
# Expected: No syntax errors detected.

# Bootstrap autoload (sanity)
composer dump-autoload --no-interaction
```

### 2.4 Per-branch propagation

Apply the same 10-commit revert list to each branch that contains the kill-switch:

```bash
for branch in development feature/core-p1 security-audit-base; do
  git checkout "$branch"
  git pull --ff-only
  git checkout -b "security/incident-2026-05-backdoor-removal-${branch//\//-}"
  # apply the same 10 reverts in the same order
  # run the 2.3 checklist
  # push and open a PR
done
```

The `feature/risk-module` remote is a duplicate of `development` — delete it after `development` cleanup merges.

### 2.5 Add regression guards

Create `tests/Feature/BackdoorGuardTest.php`:

```php
<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class BackdoorGuardTest extends TestCase
{
    /** @test */
    public function public_index_php_does_not_contain_backdoor_routes(): void
    {
        $index = file_get_contents(base_path('public/index.php'));

        $this->assertStringNotContainsString('pandaxcode', $index);
        $this->assertStringNotContainsString('some_random_long_secret_key', $index);
        $this->assertStringNotContainsString("REQUEST_URI === '/kill", $index);
        $this->assertStringNotContainsString("REQUEST_URI === '/revive", $index);
        $this->assertStringNotContainsString('x9/Handler', $index);
    }

    /** @test */
    public function gitignore_blocks_backdoor_paths(): void
    {
        $gi = file_get_contents(base_path('.gitignore'));

        $this->assertStringContainsString('/bootstrap/cache/', $gi);
        $this->assertStringContainsString(".env\n", $gi . "\n");
        $this->assertStringNotContainsString('!bootstrap/cache/vendor/assets/.bin/x9', $gi);
    }
}
```

Add to `composer.json` → `"scripts"`:

```json
"audit:backdoor": "tests/Scripts/backdoor_grep.sh"
```

Create `tests/Scripts/backdoor_grep.sh` (chmod +x):

```bash
#!/usr/bin/env bash
set -euo pipefail
PATTERNS='pandaxcode|some_random_long_secret_key|xcr9z|/kill/.*/destroy|/revive/|x9/Handler'
if git grep -nE "$PATTERNS" -- app public bootstrap config routes 2>/dev/null; then
  echo "BACKDOOR IOC DETECTED — see lines above" >&2
  exit 1
fi
```

Add to `bitbucket-pipelines.yml`:

```yaml
- step:
    name: Backdoor IOC scan
    script:
      - bash tests/Scripts/backdoor_grep.sh
```

### 2.6 Delete the worktree branches

```bash
git worktree list                                    # see active worktrees
git worktree remove --force ./<path-of-worktree>     # for each worktree-agent-*
git branch -D worktree-agent-a046100c \
              worktree-agent-a069f248 \
              worktree-agent-a0d05694 \
              worktree-agent-a3cbdb99 \
              worktree-agent-ac5ab1f8 \
              worktree-agent-aca0293d \
              worktree-agent-acc835bf \
              worktree-agent-acc9e1e0
```

### 2.7 Push, review, merge

```bash
git push -u origin security/incident-2026-05-backdoor-removal
```

Open a PR. Require 2 reviewers (NOT Asad). Require CI green.

Merge order: `security-audit-base` → `development` → `main`.

---

## Phase 3 — Secret rotation (parallel with Phase 2; 48–72 hours)

**Assumption:** every value in production `.env` is in attacker hands.

### 3.1 Inventory

SSH to the prod host. Read the file. Rotate at minimum:

| Variable | Service | Rotate at |
|---|---|---|
| `APP_KEY` | Laravel | `php artisan key:generate --force` |
| `DB_*` | MySQL | `ALTER USER ... IDENTIFIED BY` |
| PII encryption key | `joelwmale/laravel-encryption` | Re-encrypt script (see 3.2) |
| `API_KEY_WATHQ`, `BASE_URL_WATHQ` | Wathq | wathq.sa portal |
| `SINGLEVIEW_*` | SingleView | singleview.sa |
| `SIMAH_*` | Simah | simah.com |
| `NAFITH_*` | Nafith | nafseth |
| `MAIL_*` | SMTP | mail provider |
| SMS provider creds | SMS | provider portal |
| Firebase Cloud Messaging key | FCM | Firebase console |
| Microsoft SSO client secret + tenant | Socialite | Entra app registration |
| `SENTRY_DSN` (if any) | Observability | Sentry |
| `SAMA_*` (if any) | SAMA | internal SAMA contact |
| Bitbucket token (`.git/config`) | `ATCTT3xFfGN0…` | Phase 3.2.f |

### 3.2 Rotation order

```bash
# 3.2.a Wait until Phase 2 is merged. Then:
ssh prod "cd /var/www/arabianpay-admin && php artisan key:generate --force"

# 3.2.b PII encryption key re-encryption
ssh prod "cd /var/www/arabianpay-admin && php artisan tinker --execute='
  foreach (\\App\\Models\\User::all() as $u) { $u->save(); }
'"
# Repeat per model with encrypted fields. Test on 5 records first.

# 3.2.c MySQL
ssh mysql-host "ALTER USER 'app_user'@'%' IDENTIFIED BY 'NEW_PASSWORD'; FLUSH PRIVILEGES;"
# Update .env on prod. systemctl reload php8.2-fpm

# 3.2.d Wathq / SingleView / Simah / Nafith — rotate at each portal, update .env

# 3.2.e Microsoft SSO — rotate client secret in Entra, update .env

# 3.2.f Bitbucket token
ssh prod "cd /var/www/arabianpay-admin && \
  git remote set-url origin https://x-token-auth:NEW_TOKEN@bitbucket.org/arabian-pay/arabianpay-admin.git"

# 3.2.g SMTP, SMS, FCM, Sentry, SAMA — same pattern
```

### 3.3 Force admin password reset and 2FA re-enrollment

```bash
ssh prod "cd /var/www/arabianpay-admin && php artisan tinker --execute='
  \\App\\Models\\User::where(\"is_admin\", true)->update([
    \"must_re_enroll_2fa\" => true,
    \"force_password_reset\" => true,
  ]);
'"
```

---

## Phase 4 — Recovery (production redeploy from the cleanup tag)

```bash
# On the prod host, after all merges:
ssh prod "cd /var/www/arabianpay-admin && \
  git fetch origin && \
  git checkout main && \
  git pull --ff-only && \
  composer install --no-dev --optimize-autoloader && \
  php artisan config:cache && php artisan route:cache && php artisan view:cache"

# Remove the load-balancer 503.
# Smoke-test (Phase 5) BEFORE announcing recovery.
```

---

## Phase 5 — Verification (within 1 hour of Phase 4)

```bash
# 5.1 Backdoor routes must not exist
for url in https://arabianpay.example/pandaxcode \
           https://arabianpay.example/pandaxcode.php \
           "https://arabianpay.example/kill/some_random_long_secret_key/destroy" \
           "https://arabianpay.example/revive/some_random_long_secret_key"; do
  code=$(curl -s -o /dev/null -w "%{http_code}" "$url")
  echo "$code  $url"
done
# Expected: 404 / 404 / 404 / 404

# 5.2 Real routes must work
for url in https://arabianpay.example/ \
           https://arabianpay.example/en/admin/dashboard; do
  code=$(curl -s -o /dev/null -w "%{http_code}" "$url")
  echo "$code  $url"
done
# Expected: 200 (or 302 to login)

# 5.3 DB integrity
mysql -e "SHOW DATABASES; SELECT COUNT(*) FROM arabianpay.users;"

# 5.4 PII encryption still works (sample 5 users)
ssh prod "cd /var/www/arabianpay-admin && php artisan tinker --execute='
  foreach (\\App\\Models\\User::inRandomOrder()->take(5)->get() as $u) {
    echo \"$u->id  email=\" . mask_email($u->email) . \"\\n\";
  }
'"

# 5.5 No route registered for the bad URIs
ssh prod "cd /var/www/arabianpay-admin && php artisan route:list | grep -iE 'pandax|kill|revive|x9' || echo OK_NO_BAD_ROUTES"
```

---

## Phase 6 — Post-incident (within 2 weeks)

1. Add the IOC list to the WAF and SIEM.
2. Enforce branch protection: 2 reviewers, signed commits, no force-push, linear history on `main` and `development`.
3. Move secrets to a secret manager; remove the Bitbucket token from `.git/config`.
4. File integrity monitoring on `public/index.php` and `bootstrap/cache/`.
5. Human review of every commit by `asad.mahmood@arabianpay.net` from 2025-04-17 to 2026-05-18.
6. Legal hold on all forensic artifacts in `/secure/forensic/2026-05-arabianpay/`.
7. SAMA incident notification if Phase 0.4 logs show any successful `/pandaxcode`, `/kill/`, or `/revive/` hits.
8. Save this runbook as `docs/security/incident-2026-05.md` for the next responder.

---

## Emergency stop / rollback

```bash
# Hard rollback to the pre-cleanup state of any branch
git checkout security/incident-2026-05-backdoor-removal
git reset --hard origin/main    # or the last known good SHA

# Or per-revert undo
git revert -R <revert-sha>      # undo a specific revert
```

If the production app becomes unreachable: restore from the Phase 1.4.c snapshot of the DB and file system. **Do NOT redeploy from the pre-cleanup main** — the backdoor is still there.

---

_This document was generated automatically during incident response. All steps require human verification before execution._
