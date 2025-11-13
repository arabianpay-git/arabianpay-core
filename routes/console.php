<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('products:notify-low-stock')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('products:notify-low-stock')->everyMinute()->withoutOverlapping();

Schedule::command('process:scheduled-payments')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('send:scheduled-payment-reminders')->dailyAt('08:00')->withoutOverlapping();
