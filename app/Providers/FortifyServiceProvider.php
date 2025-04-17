<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\LoginAttempt;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $username = $request->input(Fortify::username());
            $ip = $request->ip();
            $key = Str::transliterate(Str::lower($username . '|' . $ip));

            $attempt = LoginAttempt::firstOrNew(['ip_address' => $ip]);
            $now = now();

            if ($attempt->locked_until == 10) {
                return Limit::perMinutes(1, 0)
                    ->by($key)
                    ->response(function () {
                        return response()->json([
                            'message' => '🚫 Access to this service is currently restricted due to repeated failed attempts.'
                        ], Response::HTTP_TOO_MANY_REQUESTS);
                    });
            }

            if ($attempt->locked_until && Carbon::parse($attempt->locked_until)->gt($now)) {
                $remaining = Carbon::parse($attempt->locked_until)->diffInSeconds($now);
                $minutes = floor($remaining / 60);
                $seconds = $remaining % 60;

                return Limit::perMinutes(1, 0)
                    ->by($key)
                    ->response(function () use ($minutes, $seconds) {
                        return response()->json([
                            'message' => "⏳ Too many attempts. Please wait {$minutes} minute(s) and {$seconds} second(s) before trying again."
                        ], Response::HTTP_TOO_MANY_REQUESTS);
                    });
            }
            $attempt->attempts += 1;
            $attempt->last_attempt_at = $now;

            if ($attempt->attempts >= 5 && $attempt->attempts < 8) {
                if ($attempt->locked_until && Carbon::parse($attempt->locked_until)->lt($now)) {
                    $attempt->locked_until = now()->addMinutes(5);
                } else {
                    $attempt->locked_until = now()->addMinutes(5);
                }
            } elseif ($attempt->attempts >= 8 && $attempt->attempts < 11) {
                $attempt->locked_until = now()->addMinutes(10);
            } elseif ($attempt->attempts >= 10) {
                $attempt->locked_until = now()->addMinutes(10);
            }

            $attempt->save();

            return Limit::perMinute(5)->by($key);
        });


        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
