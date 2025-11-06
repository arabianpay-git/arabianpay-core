<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SingleViewService
{
    protected string $domain;
    protected string $clientId;
    protected string $clientCode;
    protected string $merchantId;

    public function __construct()
    {
        $this->domain     = config('singleview.domain');
        $this->clientId   = config('singleview.client_id');
        $this->clientCode = config('singleview.client_code');
        $this->merchantId = config('singleview.merchant_id');
    }

    /**
     * Generate signature for any API request
     */
    protected function generateSignature(array $body = []): string
    {
        $endpoint = config('singleview.endpoints.signature');

        $response = Http::withHeaders([
            'clientId'   => $this->clientId,
            'clientCode' => $this->clientCode,
        ])->withBody(json_encode($body), 'application/json')
            ->send('GET', $this->domain . $endpoint);

        if ($response->failed()) {
            Log::error('Signature generation failed', ['response' => $response->body()]);
            throw new \Exception('Could not generate signature');
        }

        return $response->json('signature');
    }

    /**
     * Get access token (cached)
     */
    public function getAccessToken(): string
    {
        return Cache::remember('singleview_access_token', 3500, function () {
            $body = [
                "clientId"   => $this->clientId,
                "clientCode" => $this->clientCode,
                "merchantId" => $this->merchantId,
                "grantType"  => config('singleview.grant_type'),
            ];

            $signature = $this->generateSignature($body);

            $response = Http::withHeaders([
                'signature'  => $signature,
                'clientId'   => $this->clientId,
                'clientCode' => $this->clientCode,
            ])->post($this->domain . config('singleview.endpoints.token'), $body);

            if ($response->failed()) {
                Log::error('Token request failed', ['response' => $response->body()]);
                throw new \Exception('Could not get access token');
            }

            return $response->json('payload.access_token');
        });
    }

    /**
     * Generic API request handler
     */
    public function requestApi(string $endpointKey, array $body = [], string $method = 'POST'): array
    {
        $endpoint = config("singleview.endpoints.{$endpointKey}");
        if (!$endpoint) throw new \Exception("API endpoint '{$endpointKey}' not defined");

        $signature = $this->generateSignature($body);

        $request = Http::withHeaders([
            'signature'     => $signature,
            'clientId'      => $this->clientId,
            'clientCode'    => $this->clientCode,
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ]);

        $url = $this->domain . $endpoint;
        $response = $method === 'GET' ? $request->get($url, $body) : $request->post($url, $body);

        if ($response->failed()) {
            Log::error("API call failed: {$endpointKey}", ['response' => $response->body()]);
            throw new \Exception("API call failed: {$endpointKey}");
        }

        return $response->json();
    }

    /**
     * Create Consent (cached per bank code)
     */
    public function createConsent(
        string $redirectUrl,
        string $bankCode,
        string $accountType = 'retail',
        ?DateTime $expiryDate = null,
        ?DateTime $txnFromDate = null,
        ?DateTime $txnToDate = null
    ): array {
        $cacheKey = "singleview_consent_{$bankCode}";

        return Cache::remember($cacheKey, 3600, function () use ($redirectUrl, $bankCode, $accountType, $expiryDate, $txnFromDate, $txnToDate) {
            $expiryDate  = $expiryDate ?: now()->addHours(1);
            $txnFromDate = $txnFromDate ?: new DateTime('2016-01-01');
            $txnToDate   = $txnToDate ?: now();

            $body = [
                "dateTimeStamp" => now()->toIso8601String(),
                "requestID"     => (string) Str::uuid(),
                "merchantId"    => $this->merchantId,
                "useCaseType"   => "AISP",
                "redirectUrl"   => $redirectUrl,
                "banks"         => [[
                    "code"         => $bankCode,
                    "permissions"  => [
                        "ReadAccountsBasic",
                        "ReadAccountsDetail",
                        "ReadBalances",
                        "ReadParty",
                        "ReadPartyPSU",
                        "ReadPartyPSUIdentity",
                        "ReadBeneficiariesBasic",
                        "ReadBeneficiariesDetail",
                        "ReadTransactionsBasic",
                        "ReadTransactionsDetail",
                        "ReadTransactionsCredits",
                        "ReadTransactionsDebits",
                        "ReadScheduledPaymentsBasic",
                        "ReadScheduledPaymentsDetail",
                        "ReadDirectDebits",
                        "ReadStandingOrdersBasic",
                        "ReadStandingOrdersDetail"
                    ],
                    "expiryDate"   => $expiryDate->format('c'),
                    "txnFromDate"  => $txnFromDate->format('c'),
                    "txnToDate"    => $txnToDate->format('c'),
                    "accountType"  => $accountType,
                ]]
            ];

            return $this->requestApi('consent', $body);
        });
    }

    /**
     * Get Consent Details (dynamic, no cache)
     */
    public function getConsentDetails(string $bankCode, string $consentId): array
    {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "banks"         => [[
                "code"      => $bankCode,
                "consentId" => $consentId
            ]]
        ];

        return $this->requestApi('consent_details', $body);
    }

    /**
     * Revoke Consent (dynamic, no cache)
     */
    public function revokeConsent(string $bankCode, string $consentId): array
    {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "banks"         => [[
                "code"      => $bankCode,
                "consentId" => $consentId
            ]]
        ];

        return $this->requestApi('revoke_consent', $body);
    }

    /**
     * Get Accounts / IBAN check (dynamic, no cache)
     */
    public function getAccounts(string $bankCode, string $consentId, string $iban = '', bool $ibanCheck = true): array
    {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "ibanCheck"     => $ibanCheck,
            "banks"         => [[
                "code"      => $bankCode,
                "consentId" => $consentId,
                "iban"      => $iban,
            ]]
        ];

        return $this->requestApi('accounts', $body);
    }

    /**
     * Get All Accounts Transactions (Generate E-stmt JSON)
     */
    public function getAllAccountsStatement(
        string $bankCode,
        string $consentId,
        ?string $fromDate = null,
        ?string $toDate = null,
        bool $estatement = true
    ): array {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "fromDate"      => $fromDate ?: '2016-01-01T00:00:00+02:00',
            "toDate"        => $toDate ?: now()->toIso8601String(),
            "estatement"    => $estatement,
            "banks"         => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('e_statement', $body);
    }

    /**
     * Credit Check Basic JSON
     */
    public function creditCheckBasic(
        string $bankCode,
        string $consentId,
        ?string $fromDate = null,
        ?string $toDate = null,
        bool $creditCheck = true
    ): array {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "creditCheck"   => $creditCheck,
            "fromDate"      => $fromDate ?: '2016-01-01T00:00:00+02:00',
            "toDate"        => $toDate ?: now()->toIso8601String(),
            "banks"         => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('e_statement', $body);
    }

    /**
     * Credit Check Advanced JSON
     */
    public function creditCheckAdvanced(
        string $bankCode,
        string $consentId,
        ?string $fromDate = null,
        ?string $toDate = null,
        bool $creditCheckAdvanced = true
    ): array {
        $body = [
            "dateTimeStamp"         => now()->toIso8601String(),
            "requestID"             => (string) Str::uuid(),
            "merchantId"            => $this->merchantId,
            "creditCheckAdvanced"   => $creditCheckAdvanced,
            "fromDate"              => $fromDate ?: '2016-01-01T00:00:00+02:00',
            "toDate"                => $toDate ?: now()->toIso8601String(),
            "banks"                 => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('e_statement', $body);
    }

    /**
     * Income Check Basic JSON
     */
    public function incomeCheckBasic(
        string $bankCode,
        string $consentId,
        ?string $fromDate = null,
        ?string $toDate = null,
        string $timeLine = 'byDay',
        bool $insights = true,
        bool $incomeCheck = true
    ): array {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "insights"      => $insights,
            "incomeCheck"   => $incomeCheck,
            "fromDate"      => $fromDate ?: '2016-01-01T00:00:00+02:00',
            "toDate"        => $toDate ?: now()->toIso8601String(),
            "timeLine"      => $timeLine,
            "banks"         => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('e_statement', $body);
    }

    /**
     * Income Check Advanced JSON
     */
    public function incomeCheckAdvanced(
        string $bankCode,
        string $consentId,
        ?string $fromDate = null,
        ?string $toDate = null,
        string $timeLine = 'byDay',
        bool $insights = true,
        bool $incomeCheckAdvanced = true
    ): array {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "insights"      => $insights,
            "incomeCheckAdvanced"   => $incomeCheckAdvanced,
            "fromDate"      => $fromDate ?: '2016-01-01T00:00:00+02:00',
            "toDate"        => $toDate ?: now()->toIso8601String(),
            "timeLine"      => $timeLine,
            "banks"         => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('e_statement', $body);
    }

    /**
     * Expense Check Basic JSON
     */
    public function expenseCheckBasic(
        string $bankCode,
        string $consentId,
        ?string $fromDate = null,
        ?string $toDate = null,
        string $timeLine = 'byDay',
        bool $insights = true,
        bool $expenseCheck = true
    ): array {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "insights"      => $insights,
            "expenseCheck"   => $expenseCheck,
            "fromDate"      => $fromDate ?: '2016-01-01T00:00:00+02:00',
            "toDate"        => $toDate ?: now()->toIso8601String(),
            "timeLine"      => $timeLine,
            "banks"         => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('e_statement', $body);
    }

    /**
     * Expense Check Advanced JSON
     */
    public function expenseCheckAdvanced(
        string $bankCode,
        string $consentId,
        ?string $fromDate = null,
        ?string $toDate = null,
        string $timeLine = 'byDay',
        bool $insights = true,
        bool $expenseCheckAdvanced = true
    ): array {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "insights"      => $insights,
            "expenseCheckAdvanced"   => $expenseCheckAdvanced,
            "fromDate"      => $fromDate ?: '2016-01-01T00:00:00+02:00',
            "toDate"        => $toDate ?: now()->toIso8601String(),
            "timeLine"      => $timeLine,
            "banks"         => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('e_statement', $body);
    }

    /**
     * Get All Accounts
     */
    public function getAllAccounts(
        string $bankCode,
        string $consentId
    ): array {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "banks"         => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('all_accounts', $body);
    }

    /**
     * Get Parties
     */
    public function getParties(string $bankCode, string $consentId): array
    {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "banks" => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('parties', $body);
    }

    /**
     * Get Account by ID
     */
    public function getAccountById(string $bankCode, string $consentId, string $accountId): array
    {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "banks" => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                    "accountId" => $accountId,
                ]
            ]
        ];

        return $this->requestApi('account_by_id', $body);
    }

    /**
     * Get Parties by Account ID
     */
    public function getPartiesById(string $bankCode, string $consentId, string $accountId): array
    {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "banks" => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                    "accountId" => $accountId,
                ]
            ]
        ];

        return $this->requestApi('parties_by_id', $body);
    }

    /**
     * Get All Account Balances
     */
    public function getAllAccountsBalance(string $bankCode, string $consentId, bool $accountAggregation = true): array
    {
        $body = [
            "dateTimeStamp"     => now()->toIso8601String(),
            "requestID"         => (string) Str::uuid(),
            "merchantId"        => $this->merchantId,
            "accountAggregation" => $accountAggregation,
            "banks" => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('all_accounts_balance', $body);
    }

    /**
     * Get All Accounts Transactions
     */
    public function getAllAccountsTransactions(string $bankCode, string $consentId, ?string $fromDate = null, ?string $toDate = null, bool $accountAggregation = true): array
    {
        $body = [
            "dateTimeStamp"     => now()->toIso8601String(),
            "requestID"         => (string) Str::uuid(),
            "merchantId"        => $this->merchantId,
            "fromDate"          => $fromDate ?: '2016-01-01T00:00:00+02:00',
            "toDate"            => $toDate ?: now()->toIso8601String(),
            "accountAggregation" => $accountAggregation,
            "banks" => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('all_accounts_transactions', $body);
    }

    /**
     * Get All Accounts Direct Debits
     */
    public function getAllAccountsDirectDebits(string $bankCode, string $consentId, bool $accountAggregation = true): array
    {
        $body = [
            "dateTimeStamp"     => now()->toIso8601String(),
            "requestID"         => (string) Str::uuid(),
            "merchantId"        => $this->merchantId,
            "accountAggregation" => $accountAggregation,
            "banks" => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('all_accounts_direct_debits', $body);
    }

    /**
     * Get All Accounts Standing Orders
     */
    public function getAllAccountsStandingOrders(string $bankCode, string $consentId, bool $accountAggregation = true): array
    {
        $body = [
            "dateTimeStamp"     => now()->toIso8601String(),
            "requestID"         => (string) Str::uuid(),
            "merchantId"        => $this->merchantId,
            "accountAggregation" => $accountAggregation,
            "banks" => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('all_accounts_standing_orders', $body);
    }

    /**
     * Get All Accounts Scheduled Payments
     */
    public function getAllAccountsScheduledPayments(string $bankCode, string $consentId, bool $accountAggregation = true): array
    {
        $body = [
            "dateTimeStamp"     => now()->toIso8601String(),
            "requestID"         => (string) Str::uuid(),
            "merchantId"        => $this->merchantId,
            "accountAggregation" => $accountAggregation,
            "banks" => [
                [
                    "code"      => $bankCode,
                    "consentId" => $consentId,
                ]
            ]
        ];

        return $this->requestApi('all_accounts_scheduled_payments', $body);
    }

    public function getAllAccountsCheck(string $bankCode, string $consentId): array
    {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "accountCheck"  => true,
            "banks"         => [["code" => $bankCode, "consentId" => $consentId]]
        ];

        return $this->requestApi('all_accounts_check', $body);
    }

    public function getAllAccountsBalanceCheck(string $bankCode, string $consentId): array
    {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "balanceCheck"  => true,
            "banks"         => [["code" => $bankCode, "consentId" => $consentId]]
        ];

        return $this->requestApi('all_accounts_balance', $body);
    }

    /**
     * KYC API
     */
    public function kyc(string $bankCode, string $consentId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $body = [
            "dateTimeStamp" => now()->toIso8601String(),
            "requestID"     => (string) Str::uuid(),
            "merchantId"    => $this->merchantId,
            "kyc"           => true,
            "fromDate"      => $fromDate ?: '2016-01-01T00:00:00+02:00',
            "toDate"        => $toDate ?: now()->toIso8601String(),
            "banks"         => [["code" => $bankCode, "consentId" => $consentId]]
        ];

        return $this->requestApi('kyc_check', $body);
    }

    /**
     * Customer Verification API
     */
    public function customerVerification(string $bankCode, string $consentId): array
    {
        $body = [
            "dateTimeStamp"          => now()->toIso8601String(),
            "requestID"              => (string) Str::uuid(),
            "merchantId"             => $this->merchantId,
            "customerVerification"   => true,
            "banks"                  => [["code" => $bankCode, "consentId" => $consentId]]
        ];

        return $this->requestApi('customer_verification', $body);
    }
}
