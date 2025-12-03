<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpVerificationController extends Controller
{
    const PHONE = '0506879195';
    const COOLDOWN_SECONDS = 60;
    const MAX_ATTEMPTS = 5;

    public function send()
    {
        $key = 'send-otp:' . self::PHONE;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'otp' => "Please wait {$seconds}s before requesting another OTP."
            ]);
        }

        RateLimiter::hit($key, self::COOLDOWN_SECONDS);

        $activeOtp = Otp::where('phone', self::PHONE)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($activeOtp) {
            return redirect()->route('risk.merchantScore')->with('error', 'An active OTP is already pending. Please wait or use that one.');
        }

        Otp::where('phone', self::PHONE)->update(['used' => true]);

        $code = rand(100000, 999999);
        $expiresAt = now()->addMinutes(12);

        Otp::create([
            'phone' => self::PHONE,
            'code' => $code,
            'expires_at' => $expiresAt,
            'sends' => 1,
        ]);

        $this->sendSmsOtp(self::PHONE, $code, "Your OTP code is: {$code}");

        return redirect()
            ->route('otp.verify.form', ['phone' => self::PHONE])
            ->with('status', 'OTP sent to ' . self::PHONE);
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

    protected function sendSms($phone, $message)
    {
        $post = [
            "userName"   => "Arabianpay",
            "apiKey"     => "d99970b46c8430547b33815c20b68d41",
            "userSender" => "Arabianpay",
            "msg"        => $message,
            "numbers"    => $phone,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://www.msegat.com/gw/sendsms.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($post),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        curl_exec($curl);
        curl_close($curl);
    }

    public function sendSmsOtp(array|string $phones, string $otp, ?string $message = null): array
    {
        $phones = is_array($phones) ? $phones : [$phones];
        $message = $message ?? "Your OTP is: {$otp}";

        $postData = [
            "src"   => "Arabianpay",
            "dests" => $phones,
            "body"  => $message,
        ];

        try {
            $response = Http::withToken('EGE4CF3dD_Q6yXGnnMRJ')
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
}
