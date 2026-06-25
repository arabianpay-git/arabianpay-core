<?php

namespace App\Providers;

use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use App\Models\RefundRequest;
use App\Models\Setting;
use App\Policies\RefundRequestPolicy;
use App\Services\AuditTrailService;
use App\Support\CspNonce;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('audit-trail', function ($app) {
            return new AuditTrailService($app['request']);
        });

        // CORE-P0-10: request-scoped CSP nonce.
        $this->app->singleton(CspNonce::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::aliasMiddleware('ensure.two-factor', EnsureTwoFactorIsEnabled::class);

        Gate::policy(RefundRequest::class, RefundRequestPolicy::class);

        // CORE-P0-10: expose the per-request CSP nonce to Blade as @cspNonce.
        // Usage: <script @cspNonce>…</script>  →  <script nonce="…">…</script>
        Blade::directive('cspNonce', fn () => "<?php echo 'nonce=\"'.e(app(\\App\\Support\\CspNonce::class)->value()).'\"'; ?>");

        // Get default language from settings.
        // Wrapped in try/catch: during tests (RefreshDatabase) migrations haven't
        // run yet when the service provider boots, so the settings table may not exist.
        try {
            $general = settings('general', []);
            $locale = $general['default_language'] ?? config('app.locale', 'en');

            // Set Laravel locale
            App::setLocale($locale);

            // If LaravelLocalization is installed, set its locale too
            if (class_exists(LaravelLocalization::class)) {
                LaravelLocalization::setLocale($locale);
            }

            $general = Setting::getByKey('general', []);
            if (! empty($general['timezone'])) {
                Config::set('app.timezone', $general['timezone']);
                date_default_timezone_set($general['timezone']);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Table does not exist yet (fresh migration / test bootstrap). Use defaults.
        }

        RateLimiter::for('global', function (Request $request) {
            // Identify the user by authenticated user ID or IP
            $identifier = optional($request->user())->id ?: $request->ip();

            // Sliding window: 100 requests per minute
            $sliding = Limit::perMinute(60)
                ->by($identifier)
                ->response(fn () => response()->json([
                    'message' => 'Too many requests. Slow down and try again later.',
                ], 429));

            // Burst window: 200 requests every 5 minutes
            $burst = Limit::perMinutes(5, 100)
                ->by($identifier)
                ->response(fn () => response()->json([
                    'message' => 'Burst limit exceeded. Please wait before retrying.',
                ], 429));

            return [$sliding, $burst];
        });

        // ------------ EMAIL SEND RATE LIMITER ------------
        RateLimiter::for('send-email', function (Request $request) {
            $identifier = optional($request->user())->id ?: $request->ip();

            return Limit::perHour(10)
                ->by($identifier)
                ->response(fn () => response()->json([
                    'message' => 'Email send limit reached. Please try again later.',
                ], 429));
        });

        // ------------ JETSTREAM / FORTIFY THROTTLES ------------
        // Email verification link resend: max 3 per hour
        RateLimiter::for('verification', function (Request $request) {
            $identifier = optional($request->user())->id ?: $request->ip();

            return Limit::perHour(3)
                ->by($identifier)
                ->response(fn () => back()->withErrors(['email' => 'Too many verification emails sent. Try again later.']));
        });

        // Password reset (forgot password email): max 5 per hour
        RateLimiter::for('password-reset', function (Request $request) {
            $identifier = $request->email.'|'.$request->ip();

            return Limit::perHour(5)
                ->by($identifier)
                ->response(fn () => back()->withErrors(['email' => 'Too many password reset requests. Try again later.']));
        });

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', MicrosoftProvider::class);
        });

        // The default `current_password` rule uses Auth::guard()->validate() which
        // queries users by email. Emails are encrypted at rest in this application,
        // so that lookup always fails. Override the rule to verify the password
        // hash directly against the authenticated user.
        Validator::extend('current_password', function ($attribute, $value, $parameters, $validator) {
            $guard = $parameters[0] ?? null;
            $user = auth($guard)->user();

            return $user && filled($user->password) && Hash::check($value, $user->password);
        }, __('The provided password does not match your current password.'));
    }
}
