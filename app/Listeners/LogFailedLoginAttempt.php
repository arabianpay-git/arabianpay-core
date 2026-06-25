<?php

namespace App\Listeners;

use App\Models\LoginAttempt;
use Illuminate\Auth\Events\Failed;

class LogFailedLoginAttempt
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $ip = request()->ip();
        $attempt = LoginAttempt::firstOrNew(['ip_address' => $ip]);
        $now = now();

        $attempt->attempts += 1;
        $attempt->last_attempt_at = $now;

        if ($attempt->attempts >= 5 && $attempt->attempts < 8) {
            $attempt->locked_until = $now->addMinutes(5);
        } elseif ($attempt->attempts >= 8 && $attempt->attempts < 11) {
            $attempt->locked_until = $now->addMinutes(10);
        } elseif ($attempt->attempts >= 11) {
            $attempt->locked_until = $now->addMinutes(15); // progressive lock
        }

        $attempt->save();
    }
}
