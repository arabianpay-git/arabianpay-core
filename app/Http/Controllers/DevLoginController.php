<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * TEMPORARY DEV-ONLY LOGIN CONTROLLER
 * 
 * ⚠️ WARNING: This controller bypasses authentication for local development only!
 * DELETE THIS FILE before deploying to production!
 * 
 * Purpose: Allows login with email only (no password) for local testing
 * when passkey and Microsoft login are unavailable.
 */
class DevLoginController extends Controller
{
    /**
     * Show the dev login form
     */
    public function showLoginForm()
    {
        // Only allow in local environment
        if (!app()->environment('local')) {
            abort(404);
        }

        return view('auth.dev-login');
    }

    /**
     * Handle dev login (email only, no password)
     */
    public function login(Request $request)
    {
        // Only allow in local environment
        if (!app()->environment('local')) {
            abort(404);
        }

        $request->validate([
            'email' => 'required|email',
        ]);

        // Find user by encrypted email
        $user = User::whereEncrypted('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'No user found with this email address.',
            ]);
        }

        // Log the dev login
        Log::warning('DEV LOGIN BYPASS USED', [
            'user_id' => $user->id,
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        // Login without password verification
        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
