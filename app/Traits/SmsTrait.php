<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait SmsTrait
{
    /**
     * Send SMS via OurSMS API
     *
     * @return array
     */
    protected function sendSmsViaOurSms(array|string $phones, string $message)
    {
        if (is_string($phones)) {
            $phones = [$phones];
        }

        $postData = [
            'src' => 'Arabianpay',
            'dests' => $phones,
            'body' => $message,
        ];

        $response = Http::withToken('EGE4CF3dD_Q6yXGnnMRJ')
            ->acceptJson()
            ->post('https://api.oursms.com/msgs/sms', $postData);

        return $response->successful()
            ? $response->json()
            : ['error' => $response->body()];
    }

    /**
     * Send order SMS notification
     *
     * @return bool
     */
    protected function sendOrderSms(string $phoneNumber, string $message)
    {
        try {
            // Format phone number if needed
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            // Send SMS via OurSMS API
            $result = $this->sendSmsViaOurSms($formattedPhone, $message);

            if (isset($result['error'])) {
                Log::error("SMS send failed to {$formattedPhone}", [
                    'error' => $result['error'],
                    'message' => $message,
                ]);

                return false;
            }

            Log::info("SMS sent successfully to {$formattedPhone}", [
                'message_id' => $result['id'] ?? null,
                'message' => $message,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error("SMS send exception to {$phoneNumber}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Format phone number for SMS API
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Handle Saudi numbers: convert 05xxxxxxxx to +9665xxxxxxxx
        if (strlen($phone) == 10 && str_starts_with($phone, '05')) {
            return '+966'.substr($phone, 1);
        }

        // If already starts with +, return as is
        if (str_starts_with($phoneNumber, '+')) {
            return $phoneNumber;
        }

        // If no country code, assume Saudi
        if (strlen($phone) == 9 && str_starts_with($phone, '5')) {
            return '+966'.$phone;
        }

        // Default: return as is
        return $phoneNumber;
    }

    /**
     * Send bulk SMS to multiple numbers
     */
    protected function sendBulkSms(array $phoneNumbers, string $message): array
    {
        $results = [];

        foreach ($phoneNumbers as $phone) {
            $results[$phone] = $this->sendOrderSms($phone, $message);
        }

        return $results;
    }
}
