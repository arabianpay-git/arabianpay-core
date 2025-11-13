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
     * Charge a token via ClickPay/payment/request
     *
     * @param array $payload (token, cart_id, cart_amount, cart_description, customer_details, shipping_details?)
     * @return array ['success' => bool, 'response' => array|null, 'error' => string|null]
     */
    public function chargeWithToken(array $payload): array
    {
        $body = array_merge([
            'profile_id'    => $this->profile,
            'tran_type'     => 'sale',
            'tran_class'    => 'ecom',
            'cart_currency' => config('services.clickpay.currency', 'SAR'),
            'hide_shipping' => true,
        ], $payload);

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->post($this->base . '/payment/request', $body);

            $json = $response->json();

            // success detection
            if ($response->ok() && (isset($json['code']) && $json['code'] == 0 || (isset($json['isSuccess']) && $json['isSuccess'] === true))) {
                return ['success' => true, 'response' => $json];
            }

            return ['success' => false, 'response' => $json, 'status' => $response->status()];
        } catch (\Throwable $e) {
            Log::error('ClickPay charge error: ' . $e->getMessage(), ['payload' => $body]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
