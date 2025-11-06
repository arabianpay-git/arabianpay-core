<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomLoginRateLimiter
{
    protected $maxAttemptsFirstStage = 5;  // 5 attempts before 5 minutes cooldown
    protected $maxAttemptsSecondStage = 3; // 3 more attempts before 15 minutes cooldown
    protected $maxAttemptsThirdStage = 2;  // 2 more attempts before blocking the IP

    protected $blockDurationFirstStage = 5; // in minutes
    protected $blockDurationSecondStage = 15; // in minutes

    /**
     * Increment the login attempts for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function increment(Request $request)
    {
        $ip = $request->ip();

        // Check if the IP exists in the database
        $attempt = DB::table('login_attempts')->where('ip_address', $ip)->first();

        if ($attempt) {
            // Update the attempts and last attempt timestamp
            DB::table('login_attempts')
                ->where('ip_address', $ip)
                ->update([
                    'attempts' => $attempt->attempts + 1,
                    'last_attempt_at' => now(),
                ]);
        } else {
            // Insert a new record if it's the first attempt
            DB::table('login_attempts')->insert([
                'ip_address' => $ip,
                'attempts' => 1,
                'last_attempt_at' => now(),
                'locked_until' => null,
            ]);
        }
    }

    /**
     * Check if the user has too many failed login attempts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function tooManyAttempts(Request $request)
    {
        $ip = $request->ip();
        $attempt = DB::table('login_attempts')->where('ip_address', $ip)->first();

        if (!$attempt) {
            return false;
        }

        // Check stages
        if ($attempt->attempts >= $this->maxAttemptsFirstStage && !$attempt->locked_until) {
            return true;  // Give 5 minutes after 5 wrong attempts
        }

        if ($attempt->attempts >= $this->maxAttemptsFirstStage + $this->maxAttemptsSecondStage && !$attempt->locked_until) {
            return true;  // Give 15 minutes after 8 wrong attempts
        }

        if ($attempt->attempts >= $this->maxAttemptsFirstStage + $this->maxAttemptsSecondStage + $this->maxAttemptsThirdStage) {
            return true;  // Block after 10 wrong attempts
        }

        return false;
    }

    /**
     * Lock the IP for a certain duration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function lock(Request $request)
    {
        $ip = $request->ip();
        $attempt = DB::table('login_attempts')->where('ip_address', $ip)->first();

        if ($attempt->attempts >= $this->maxAttemptsFirstStage + $this->maxAttemptsSecondStage) {
            // If second stage, lock for 15 minutes
            $lockDuration = $this->blockDurationSecondStage;
        } else {
            // If first stage, lock for 5 minutes
            $lockDuration = $this->blockDurationFirstStage;
        }

        // Update the IP lock time
        DB::table('login_attempts')
            ->where('ip_address', $ip)
            ->update(['locked_until' => now()->addMinutes($lockDuration)]);
    }

    /**
     * Check if the user is currently blocked.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function isBlocked(Request $request)
    {
        $ip = $request->ip();
        $attempt = DB::table('login_attempts')->where('ip_address', $ip)->first();

        if ($attempt && $attempt->locked_until && now()->lt($attempt->locked_until)) {
            return true;  // Blocked IP
        }

        return false;
    }

    /**
     * Clear the login attempts for the given IP.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function clear(Request $request)
    {
        $ip = $request->ip();
        DB::table('login_attempts')->where('ip_address', $ip)->delete();
    }
}
