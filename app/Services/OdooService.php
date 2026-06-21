<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OdooService
{
    protected string $baseUrl;
    protected string $login;
    protected string $password;
    protected int $tokenTtl;

    protected string $tokenCacheKey = 'odoo_token';
    protected string $sessionCacheKey = 'odoo_session_id';

    public function __construct()
    {
        $this->baseUrl = rtrim(config('odoo.base_url'), '/');
        $this->login = config('odoo.login');
        $this->password = config('odoo.password');
        $this->tokenTtl = (int) config('odoo.token_ttl', 3500);
    }

    protected function authenticate(): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/login', [
            'params' => [
                'login' => $this->login,
                'password' => $this->password,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Odoo authentication failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Odoo authentication failed: ' . $response->body());
        }

        $json = $response->json();

        $token = data_get($json, 'result.token');
        if (empty($token)) {
            Log::error('Odoo authentication response missing token', $json ?? []);
            throw new \RuntimeException('Odoo authentication: token missing in response');
        }

        $sessionId = null;
        $setCookie = $response->header('Set-Cookie');
        if ($setCookie && preg_match('/session_id=([^;]+)/', $setCookie, $matches)) {
            $sessionId = $matches[1];
        }

        Cache::put($this->tokenCacheKey, $token, $this->tokenTtl);
        if ($sessionId) {
            Cache::put($this->sessionCacheKey, $sessionId, $this->tokenTtl);
        }

        return ['token' => $token, 'session_id' => $sessionId];
    }

    protected function getCredentials(): array
    {
        $token = Cache::get($this->tokenCacheKey);
        $sessionId = Cache::get($this->sessionCacheKey);

        if ($token) {
            return ['token' => $token, 'session_id' => $sessionId];
        }

        return $this->authenticate();
    }

    protected function getCrData(Merchant $merchant): array
    {
        $data = $merchant->goverment_data;
        if (is_string($data)) {
            return json_decode($data, true) ?? [];
        }
        return is_array($data) ? $data : [];
    }

    public function createVendor(Merchant $merchant): int
    {
        $creds = $this->getCredentials();

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $creds['token'],
        ];

        if ($sessionId = $creds['session_id']) {
            $headers['Cookie'] = 'session_id=' . $sessionId;
        }

        $user = $merchant->user;
        $crData = $this->getCrData($merchant);

        $name = $user->business_name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        $payload = [
            'params' => [
                'name' => $name,
                'is_company' => true,
                'email' => $user->email ?? '',
                'phone' => $user->phone_number ?? '',
                'mobile' => $user->phone_number ?? '',
                'street' => Arr::get($crData, 'address', ''),
                'city' => $user->city->name ?? '',
                'country_id' => 192,
                'vat' => $merchant->vat_register_number ?? '',
                'cr_no' => $merchant->cr_number ?? '',
                'unified_number' => Arr::get($crData, 'unifiedNationalNumber', ''),
                'crm_arabian_code' => 'VND-' . $merchant->id,
                'l10n_sa_edi_building_number' => Arr::get($crData, 'buildingNumber', ''),
                'l10n_sa_edi_plot_identification' => Arr::get($crData, 'plotNumber', ''),
                'l10n_sa_additional_identification_number' => $merchant->cr_number ?? '',
                'l10n_sa_additional_identification_scheme' => 'CRN',
                'payment_term_id' => 1,
                'currency_id' => 162,
            ],
        ];

        $response = Http::withHeaders($headers)
            ->post($this->baseUrl . '/api/create_vendor', $payload);

        if ($response->status() === 401) {
            Cache::forget($this->tokenCacheKey);
            Cache::forget($this->sessionCacheKey);

            $creds = $this->authenticate();
            $headers['Authorization'] = 'Bearer ' . $creds['token'];
            if ($sessionId = $creds['session_id']) {
                $headers['Cookie'] = 'session_id=' . $sessionId;
            }

            $response = Http::withHeaders($headers)
                ->post($this->baseUrl . '/api/create_vendor', $payload);
        }

        if (!$response->successful()) {
            Log::error('Odoo create vendor failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'merchant_id' => $merchant->id,
            ]);
            throw new \RuntimeException('Odoo create vendor failed: ' . $response->body());
        }

        $json = $response->json();
        $vendorId = data_get($json, 'result.vendor_id') ?? data_get($json, 'result.id');

        if (!$vendorId) {
            Log::error('Odoo create vendor response missing vendor ID', $json ?? []);
            throw new \RuntimeException('Odoo create vendor: vendor ID missing in response');
        }

        return (int) $vendorId;
    }
}
