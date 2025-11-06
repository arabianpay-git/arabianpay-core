<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SingleView API Configuration
    |--------------------------------------------------------------------------
    | All required credentials and settings for SingleView APIs
    */

    'domain'       => env('SINGLEVIEW_DOMAIN', 'https://core.arabinapay.net'),

    'client_id'    => env('SINGLEVIEW_CLIENT_ID'),
    'client_code'  => env('SINGLEVIEW_CLIENT_CODE'),
    'merchant_id'  => env('SINGLEVIEW_MERCHANT_ID'),

    // API Endpoints (in case they change later, just update here)
    'endpoints' => [
        'signature'         => '/v1/api/observice/signature',
        'token'             => '/v1/api/observice/token',
        'consent'           => '/v1/api/observice/connect',
        'consent_details'   => '/v1/api/observice/consent/details',
        'accounts'          => '/v1/api/observice/accounts',
        'e_statement'       => '/v1/api/observice/allAccountsTransactions',
        'all_accounts'      => '/v1/api/observice/accounts',
        'parties' => '/v1/api/observice/parties',
        'account_by_id' => '/v1/api/observice/accountsById',
        'parties_by_id' => '/v1/api/observice/partiesById',
        'all_accounts_balance' => '/v1/api/observice/allAccountsBalance',
        'all_accounts_transactions' => '/v1/api/observice/allAccountsTransactions',
        'all_accounts_direct_debits' => '/v1/api/observice/allAccountsDirectDebits',
        'all_accounts_standing_orders' => '/v1/api/observice/allAccountsStandingOrders',
        'all_accounts_scheduled_payments' => '/v1/api/observice/allAccountsScheduledPayments',
        'all_accounts_check' => '/v1/api/observice/allAccountsCheck',
        'all_accounts_balance' => '/v1/api/observice/allAccountsBalance',
        'kyc_check' => '/v1/api/observice/kyc',
        'customer_verification' => '/v1/api/observice/parties',
    ],

    // Default grant type
    'grant_type' => env('SINGLEVIEW_GRANT_TYPE', 'client_credentials'),
];
