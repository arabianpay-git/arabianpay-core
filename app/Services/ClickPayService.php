<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClickPayService
{
    protected $base;

    protected $profile;

    protected $serverKey;

    public function __construct()
    {
        $this->base = config('services.clickpay.base_url', 'https://secure.clickpay.com.sa');
        $this->profile = config('services.clickpay.profile_id');
        $this->serverKey = config('services.clickpay.server_key');
    }

    /**
     * Charge a saved token for a recurring payment
     *
     * @param  array  $payload  (token, tran_ref, cart_id, cart_amount, cart_description)
     * @return array ['success' => bool, 'response' => array|null, 'error' => string|null]
     */
    public function chargeWithToken(array $payload): array
    {
        $body = array_merge([
            'profile_id' => $this->profile,
            'tran_type' => 'sale',
            'tran_class' => 'recurring',
            'cart_currency' => config('services.clickpay.currency', 'SAR'),
        ], $payload);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->serverKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($this->base.'/payment/request', $body);

            $json = $response->json();

            if ($response->ok() && (
                (isset($json['isSuccess']) && $json['isSuccess'] === true) ||
                (isset($json['tran_ref']) && ! empty($json['tran_ref']))
            )) {
                return [
                    'success' => true,
                    'response' => $json,
                ];
            }

            return [
                'success' => false,
                'response' => $json,
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('ClickPay recurring charge error: '.$e->getMessage(), [
                'payload' => $body,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
