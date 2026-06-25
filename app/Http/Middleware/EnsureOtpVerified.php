<?php

namespace App\Http\Middleware;

use App\Models\Otp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOtpVerified
{
    /**
     * Redirect to OTP process if not verified yet.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Authentication required.');
        }

        $phone = $user->phone_number;

        if (empty($phone)) {
            return redirect()->back()->with('error', 'Your account does not have a verified phone number.');
        }

        // Get the latest OTP
        $otp = Otp::where('phone', $phone)->latest()->first();

        // If there's a valid used OTP (i.e., verified), continue
        if ($otp && $otp->used && $otp->expires_at > now()) {
            $otp->forceFill(['expires_at' => now()->addSeconds(60)])->save();

            return $next($request);
        }

        // If there's an active unverified OTP, go to verify screen
        if ($otp && ! $otp->used && $otp->expires_at > now()) {
            return redirect()->route('otp.verify.form', ['phone' => $phone])
                ->with('error', 'Please verify OTP first.');
        }

        // If no valid OTP, redirect to send a new one
        return redirect()->route('otp.send');
    }
}
