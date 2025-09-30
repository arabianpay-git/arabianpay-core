<?php

use App\Services\SingleViewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('singleview')->group(function () {

    // Test: Get Access Token
    Route::get('/token', function () {
        $service = app(SingleViewService::class);
        return response()->json([
            'access_token' => $service->getAccessToken()
        ]);
    });

    // Test: Create Consent
    Route::get('/consent/create', function () {
        $service = app(SingleViewService::class);
        $bankCode = request()->query('bankCode', 'SVMOB1'); // default bankCode if not provided
        $redirectUrl = request()->query('redirectUrl', 'https://partners.arabianpay.net/');
        return response()->json($service->createConsent($redirectUrl, $bankCode));
    });

    // Test: Get Consent Details
    Route::get('/consent/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getConsentDetails($bankCode, $consentId));
    });

    // Test: Revoke Consent
    Route::get('/consent/{bankCode}/{consentId}/revoke', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->revokeConsent($bankCode, $consentId));
    });

    // Test: Get Accounts / IBAN Check
    Route::get('/accounts/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        $iban = request()->query('iban', ''); // optional IBAN query parameter
        $ibanCheck = filter_var(request()->query('ibanCheck', true), FILTER_VALIDATE_BOOLEAN); // optional, defaults to true
        return response()->json($service->getAccounts($bankCode, $consentId, $iban, $ibanCheck));
    });

    // Test: Get All Accounts Transactions
    Route::get('/all-transactions/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        $fromDate = request()->query('fromDate', '2016-01-01T00:00:00+02:00'); // optional
        $toDate   = request()->query('toDate', now()->toIso8601String());   // optional
        $estatement = filter_var(request()->query('estatement', true), FILTER_VALIDATE_BOOLEAN);
        return response()->json($service->getAllAccountsStatement($bankCode, $consentId, $fromDate, $toDate, $estatement));
    });

    // Test: Credit Check Basic
    Route::get('/credit-check-basic/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        $fromDate = request()->query('fromDate'); // optional
        $toDate   = request()->query('toDate');   // optional
        $creditCheck = filter_var(request()->query('creditCheck', true), FILTER_VALIDATE_BOOLEAN);
        return response()->json($service->creditCheckBasic($bankCode, $consentId, $fromDate, $toDate, $creditCheck));
    });

    // Test: Credit Check Advanced
    Route::get('/credit-check-advanced/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        $fromDate = request()->query('fromDate'); // optional
        $toDate   = request()->query('toDate');   // optional
        $creditCheckAdvanced = filter_var(request()->query('creditCheckAdvanced', true), FILTER_VALIDATE_BOOLEAN);
        return response()->json($service->creditCheckAdvanced($bankCode, $consentId, $fromDate, $toDate, $creditCheckAdvanced));
    });

    // Test: Income Check Basic
    Route::get('/income-check-basic/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        $fromDate   = request()->query('fromDate'); // optional
        $toDate     = request()->query('toDate');   // optional
        $timeLine   = request()->query('timeLine', 'byDay'); // optional
        $incomeCheck = filter_var(request()->query('incomeCheck', true), FILTER_VALIDATE_BOOLEAN);
        return response()->json($service->incomeCheckBasic($bankCode, $consentId, $fromDate, $toDate, $timeLine, $incomeCheck));
    });

    // Test: Income Check Advanced
    Route::get('/income-check-advanced/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        $fromDate   = request()->query('fromDate'); // optional
        $toDate     = request()->query('toDate');   // optional
        $timeLine   = request()->query('timeLine', 'byDay'); // optional
        $incomeCheck = filter_var(request()->query('incomeCheck', true), FILTER_VALIDATE_BOOLEAN);
        return response()->json($service->incomeCheckAdvanced($bankCode, $consentId, $fromDate, $toDate, $timeLine, $incomeCheck));
    });

    // Test: Expense Check Basic
    Route::get('/expense-check-basic/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        $fromDate   = request()->query('fromDate'); // optional
        $toDate     = request()->query('toDate');   // optional
        $timeLine   = request()->query('timeLine', 'byDay'); // optional
        $expenseCheck = filter_var(request()->query('expenseCheck', true), FILTER_VALIDATE_BOOLEAN);
        return response()->json($service->expenseCheckBasic($bankCode, $consentId, $fromDate, $toDate, $timeLine, $expenseCheck));
    });

    // Test: Expense Check Advanced
    Route::get('/expense-check-advanced/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        $fromDate   = request()->query('fromDate'); // optional
        $toDate     = request()->query('toDate');   // optional
        $timeLine   = request()->query('timeLine', 'byDay'); // optional
        $expenseCheckAdvanced = filter_var(request()->query('expenseCheckAdvanced', true), FILTER_VALIDATE_BOOLEAN);
        return response()->json($service->expenseCheckAdvanced($bankCode, $consentId, $fromDate, $toDate, $timeLine, $expenseCheckAdvanced));
    });

    // Test: Get All Accounts
    Route::get('/all-accounts/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getAllAccounts($bankCode, $consentId));
    });

    Route::get('/all-parties/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getParties($bankCode, $consentId));
    });

    Route::get('/account/{bankCode}/{consentId}/{accountId}', function ($bankCode, $consentId, $accountId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getAccountById($bankCode, $consentId, $accountId));
    });

    Route::get('/parties/{bankCode}/{consentId}/{accountId}', function ($bankCode, $consentId, $accountId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getPartiesById($bankCode, $consentId, $accountId));
    });

    // Test: Get All Accounts Balance
    Route::get('/accounts-balance/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getAllAccountsBalance($bankCode, $consentId));
    });

    // Test: Get All Accounts Transactions
    Route::get('/accounts-transactions/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        $fromDate = request()->query('fromDate'); // optional
        $toDate = request()->query('toDate');     // optional
        return response()->json($service->getAllAccountsTransactions($bankCode, $consentId, $fromDate, $toDate));
    });

    // Test: Get All Accounts Direct Debits
    Route::get('/accounts-direct-debits/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getAllAccountsDirectDebits($bankCode, $consentId));
    });

    // Test: Get All Accounts Standing Orders
    Route::get('/accounts-standing-orders/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getAllAccountsStandingOrders($bankCode, $consentId));
    });

    // Test: Get All Accounts Scheduled Payments
    Route::get('/accounts-scheduled-payments/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getAllAccountsScheduledPayments($bankCode, $consentId));
    });

    // Test: Get All Account Check
    Route::get('/accounts-check/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getAllAccountsCheck($bankCode, $consentId));
    });

    // Test: Get All Accounts Balance (Balance Check)
    Route::get('/accounts-balance-check/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->getAllAccountsBalanceCheck($bankCode, $consentId));
    });

    // Test: KYC
    Route::get('/kyc/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->kyc($bankCode, $consentId));
    });

    // Test: Customer Verification
    Route::get('/customer-verification/{bankCode}/{consentId}', function ($bankCode, $consentId) {
        $service = app(SingleViewService::class);
        return response()->json($service->customerVerification($bankCode, $consentId));
    });
});
