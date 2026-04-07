<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Features;

trait RedirectsToTwoFactorChallenge
{
    protected function requiresTwoFactorChallenge(User $user): bool
    {
        if (! Features::enabled(Features::twoFactorAuthentication())) {
            return false;
        }

        return $user->hasEnabledTwoFactorAuthentication();
    }

    /**
     * Store pending login for Fortify two-factor challenge (same session keys as Fortify).
     */
    protected function beginTwoFactorChallenge(Request $request, User $user): void
    {
        $request->session()->put([
            'login.id' => $user->getKey(),
            'login.remember' => $request->boolean('remember'),
        ]);

        TwoFactorAuthenticationChallenged::dispatch($user);
    }

    protected function redirectToTwoFactorChallenge(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $this->beginTwoFactorChallenge($request, $user);

        return redirect()->route('two-factor.login');
    }
}
