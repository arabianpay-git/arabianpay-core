<?php

// [PHASE-0 2026-04-06] API tester routes removed (F-003 SSRF vulnerability).
// ApiTesterController deleted. These routes are permanently disabled.

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::any('/api-tester', function () {
    Log::warning('[PHASE-0] Blocked request to disabled /api-tester endpoint', [
        'ip' => request()->ip(),
        'user_id' => auth()->id(),
    ]);
    abort(404);
})->name('api.tester');

Route::any('/api-tester/send', function () {
    Log::warning('[PHASE-0] Blocked request to disabled /api-tester/send endpoint', [
        'ip' => request()->ip(),
        'user_id' => auth()->id(),
    ]);
    abort(404);
})->name('api.tester.send');
