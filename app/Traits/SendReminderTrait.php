<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

trait SendReminderTrait
{
    /**
     * Send SMS via OurSMS API
     */
    protected function sendSmsViaOurSms(array|string $phones, string $message)
    {
        if (is_string($phones)) {
            $phones = [$phones];
        }

        $postData = [
            "src"   => "Arabianpay",
            "dests" => $phones,
            "body"  => $message,
        ];

        $response = Http::withToken('EGE4CF3dD_Q6yXGnnMRJ')
            ->acceptJson()
            ->post('https://api.oursms.com/msgs/sms', $postData);

        return $response->successful()
            ? $response->json()
            : ['error' => $response->body()];
    }

    /**
     * Send email reminder
     */
    protected function sendEmail(string $email, string $message)
    {
        Mail::raw($message, function ($mail) use ($email) {
            $mail->to($email)
                ->subject('Payment Reminder');
        });
    }
}
