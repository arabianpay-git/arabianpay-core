<?php

/**
 * [PHASE-2] Financial configuration.
 *
 * Centralizes account IDs and financial constants previously hardcoded
 * in SettlementService and other financial services.
 */
return [
    'accounts' => [
        'accounts_payable' => env('FACCOUNT_ACCOUNTS_PAYABLE', 2400),
        'bank_account' => env('FACCOUNT_BANK_ACCOUNT', 1201),
        'expense' => env('FACCOUNT_EXPENSE', 5000),
    ],
];
