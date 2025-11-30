<?php

namespace App\Http\Middleware;

use App\Models\Otp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\OtpVerificationController;
use Carbon\Carbon;

class EnsureOtpVerified
{
    /**
     * Redirect to OTP process if not verified yet.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $phone = OtpVerificationController::PHONE;
        $phone = "0545232968";
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
