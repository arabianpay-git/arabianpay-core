<?php

namespace App\Traits;

trait SmsSender
{
    protected function sendSms($phone, $message)
    {
        $post = [
            "userName"   => config('services.msegat.username', 'Arabianpay'),
            "apiKey"     => config('services.msegat.api_key'),
            "userSender" => config('services.msegat.sender', 'Arabianpay'),
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
}
