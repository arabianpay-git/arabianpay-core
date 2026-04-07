<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * [PHASE-0 2026-04-06] Removed hardcoded phone number (F-026) and
 * hardcoded SMS API credentials. Phone is now derived from the
 * authenticated user. SMS credentials moved to config/services.php.
 */
class OtpVerificationController extends Controller
{
    const COOLDOWN_SECONDS = 60;
    const MAX_ATTEMPTS = 5;

    public function send(Request $request)
    {
        $phone = $this->resolvePhone($request);
        $key = 'send-otp:' . $phone;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'otp' => "Please wait {$seconds}s before requesting another OTP."
            ]);
        }

        RateLimiter::hit($key, self::COOLDOWN_SECONDS);

        $activeOtp = Otp::where('phone', $phone)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($activeOtp) {
            return redirect()->route('risk.merchantScore')->with('error', 'An active OTP is already pending. Please wait or use that one.');
        }

        Otp::where('phone', $phone)->update(['used' => true]);

        $code = rand(100000, 999999);
        $expiresAt = now()->addMinutes(12);

        Otp::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => $expiresAt,
            'sends' => 1,
        ]);

        $this->sendSmsOtp($phone, $code, "Your OTP code is: {$code}");

        return redirect()
            ->route('otp.verify.form', ['phone' => $phone])
            ->with('status', 'OTP sent to ' . $phone);
    }

    public function showVerifyForm(Request $request)
    {
        $otp = Otp::active()
            ->where('phone', $request->phone)
            ->latest()
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'code' => 'Your OTP expired. Please request a new one.'
            ]);
        }

        $secondsUntilExpire = max(0, Carbon::now()->diffInSeconds($otp->expires_at));

        return view('admin.risk-management.verify_otp', [
            'phone' => $request->phone,
            'secondsUntilExpire' => $secondsUntilExpire,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|digits_between:8,15',
            'code'  => 'required|digits:6'
        ]);

        $otp = Otp::active()
            ->where('phone', $request->phone)
            ->latest()
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'code' => 'OTP expired or invalid'
            ]);
        }

        $otp->increment('attempts');

        if ($otp->attempts > self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'Too many wrong attempts. Please request a new OTP.'
            ]);
        }

        if ($otp->code !== $request->code) {
            throw ValidationException::withMessages([
                'code' => 'Incorrect OTP'
            ]);
        }

        $otp->forceFill(['used' => true])->save();

        return redirect()->route('risk.merchantScore')->with('success', 'OTP verified. You may now update the score.');
    }

    public function sendSmsOtp(array|string $phones, string $otp, ?string $message = null): array
    {
        $phones = is_array($phones) ? $phones : [$phones];
        $message = $message ?? "Your OTP is: {$otp}";

        $token = config('services.oursms.token');

        if (empty($token)) {
            Log::error('[PHASE-0] OURSMS_API_TOKEN not configured, cannot send OTP SMS');
            throw new \RuntimeException('SMS service not configured.');
        }

        $postData = [
            "src"   => config('services.oursms.sender', 'Arabianpay'),
            "dests" => $phones,
            "body"  => $message,
        ];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post('https://api.oursms.com/msgs/sms', $postData);

            if (!$response->successful()) {
                Log::error('SMS OTP sending failed', ['response' => $response->body()]);
                throw new \RuntimeException('SMS OTP sending failed: ' . substr($response->body(), 0, 500));
            }

            return [
                'otp' => $otp,
                'response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('SMS OTP sending exception', [
                'error' => $e->getMessage(),
                'phones' => $phones
            ]);
            throw $e;
        }
    }

    /**
     * Resolve the phone number from the authenticated user.
     */
    private function resolvePhone(Request $request): string
    {
        $user = $request->user();

        if (! $user || empty($user->phone_number)) {
            Log::warning('[PHASE-0] OTP send attempted without authenticated user phone', [
                'ip' => $request->ip(),
            ]);
            abort(403, 'Authenticated user with phone number required.');
        }

        return $user->phone_number;
    }
}
