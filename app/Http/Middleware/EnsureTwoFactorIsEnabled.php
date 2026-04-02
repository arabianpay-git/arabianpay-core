<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsEnabled
{
    /**
     * Block access until the user enables and confirms two-factor authentication (Fortify).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('fortify.mandatory_two_factor', true)) {
            return $next($request);
        }

        if (! Features::enabled(Features::twoFactorAuthentication())) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || $user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => translate('Two-factor authentication is required. Please enable it in your profile.'),
                'redirect' => route('profile.show'),
            ], 403);
        }

        return redirect()
            ->route('profile.show')
            ->with('error', translate('Two-factor authentication is required. Please enable it below before continuing.'));
    }
}
