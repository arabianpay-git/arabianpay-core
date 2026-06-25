<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeanService
{
    protected string $baseUrl;

    protected string $authUrl;

    protected string $clientId;

    protected string $clientSecret;

    protected int $tokenTtl = 3500; // 58 minutes in seconds

    public function __construct()
    {
        $this->setEnvironment(config('lean.environment', 'sandbox'));
        $this->clientId = config('lean.client_id');
        $this->clientSecret = config('lean.client_secret');
    }

    public function setEnvironment(string $environment): self
    {
        $config = config("lean.environments.{$environment}");

        if (! $config) {
            throw new \InvalidArgumentException("Invalid Lean environment: {$environment}");
        }

        $this->authUrl = $config['auth_url'];
        $this->baseUrl = $config['api_url'];

        return $this;
    }

    protected function getAccessToken(): string
    {
        return Cache::remember('lean_access_token', $this->tokenTtl, function () {
            return $this->fetchAccessToken();
        });
    }

    protected function fetchAccessToken(): string
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->authUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'api',
                ]);

            if (! $response->successful()) {
                Log::error('Lean Auth Failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                throw new \Exception('Failed to authenticate with Lean API');
            }

            $data = $response->json();

            if (! isset($data['access_token'])) {
                throw new \Exception('Invalid token response from Lean API');
            }

            return $data['access_token'];
        } catch (\Exception $e) {
            Log::error('Lean Token Fetch Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function clearTokenCache(): bool
    {
        return Cache::forget('lean_access_token');
    }

    protected function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        $token = $this->getAccessToken();

        try {
            $response = Http::withToken($token)
                ->timeout(60)
                ->acceptJson()
                ->$method($this->baseUrl.$endpoint, $params);

            if ($response->status() === 401) {
                $this->clearTokenCache();
                $token = $this->getAccessToken();

                $response = Http::withToken($token)
                    ->timeout(60)
                    ->acceptJson()
                    ->$method($this->baseUrl.$endpoint, $params);
            }

            if (! $response->successful()) {
                Log::error('Lean API Request Failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                throw new \Exception('Lean API request failed with status: '.$response->status());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Lean API Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getBanks(?string $accountType = null): array
    {
        $params = [];

        if ($accountType) {
            $params['account_types'] = $accountType;
        }

        return $this->makeRequest('get', '/banks/v1/', $params);
    }

    public function getEntities(string $startDate, string $endDate, int $page = 0, int $size = 50): array
    {
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'page_number' => $page,
            'page_size' => $size,
        ];

        return $this->makeRequest('get', '/customers/v1/entities', $params);
    }

    public function getCustomers(int $page = 0, int $size = 15): array
    {
        $params = [
            'page_number' => $page,
            'page_size' => $size,
        ];

        return $this->makeRequest('get', '/customers/v1', $params);
    }

    /**
     * NEW: Get entities for a specific customer ID
     */
    public function getEntitiesByCustomerId(string $customerId, int $page = 0, int $size = 50): array
    {
        $params = [
            'page_number' => $page,
            'page_size' => $size,
        ];

        $endpoint = "/customers/v1/{$customerId}/entities";

        return $this->makeRequest('get', $endpoint, $params);
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->getCustomers(0, 1);

            return isset($response['data']) || isset($response['page']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get bank statements report for a specific customer
     *
     * @param  array|string  $accountSubTypes  One or multiple account types
     * @param  string  $startDate  YYYY-MM-DD
     * @param  string  $endDate  YYYY-MM-DD
     */
    public function createBankStatementsReport(
        string $customerId,
        array|string $accountSubTypes,
        string $startDate,
        string $endDate
    ): array {
        $endpoint = "/insights/v2/customers/{$customerId}/reports/bank-statements";

        // Ensure account_sub_types is always an array
        $accountSubTypesArray = is_array($accountSubTypes) ? $accountSubTypes : [$accountSubTypes];

        $payload = [
            'account_sub_types' => $accountSubTypesArray,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        return $this->makeRequest('post', $endpoint, $payload);
    }

    /**
     * Get a specific bank statements report by customer ID and report ID
     */
    public function getBankStatementReportById(string $customerId, string $reportId): array
    {
        $endpoint = "/insights/v2/customers/{$customerId}/reports/bank-statements/{$reportId}";

        return $this->makeRequest('get', $endpoint);
    }
}
