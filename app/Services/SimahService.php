<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\RequestException;

class SimahService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    // HTTP timeout in seconds
    protected int $timeout;

    // Retry attempts and interval in ms
    protected int $retryAttempts;
    protected int $retryInterval;

    // Cache key for token
    protected string $cacheKey = 'simah_token';
    protected int $cacheTtl; // seconds

    public function __construct()
    {
        $this->baseUrl = rtrim(config('simah.base_url'), '/');
        $this->username = config('simah.username');
        $this->password = config('simah.password');

        // Timeout for slow SIMAH responses
        $this->timeout = (int) config('simah.timeout', 60); // 60 seconds should be enough

        // Retry logic
        $this->retryAttempts = (int) config('simah.retry_attempts', 2);
        $this->retryInterval = (int) config('simah.retry_interval', 100); // ms

        // Token cache TTL
        $this->cacheTtl = (int) config('simah.token_ttl', 3500); // ~1 hour
    }

    /**
     * Authenticate with SIMAH and return token.
     */
    protected function authenticate(): string
    {
        $url = $this->baseUrl . '/api/v1/Identity/login';
        $start = microtime(true);

        $response = Http::timeout($this->timeout)
            ->retry($this->retryAttempts, $this->retryInterval)
            ->post($url, [
                'username' => $this->username,
                'password' => $this->password,
            ]);

        Log::info('Simah login request time: ' . round(microtime(true) - $start, 2) . ' seconds');

        if (!$response->successful()) {
            Log::error('Simah authentication failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new RequestException($response);
        }

        $json = $response->json();

        if (empty($json['data']['token'])) {
            Log::error('Simah authentication response missing token', $json);
            throw new \RuntimeException('SIMAH authentication: token missing');
        }

        Cache::put($this->cacheKey, $json['data']['token'], $this->cacheTtl);

        return $json['data']['token'];
    }

    /**
     * Get token from cache or authenticate.
     */
    protected function getToken(): string
    {
        return Cache::remember($this->cacheKey, $this->cacheTtl, function () {
            return $this->authenticate();
        });
    }

    /**
     * Get Silver Report
     */
    public function getSilverReport(array $data): array
    {
        if (empty($data['idNumber'] ?? null)) {
            throw new \InvalidArgumentException('idNumber is required.');
        }

        $body = $this->prepareSilverRequestBody($data);
        $token = $this->getToken();
        $start = microtime(true);

        // Main request with long timeout
        $response = Http::withToken($token)
            ->withOptions([
                'timeout' => 60, // total 60 seconds wait
                'connect_timeout' => 10, // 10 seconds to connect
            ])
            ->retry($this->retryAttempts, $this->retryInterval)
            ->post($this->baseUrl . '/api/v1/enquiry/commercial/silver/report', $body);

        Log::info('Simah getSilverReport request time: ' . round(microtime(true) - $start, 2) . ' seconds');

        // Retry once if token expired
        if ($response->status() === 401) {
            Cache::forget($this->cacheKey);
            $token = $this->getToken();
            $response = Http::withToken($token)
                ->withOptions([
                    'timeout' => 60,
                    'connect_timeout' => 10,
                ])
                ->post($this->baseUrl . '/api/v1/enquiry/commercial/silver/report', $body);
        }

        if (!$response->successful()) {
            Log::error('Simah getSilverReport failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new RequestException($response);
        }

        return $response->json();
    }

    /**
     * Prepare Silver report request body
     */
    protected function prepareSilverRequestBody(array $data): array
    {
        return [
            'basicInfo' => [
                'enquiryTypeId' => $data['enquiryTypeId'] ?? 15,
                'idIssuerID' => $data['idIssuerID'] ?? 'MC',
                'amount' => $data['amount'] ?? 10.99,
                'cityId' => $data['cityId'] ?? 1,
                'productId' => $data['productId'] ?? 139,
                'creditInstrumentId' => $data['creditInstrumentId'] ?? 1,
                'idNumber' => $data['idNumber'],
                'memberRefNo' => $data['memberRefNo'] ?? Str::random(32),
            ],
            'generalInfo' => [
                'expiryDate' => $data['expiryDate'] ?? '30/10/2040',
                'isHijriDate' => $data['isHijriDate'] ?? false,
                'nationality' => $data['nationality'] ?? 196,
                'familyName' => $data['familyName'] ?? 'ABC',
                'firstName' => $data['firstName'] ?? 'ABB',
                'secondName' => $data['secondName'] ?? 'BBC',
                'thirdName' => $data['thirdName'] ?? 'CCD',
                'gender' => $data['gender'] ?? 1,
                'dateOfBirth' => $data['dateOfBirth'] ?? '30/11/1970',
                'isHijriDateOfBirth' => $data['isHijriDateOfBirth'] ?? false,
                'name' => $data['name'] ?? 'ABCDE',
                'legalId' => $data['legalId'] ?? 1,
                'activityId' => $data['activityId'] ?? 1,
                'noOfEmployeesId' => $data['noOfEmployeesId'] ?? 15,
            ],
            'contacts' => $data['contacts'] ?? [[
                'contactType' => 4,
                'areaCode' => '',
                'phoneNumber' => '545232968',
                'extension' => '966',
                'countryCode' => '966',
            ]],
            'addressInfo' => $data['addressInfo'] ?? [
                'addressType' => 6,
                'street' => 'Olaya',
                'buildingNumber' => 99999,
                'zipCode' => 99999,
                'additionalNumber' => 9999,
                'city' => 1,
                'district' => 'Olaya',
            ],
            'isNationalId' => $data['isNationalId'] ?? true,
        ];
    }
}
