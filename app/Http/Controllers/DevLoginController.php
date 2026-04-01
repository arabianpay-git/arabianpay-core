<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Local-environment-only login using email + password (same verification as Fortify).
 * Routes return 404 when APP_ENV is not local.
 */
class DevLoginController extends Controller
{
    /**
     * Show the dev login form
     */
    public function showLoginForm()
    {
        if (! app()->environment('local')) {
            abort(404);
        }

        return view('auth.dev-login');
    }

    /**
     * Handle dev login (email + password, matches Fortify::authenticateUsing).
     */
    public function login(Request $request)
    {
        if (! app()->environment('local')) {
            abort(404);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::whereEncrypted('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        Log::warning('DEV LOGIN (email/password) USED', [
            'user_id' => $user->id,
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
