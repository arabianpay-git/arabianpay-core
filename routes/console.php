<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─────────────────────────────────────────────
// [PHASE-6] Scheduled commands with production-safe intervals
//
// All commands use withoutOverlapping() to prevent duplicate processing.
// Intervals are set for production safety — not everyMinute.
// ─────────────────────────────────────────────

// Process scheduled payments: check for due/overdue payments and charge via ClickPay.
// Every 5 minutes with overlap protection. Uses lockForUpdate() internally.
Schedule::command('process:scheduled-payments')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Send payment reminders: notify customers of upcoming due dates.
// Every 15 minutes — reminders are not time-critical.
Schedule::command('send:scheduled-payment-reminders')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Notify low stock products: alert suppliers about inventory levels.
// Once daily at 9 AM — no need for frequent runs.
Schedule::command('products:notify-low-stock')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();
