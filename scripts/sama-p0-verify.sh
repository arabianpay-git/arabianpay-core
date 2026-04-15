#!/usr/bin/env bash
set -u
cd /Users/gazitom/Documents/my_project/arabianpay-admin

echo "=================================================="
echo "SAMA P0 Final Verification — Core (arabianpay-admin)"
echo "=================================================="
echo

PASS=0; FAIL=0; WARN=0
pass() { echo "  [PASS] $1"; PASS=$((PASS+1)); }
fail() { echo "  [FAIL] $1"; FAIL=$((FAIL+1)); }
warn() { echo "  [WARN] $1"; WARN=$((WARN+1)); }

echo "--- CORE-P0-01: AuditLogMiddleware registered globally"
if grep -q "AuditLogMiddleware::class" bootstrap/app.php; then
    pass "bootstrap/app.php references AuditLogMiddleware"
else
    fail "AuditLogMiddleware not registered in bootstrap/app.php"
fi

echo
echo "--- CORE-P0-02: Mandatory 2FA"
if grep -q "'mandatory_two_factor' => true" config/fortify.php; then
    pass "fortify.php hardcodes mandatory_two_factor=true"
else
    fail "mandatory_two_factor is not hardcoded to true"
fi
if grep -rqn "BYPASS_TWO_FACTOR_CHALLENGE" app/ 2>/dev/null; then
    fail "Bypass token still referenced somewhere in app/"
else
    pass "No BYPASS_TWO_FACTOR_CHALLENGE references in app/"
fi

echo
echo "--- CORE-P0-03: No hardcoded DB password"
if grep -E "env\('DB_[A-Z_]*PASSWORD[A-Z_]*',\s*'[^']+'\)" config/database.php > /dev/null 2>&1; then
    fail "Hardcoded DB password default still present in config/database.php"
else
    pass "config/database.php has no hardcoded password default"
fi

echo
echo "--- CORE-P0-04: No dd()/dump() in production controllers"
DD_HITS=$(grep -rnE "^\s*(dd|dump|var_dump|die)\s*\(" app/Http/Controllers/ 2>/dev/null | grep -v "^\s*//" | wc -l | tr -d ' ')
if [ "$DD_HITS" = "0" ]; then
    pass "No uncommented dd()/dump() in app/Http/Controllers/"
else
    fail "$DD_HITS uncommented debug calls found in controllers"
fi

echo
echo "--- CORE-P0-05: Debug off + error views"
if grep -q "APP_DEBUG=false" .env.example; then
    pass ".env.example ships APP_DEBUG=false"
else
    fail ".env.example still has APP_DEBUG=true"
fi
for code in 401 405 408 504; do
    f="resources/views/errors/${code}.blade.php"
    if [ -s "$f" ]; then
        pass "error view $code exists and is non-empty"
    else
        fail "error view $code missing or empty"
    fi
done

echo
echo "--- CORE-P0-06: Session encryption enabled"
if grep -q "SESSION_ENCRYPT=true" .env.example; then
    pass ".env.example SESSION_ENCRYPT=true"
else
    fail "SESSION_ENCRYPT not true in .env.example"
fi
if grep -E "env\('SESSION_ENCRYPT', true\)" config/session.php > /dev/null; then
    pass "config/session.php default for SESSION_ENCRYPT is true"
else
    fail "config/session.php default for SESSION_ENCRYPT is not true"
fi

echo
echo "--- CORE-P0-07: CORS strict allow-list"
if grep -q "allowed_origins.*CORS_ALLOWED_ORIGINS" config/cors.php; then
    pass "config/cors.php reads CORS_ALLOWED_ORIGINS env var"
else
    fail "CORS allowlist not wired to env"
fi
if grep -qE "allowed_origins.*\['\*'\]" config/cors.php; then
    fail "Wildcard '*' still present in config/cors.php"
else
    pass "No wildcard origin default in config/cors.php"
fi

echo
echo "--- CORE-P0-08: Sanctum token expiration + refresh endpoint"
if grep -qE "'expiration'\s*=>\s*env\('SANCTUM_EXPIRATION',\s*60\)" config/sanctum.php; then
    pass "config/sanctum.php expiration default = 60"
else
    fail "Sanctum expiration not set to 60"
fi
if php artisan route:list --no-ansi 2>/dev/null | grep -q "api/auth/refresh-token"; then
    pass "POST /api/auth/refresh-token route registered"
else
    fail "Refresh-token route missing"
fi

echo
echo "--- CORE-P0-09: Secret hardening"
if grep -qE "'password'\s*=>\s*env\('SIMAH_PASSWORD'\)\s*," config/simah.php; then
    pass "config/simah.php has no hardcoded SIMAH password default"
else
    fail "SIMAH password default still present"
fi
if [ -f docs/compliance/SECRET_ROTATION_CHECKLIST.md ]; then
    pass "SECRET_ROTATION_CHECKLIST.md present"
else
    fail "SECRET_ROTATION_CHECKLIST.md missing"
fi
if git ls-files | grep -E "^\.env$|\.env\.production$|\.env\.backup$" > /dev/null 2>&1; then
    fail ".env tracked in git"
else
    pass ".env not tracked in git"
fi

echo
echo "--- CORE-P0-10: CSP nonce plumbing"
if [ -f app/Support/CspNonce.php ]; then
    pass "CspNonce service file present"
else
    fail "CspNonce service missing"
fi
if grep -q "Blade::directive('cspNonce'" app/Providers/AppServiceProvider.php; then
    pass "@cspNonce Blade directive registered"
else
    fail "@cspNonce Blade directive missing"
fi
if grep -q "csp_nonce" app/Helpers/SettingHelper.php; then
    pass "csp_nonce() helper present"
else
    fail "csp_nonce() helper missing"
fi
if [ -f docs/compliance/CSP_INLINE_SCRIPT_INVENTORY.md ]; then
    pass "CSP inline script inventory doc present"
else
    fail "CSP inventory doc missing"
fi

echo
echo "--- CORE-P0-11: Daily reconciliation scheduled"
if [ -f app/Services/Finance/ReconciliationService.php ]; then
    pass "ReconciliationService present"
else
    fail "ReconciliationService missing"
fi
if php artisan schedule:list --no-ansi 2>/dev/null | grep -q "reconciliation:daily"; then
    pass "reconciliation:daily is scheduled"
else
    fail "reconciliation:daily not scheduled"
fi

echo
echo "=================================================="
echo "Summary: PASS=$PASS  FAIL=$FAIL  WARN=$WARN"
echo "=================================================="
if [ "$FAIL" = "0" ]; then
    echo "RESULT: ALL P0 CONTROLS VERIFIED"
    exit 0
else
    echo "RESULT: $FAIL failures — review above"
    exit 1
fi
