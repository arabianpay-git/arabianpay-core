<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NafithService
{
    protected $baseUrl;

    protected $authBasicToken;

    protected $signSecret;

    protected $cacheKey = 'nafith_auth_token';

    /**
     * Constructor: Load configuration values.
     */
    public function __construct()
    {
        $this->baseUrl = config('nafith.base_url');
        $this->authBasicToken = config('nafith.credentials.auth_basic_token');
        $this->signSecret = config('nafith.credentials.sign_secret');
    }

    /**
     * Generate current timestamp in milliseconds.
     */
    protected function getTimestamp()
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * Generate a random tracking ID.
     */
    protected function generateTrackingId()
    {
        return (string) Str::uuid();
    }

    /**
     * Utility to forcefully clear the cached authentication token.
     */
    public function clearCachedToken()
    {
        Cache::forget($this->cacheKey);

        return true;
    }

    /**
     * Generate a new Nafith authentication token.
     */
    public function generateAuthToken()
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '.$this->authBasicToken,
            ])->asForm()->post(config('nafith.auth_url'), [
                'grant_type' => config('nafith.defaults.grant_type'),
                'scope' => config('nafith.defaults.scope'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Cache token for 1 minute less than its expiry time
                $expiry = $data['expires_in'] - 60;
                Cache::put($this->cacheKey, $data['access_token'], $expiry);

                return $data['access_token'];
            }

            Log::error('Nafith Auth Failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Nafith Auth Exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get the cached auth token or generate a new one.
     */
    protected function getAuthToken()
    {
        // Cache::remember arguments: key, time (in seconds, 35940s = 9.98h), callback
        return Cache::remember($this->cacheKey, 35940, function () {
            return $this->generateAuthToken();
        });
    }

    /**
     * Normalize a single numeric value for signature consistency (removes decimal if integer).
     */
    protected function normalizeNumber($value)
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $floatValue = (float) $value;

        if ($floatValue == (int) $floatValue) {
            return (int) $floatValue;
        }

        return (float) $floatValue;
    }

    /**
     * Applies numeric normalization specifically to 'total_value' fields in the SANAD data.
     */
    protected function normalizeSanadData(array $sanadData): array
    {
        $normalizedData = $sanadData;

        if (isset($normalizedData['total_value'])) {
            $normalizedData['total_value'] = $this->normalizeNumber($normalizedData['total_value']);
        }

        if (isset($normalizedData['sanad']) && is_array($normalizedData['sanad'])) {
            foreach ($normalizedData['sanad'] as &$sanadItem) {
                if (isset($sanadItem['total_value'])) {
                    $sanadItem['total_value'] = $this->normalizeNumber($sanadItem['total_value']);
                }
            }
            unset($sanadItem);
        }

        return $normalizedData;
    }

    /**
     * Generates the HMAC SHA-256 signature required for Nafith API calls.
     */
    protected function generateSignature($method, $endpoint, $timestamp, $data = [], $objectId = ''): string
    {
        $method = strtoupper($method);

        $endpointPath = parse_url($endpoint, PHP_URL_PATH);

        // JSON encode the data. JSON_UNESCAPED_SLASHES is crucial for matching Nafith's signature process.
        $jsonData = ! empty($data) ? json_encode($data, JSON_UNESCAPED_SLASHES) : '{}';

        $ed = base64_encode($jsonData);

        // Build the string-to-sign (Host is static: nafith.sa)
        // If objectId is empty (e.g., for POST), 'id=' becomes 'id='
        // If objectId is present (e.g., for PATCH), 'id=' becomes 'id={sanadGroupId}'
        $stringToSign = $method."\nnafith.sa\n".$endpointPath."\nid=".$objectId.'&t='.$timestamp.'&ed='.$ed;

        Log::debug('Nafith Signature Generation', [
            'string_to_sign' => $stringToSign,
            'json_data' => $jsonData,
            'timestamp' => $timestamp,
        ]);

        // Generate HMAC SHA256 signature and Base64 encode
        $hash = hash_hmac('sha256', $stringToSign, $this->signSecret, true);
        $signature = base64_encode($hash);

        return $signature;
    }

    /**
     * Sends the request to create a SANAD Group (single or multiple SANADs).
     */
    public function createSanad(array $sanadData)
    {
        try {
            $token = $this->getAuthToken();
            if (! $token) {
                throw new \Exception('Unable to get authentication token');
            }

            $timestamp = $this->getTimestamp();

            $endpoint = $this->baseUrl.config('nafith.endpoints.sanad_group');

            $normalizedSanadData = $this->normalizeSanadData($sanadData);

            $signature = $this->generateSignature('POST', $endpoint, $timestamp, $normalizedSanadData);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token,
                'X-Nafith-Timestamp' => $timestamp,
                'X-Nafith-Tracking-Id' => $this->generateTrackingId(),
                'X-Nafith-Signature' => $signature,
            ])->post($endpoint, $normalizedSanadData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Nafith Create SANAD Failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_data' => $normalizedSanadData,
                'signature' => $signature,
                'timestamp' => $timestamp,
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Nafith Create SANAD Exception', [
                'error' => $e->getMessage(),
                'data' => $sanadData,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function createSingleSanad(array $debtorData, array $sanadItems, $referenceId, $cityOfIssuance = 1)
    {
        $debtorNationalId = (string) Arr::get($debtorData, 'national_id');
        $debtorPhoneNumber = (string) Arr::get($debtorData, 'phone_number');

        $normalizedSanadItems = [];
        $totalValue = 0;
        foreach ($sanadItems as $item) {
            $normalizedItem = $item;
            if (isset($item['total_value'])) {
                $normalizedItem['total_value'] = $this->normalizeNumber($item['total_value']);
            }
            $totalValue += Arr::get($normalizedItem, 'total_value', 0);
            $normalizedSanadItems[] = $normalizedItem;
        }

        $sanadData = [
            'debtor' => [
                'national_id' => $debtorNationalId,
            ],
            'city_of_issuance' => $cityOfIssuance,
            'debtor_phone_number' => $debtorPhoneNumber,
            'total_value' => $this->normalizeNumber($totalValue),
            'currency' => config('nafith.defaults.currency'),
            'max_approve_duration' => config('nafith.defaults.max_approve_duration'),
            'reference_id' => $referenceId,
            'sanad' => $normalizedSanadItems,
        ];

        return $this->createSanad($sanadData);
    }

    public function createMultipleSanads(array $debtorData, array $sanadItems, $referenceId, $cityOfIssuance, $totalValue = null)
    {
        $debtorNationalId = (string) Arr::get($debtorData, 'national_id');
        $debtorPhoneNumber = (string) Arr::get($debtorData, 'phone_number');
        $cityOfIssuanceName = (string) $cityOfIssuance;

        $calculatedTotalValue = 0;
        $normalizedSanadItems = [];

        foreach ($sanadItems as $item) {
            $normalizedItem = $item;
            if (isset($item['total_value'])) {
                $normalizedItem['total_value'] = $this->normalizeNumber($item['total_value']);
            }
            $calculatedTotalValue += Arr::get($normalizedItem, 'total_value', 0);
            $normalizedSanadItems[] = $normalizedItem;
        }

        $finalTotalValue = $totalValue !== null ? $totalValue : $calculatedTotalValue;

        $sanadData = [
            'debtor' => [
                'national_id' => $debtorNationalId,
            ],
            'debtor_phone_number' => $debtorPhoneNumber,
            'total_value' => $this->normalizeNumber($finalTotalValue),
            'city_of_payment' => $cityOfIssuanceName,
            'currency' => config('nafith.defaults.currency'),
            'max_approve_duration' => config('nafith.defaults.max_approve_duration'),
            'reference_id' => $referenceId,
            'sanad' => $normalizedSanadItems,
        ];

        return $this->createSanad($sanadData);
    }

    public function createSanadWithCreditor(
        array $creditorData,
        array $debtorData,
        array $sanadItems,
        $referenceId,
        $cityOfIssuance,
        $cityOfPayment,
        $totalValue = null,
        $countryOfIssuance = 'SA',
        $countryOfPayment = 'SA'
    ) {
        $creditorNationalId = (string) Arr::get($creditorData, 'national_id');
        $debtorNationalId = (string) Arr::get($debtorData, 'national_id');
        $debtorPhoneNumber = (string) Arr::get($debtorData, 'phone_number');

        $calculatedTotalValue = 0;
        $normalizedSanadItems = [];

        foreach ($sanadItems as $item) {
            $normalizedItem = $item;
            if (isset($item['total_value'])) {
                $normalizedItem['total_value'] = $this->normalizeNumber($item['total_value']);
            }
            $calculatedTotalValue += Arr::get($normalizedItem, 'total_value', 0);
            $normalizedSanadItems[] = $normalizedItem;
        }

        $finalTotalValue = $totalValue !== null ? $totalValue : $calculatedTotalValue;

        $sanadData = [
            'creditor' => [
                'national_id' => $creditorNationalId,
            ],
            'debtor' => [
                'national_id' => $debtorNationalId,
            ],
            'city_of_issuance' => (string) $cityOfIssuance,
            'debtor_phone_number' => $debtorPhoneNumber,
            'total_value' => $this->normalizeNumber($finalTotalValue),
            'city_of_payment' => (string) $cityOfPayment,
            'currency' => config('nafith.defaults.currency'),
            'max_approve_duration' => config('nafith.defaults.max_approve_duration'),
            'reference_id' => (string) $referenceId,
            'country_of_issuance' => (string) $countryOfIssuance,
            'country_of_payment' => (string) $countryOfPayment,
            'sanad' => $normalizedSanadItems,
        ];

        return $this->createSanad($sanadData);
    }

    /**
     * A generic method to handle PATCH requests for updating SANAD group status or individual SANAD statuses within a group.
     */
    protected function updateSanadStatus(string $sanadGroupId, array $data)
    {
        try {
            $token = $this->getAuthToken();
            if (! $token) {
                throw new \Exception('Unable to get authentication token');
            }

            $timestamp = $this->getTimestamp();

            // 1. Define the full endpoint URL for the HTTP request (e.g., /api/sanad-group/{id}/)
            $requestEndpointPath = '/api/sanad-group/'.$sanadGroupId.'/';
            $requestEndpointUrl = $this->baseUrl.$requestEndpointPath;

            // 2. Define the base endpoint URL for signature generation (e.g., /api/sanad-group/)
            // Nafith requires the base path in the signature string when an object ID is provided.
            $signatureBaseUrl = $this->baseUrl.config('nafith.endpoints.sanad_group');

            $requestData = $data;

            // Signature must be generated using the BASE resource URL and the Sanad Group ID as the objectId.
            $signature = $this->generateSignature('PATCH', $signatureBaseUrl, $timestamp, $requestData, $sanadGroupId);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token,
                'X-Nafith-Timestamp' => $timestamp,
                'X-Nafith-Tracking-Id' => $this->generateTrackingId(),
                'X-Nafith-Signature' => $signature,
            ])->patch($requestEndpointUrl, $requestData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Nafith Update SANAD Status Failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_data' => $requestData,
                'signature' => $signature,
                'timestamp' => $timestamp,
                'group_id' => $sanadGroupId,
                'signature_base_url_attempted' => $signatureBaseUrl,
                'request_url_attempted' => $requestEndpointUrl,
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Nafith Update SANAD Status Exception', [
                'error' => $e->getMessage(),
                'group_id' => $sanadGroupId,
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel Single or Multiple Sanad Group (status: cancelled_by_creditor).
     */
    public function cancelSanadGroup(string $sanadGroupId)
    {
        return $this->updateSanadStatus($sanadGroupId, [
            'status' => 'cancelled_by_creditor',
        ]);
    }

    /**
     * Close Single Sanad Group (status: closed).
     */
    public function closeSingleSanadGroup(string $sanadGroupId)
    {
        return $this->updateSanadStatus($sanadGroupId, [
            'status' => 'closed',
        ]);
    }

    /**
     * Close Individual Sanad inside a Multiple Sanad Group.
     */
    public function closeIndividualSanad(string $sanadGroupId, string $sanadId)
    {
        // Sanad ID needs to be provided in the body of the PATCH request under the 'sanad' array
        return $this->updateSanadStatus($sanadGroupId, [
            'sanad' => [
                [
                    'id' => $sanadId,
                    'status' => 'closed',
                ],
            ],
        ]);
    }

    /**
     * Fetch a SANAD record by its SANAD number.
     */
    public function getSanadByNumber(string $sanadNumber)
    {
        try {
            $token = $this->getAuthToken();
            if (! $token) {
                throw new \Exception('Unable to get authentication token');
            }

            $timestamp = $this->getTimestamp();

            // API endpoint
            $endpointPath = '/api/sanad/by-number/'.$sanadNumber.'/';
            $endpointUrl = $this->baseUrl.$endpointPath;

            // For signature generation, use the same base path
            $signatureBaseUrl = $this->baseUrl.'/api/sanad/by-number/';
            $signature = $this->generateSignature('GET', $signatureBaseUrl, $timestamp, [], $sanadNumber);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
                'X-Nafith-Timestamp' => $timestamp,
                'X-Nafith-Tracking-Id' => $this->generateTrackingId(),
                'X-Nafith-Signature' => $signature,
            ])->get($endpointUrl);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Nafith Get SANAD by Number Failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'sanad_number' => $sanadNumber,
                'signature' => $signature,
                'timestamp' => $timestamp,
                'endpoint' => $endpointUrl,
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Nafith Get SANAD by Number Exception', [
                'error' => $e->getMessage(),
                'sanad_number' => $sanadNumber,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Download SANAD Group by Group ID.
     */
    public function downloadSanadGroup(string $sanadGroupId)
    {
        try {
            $token = $this->getAuthToken();
            if (! $token) {
                throw new \Exception('Unable to get authentication token');
            }

            $timestamp = $this->getTimestamp();

            // Actual endpoint for the API call
            $endpointPath = '/api/sanad-group/download/'.$sanadGroupId.'/';
            $endpointUrl = $this->baseUrl.$endpointPath;

            // For signature generation, use the same base path without the group ID
            $signatureBaseUrl = $this->baseUrl.'/api/sanad-group/download/';
            $signature = $this->generateSignature('GET', $signatureBaseUrl, $timestamp, [], $sanadGroupId);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
                'X-Nafith-Timestamp' => $timestamp,
                'X-Nafith-Tracking-Id' => $this->generateTrackingId(),
                'X-Nafith-Signature' => $signature,
            ])->get($endpointUrl);

            if ($response->successful()) {
                // Nafith might return binary or base64 data (PDF, ZIP, etc.)
                // Return the raw response or JSON depending on response type
                $contentType = $response->header('Content-Type');

                if (Str::contains($contentType, ['application/pdf', 'application/zip'])) {
                    return response($response->body(), 200)->header('Content-Type', $contentType);
                }

                return $response->json();
            }

            Log::error('Nafith Download SANAD Group Failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'group_id' => $sanadGroupId,
                'signature' => $signature,
                'timestamp' => $timestamp,
                'endpoint' => $endpointUrl,
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Nafith Download SANAD Group Exception', [
                'error' => $e->getMessage(),
                'group_id' => $sanadGroupId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
