<?php

namespace App\Providers;

use App\Services\TokenEncryptionService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class AppServiceProvider extends ServiceProvider
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
        // Set default locale
        App::setLocale('ar');

        // If you also use LaravelLocalization
        if (class_exists(LaravelLocalization::class)) {
            LaravelLocalization::setLocale('ar');
        }

        RateLimiter::for('global', function (Request $request) {
            // Identify the user by authenticated user ID or IP
            $identifier = optional($request->user())->id ?: $request->ip();

            // Sliding window: 100 requests per minute
            $sliding = Limit::perMinute(25)
                ->by($identifier)
                ->response(fn() => response()->json([
                    'message' => 'Too many requests. Slow down and try again later.'
                ], 429));

            // Burst window: 200 requests every 5 minutes
            $burst = Limit::perMinutes(5, 100)
                ->by($identifier)
                ->response(fn() => response()->json([
                    'message' => 'Burst limit exceeded. Please wait before retrying.'
                ], 429));

            return [$sliding, $burst];

            // ------------ EMAIL SEND RATE LIMITER ------------
            RateLimiter::for('send-email', function (Request $request) {
                $identifier = optional($request->user())->id ?: $request->ip();
                return Limit::perHour(10)
                    ->by($identifier)
                    ->response(fn() => response()->json([
                        'message' => 'Email send limit reached. Please try again later.'
                    ], 429));
            });

            // ------------ JETSTREAM / FORTIFY THROTTLES ------------
            // Email verification link resend: max 3 per hour
            RateLimiter::for('verification', function (Request $request) {
                $identifier = optional($request->user())->id ?: $request->ip();
                return Limit::perHour(3)
                    ->by($identifier)
                    ->response(fn() => back()->withErrors(['email' => 'Too many verification emails sent. Try again later.']));
            });

            // Password reset (forgot password email): max 5 per hour
            RateLimiter::for('password-reset', function (Request $request) {
                $identifier = $request->email . '|' . $request->ip();
                return Limit::perHour(5)
                    ->by($identifier)
                    ->response(fn() => back()->withErrors(['email' => 'Too many password reset requests. Try again later.']));
            });
        });
    }
}
