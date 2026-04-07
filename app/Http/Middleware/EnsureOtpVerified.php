<?php

namespace App\Http\Middleware;

use App\Models\Otp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class EnsureOtpVerified
{
    /**
     * Redirect to OTP process if not verified yet.
     *
     * [PHASE-0 2026-04-06] Removed hardcoded phone number bypass (F-026).
     * Now uses the authenticated user's phone_number instead.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->phone_number) {
            Log::warning('[PHASE-0] OTP verification failed: no authenticated user or phone', [
                'ip' => $request->ip(),
                'user_id' => $user?->id,
            ]);
            abort(403, 'Phone number required for OTP verification.');
        }

        $phone = $user->phone_number;

        // Get the latest OTP
        $otp = Otp::where('phone', $phone)->latest()->first();

        // If there's a valid used OTP (i.e., verified), continue
        if ($otp && $otp->used && $otp->expires_at > now()) {
            $otp->forceFill(['expires_at' => Carbon::now()])->save();
            return $next($request);
        }

        // If there's an active unverified OTP, go to verify screen
        if ($otp && !$otp->used && $otp->expires_at > now()) {
            return redirect()->route('otp.verify.form', ['phone' => $phone])
                ->with('error', 'Please verify OTP first.');
        }

        // If no valid OTP, redirect to send a new one
        return redirect()->route('otp.send');
    }
}
