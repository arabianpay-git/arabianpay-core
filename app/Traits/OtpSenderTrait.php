<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait OtpSenderTrait
{
    /**
     * Send OTP via SMS
     */
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
            $response = Http::withToken(config('services.oursms.token'))
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
     * Send OTP via Email
     */
    public function sendEmailOtp(string $email, string $otp, string $subject = 'Your OTP Code', ?string $message = null): array
    {
        $messageText = $message ?? "Your OTP code is {$otp}. This OTP will expire in 10 minutes.";

        $html = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f7f7f7;'>
                <div style='max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px;'>
                    <h2 style='color: #333;'>OTP Verification</h2>
                    <p>Hello,</p>
                    <p>{$messageText}</p>
                    <div style='font-size: 24px; font-weight: bold; margin: 10px 0; color: #1a73e8;'>{$otp}</div>
                    <p>If you did not request this code, please ignore this email.</p>
                    <hr style='margin: 20px 0; border-color: #eee;'>
                    <p style='font-size: 12px; color: #999;'>Arabianpay &copy; " . date('Y') . "</p>
                </div>
            </div>
        ";

        try {
            Mail::html($html, function ($messageMail) use ($email, $subject) {
                $messageMail->to($email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return ['otp' => $otp, 'status' => 'sent'];
        } catch (\Throwable $e) {
            Log::error('Email OTP sending failed', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            throw $e;
        }
    }
}
