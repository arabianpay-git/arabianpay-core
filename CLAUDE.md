# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ArabianPay Admin is an internal admin panel for a Saudi BNPL (Buy Now, Pay Later) fintech platform. It manages merchants, customers, orders, payments, credit/risk assessment, settlements, and compliance. The platform serves both Arabic and English audiences with localized routing.

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.2+), Livewire 3, Jetstream (Livewire stack) with Sanctum auth
- **Frontend:** Blade templates, Tailwind CSS 3, Alpine.js, Vite
- **Real-time:** Laravel Reverb (WebSockets) for chat and presence events
- **Database:** MySQL (production), SQLite in-memory (tests)
- **Key packages:** Spatie Permission (RBAC), Spatie Activity Log, `joelwmale/laravel-encryption` (field-level encryption), `mcamara/laravel-localization` (i18n routing), `maatwebsite/excel` (exports), `barryvdh/laravel-dompdf` (PDF generation), `kreait/laravel-firebase` (push notifications), `spatie/laravel-passkeys`

## Common Commands

```bash
# Full dev environment (server + queue + logs + vite in parallel)
composer dev

# Individual services
php artisan serve
php artisan queue:listen --tries=1
npm run dev

# Tests
php artisan test                          # all tests
php artisan test --filter=SomeTest        # single test class
php artisan test --filter=test_method     # single test method
vendor/bin/phpunit --coverage-text        # with coverage

# Code formatting
vendor/bin/pint                           # PHP (Laravel Pint / PSR-12)
npm run format -- "resources/**/*.blade.php"  # Blade (Prettier + blade plugin)

# Build
npm run build                             # production Vite build

# Migrations
php artisan migrate
php artisan migrate:fresh --seed          # reset + seed
```

## Critical Gotchas

### Encrypted Fields — DO NOT query directly

User PII (email, phone, national_id) is encrypted at rest. Standard Eloquent `where('email', $value)` **will not work** — it compares against ciphertext and always returns null.

```php
// WRONG — always returns null
User::where('email', $email)->first();

// CORRECT — decrypt and compare
User::all()->first(fn ($u) => $u->email === $email);
```

This is slow by design. For lookups by encrypted fields, use a blind index or accept the iteration cost on small result sets.

### Translatable Models — always use the relationship

Models using `astrotomic/laravel-translatable` store translations in companion `*Translation` tables. Never set translatable attributes directly on the base model outside of the translatable API.

```php
// WRONG
$product->name = 'Widget';

// CORRECT
$product->translateOrNew('en')->name = 'Widget';
$product->translateOrNew('ar')->name = 'ويدجت';
$product->save();
```

### Settings are JSON in the DB, not .env

App settings (tax rates, date formats, timezone, etc.) live in the `settings` table as JSON values. Use `get_setting('key')` or `settings('key')` helpers. Do NOT add new `.env` variables for business configuration.

### Localized routes — always use LaravelLocalization

All web URLs are prefixed with locale (`/en/...`, `/ar/...`). When generating URLs in controllers or views, use `route()` (which respects the prefix). Never hardcode `/admin/...` paths without the locale segment.

## Rules

### DO

- Check `app/Helpers/SettingHelper.php` before adding any global helper — most cross-cutting concerns already exist there.
- Use `maskedSensitiveText()` when displaying PII in views — never render raw encrypted fields without permission checks.
- Use `resolveMedia()` / `supplierMedia()` / `productMedia()` for all image URLs — they handle local/remote fallback logic.
- Run `vendor/bin/pint` after editing PHP files.
- Use Form Requests (`app/Http/Requests/`) for validation in controllers.
- Use the `audit()` helper or `AuditTrailService` when adding new state-changing operations on sensitive data or when you been asked to add audit logging.

### DO NOT

- Do not add Eloquent `where()` clauses on encrypted fields (email, phone, national_id). See "Encrypted Fields" above.
- Do not bypass `hasSensitivePermission()` checks when exposing PII.
- Do not add new routes outside the `LaravelLocalization::setLocale()` group unless they are explicitly locale-independent (webhooks, API callbacks).
- Do not put business config in `.env` — use the `settings` table.
- Do not duplicate helper functions — read `SettingHelper.php` first.

## Architecture

### Authentication & Authorization

- **Microsoft SSO** via Socialite (`Auth\MicrosoftController`) is the primary login for employees.
- **Dev login** route exists at `/devlogin` (local env only) — bypasses SSO by email lookup on encrypted user records.
- **Two-factor auth** (Fortify/Jetstream 2FA) enforced via `EnsureTwoFactorIsEnabled` middleware.
- **OTP verification** layer via `EnsureOtpVerified` middleware.
- **Passkeys** (WebAuthn) supported via `spatie/laravel-passkeys`.
- **RBAC**: Spatie Permission with roles and permissions. `CheckAdmin` middleware gates admin routes. Sensitive data access uses a separate `SensitiveDataApproval` model with time-bounded permission grants.

### Routing

- All web routes are wrapped in `LaravelLocalization::setLocale()` prefix for i18n (`/en/admin/...`, `/ar/admin/...`).
- Admin routes require: `auth:sanctum`, `PreventBackHistory`, `SecureHeaders`, `CheckAdmin`, Jetstream auth session, `verified`.
- Settings routes are in a separate `routes/setting.php` file.
- API routes (`routes/api.php`) serve the real-time chat and third-party service test endpoints (SingleView, Simah, Nafith). |

### Middleware Stack (admin routes)

`ThrottleRequests` > `auth:sanctum` > `PreventBackHistory` > `SecureHeaders` > `CheckAdmin` > `AuthenticateSession` > `verified`
