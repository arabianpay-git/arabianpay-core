<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('products:notify-low-stock')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('products:notify-low-stock')->everyMinute()->withoutOverlapping();

// Schedule::command('process:scheduled-payments')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('process:scheduled-payments')->everyMinute()->withoutOverlapping();

Schedule::command('send:scheduled-payment-reminders')->everyMinute()->withoutOverlapping();

/*
 * Daily financial reconciliation (SAMA MVC §5).
 *
 * Runs at 02:30 local time (after day-close but before business hours).
 * onOneServer: safe to deploy to a multi-node fleet without duplicate runs.
 * withoutOverlapping: belt-and-braces; the job should finish in seconds
 * but we guard against pathological slow days.
 */
Schedule::command('reconciliation:daily')
    ->dailyAt('02:30')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();
