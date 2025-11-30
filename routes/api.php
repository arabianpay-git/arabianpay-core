<?php

use App\Services\NafithService;
use App\Services\SimahService;
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






Route::prefix('nafith')->group(function () {

    // Test authentication token generation
    Route::get('/auth-token', function (Request $request) {
        try {
            $nafithService = app(NafithService::class);
            $token = $nafithService->generateAuthToken();

            return response()->json([
                'success' => true,
                'token' => $token,
                'length' => strlen($token)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Test creating a single SANAD (uses generic createSanad method)
    Route::get('/create-sanad', function (Request $request) {
        try {
            $nafithService = app(NafithService::class);

            // Default data structure for a single SANAD (replace with actual request validation/data)
            $sanadData = [
                'debtor' => [
                    'national_id' => $request->input('debtor_nin', '1000473387'),
                ],
                'city_of_issuance' => $request->input('city_of_issuance', 1),
                'debtor_phone_number' => $request->input('debtor_phone_number', '0546258295'),
                'total_value' => $request->input('total_value', 200),
                'currency' => $request->input('currency', 'SAR'),
                'max_approve_duration' => $request->input('max_approve_duration', 100),
                'reference_id' => $request->input('reference_id', 'test_ref_' . time()),
                'sanad' => [
                    [
                        'due_type' => $request->input('due_type', 'date'),
                        'due_date' => $request->input('due_date', '2025-12-28'),
                        'total_value' => $request->input('total_value', 200),
                        'reference_id' => $request->input('sanad_reference_id', 'sanad_test_' . time()),
                    ]
                ]
            ];

            $result = $nafithService->createSanad($sanadData);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Test creating multiple SANADs (using the dedicated helper)
    Route::get('/create-multiple-sanads', function (Request $request) {
        try {
            $nafithService = app(NafithService::class);

            $debtorData = [
                'national_id' => $request->input('debtor_nin', '1000473387'),
                'phone_number' => $request->input('debtor_phone_number', '0546258295'),
            ];

            // Define mock items for the SANAD group
            $sanadItems = [
                [
                    'due_type' => 'date',
                    'due_date' => '2025-12-28',
                    'total_value' => 200.00,
                    'reference_id' => 'sanad_multi_1_' . time(),
                ],
                [
                    'due_type' => 'upon request',
                    'total_value' => 920.44,
                    'reference_id' => 'sanad_multi_2_' . time(),
                ],
            ];

            $totalValue = 200.00 + 920.44;

            $result = $nafithService->createMultipleSanads(
                $debtorData,
                $sanadItems,
                $request->input('reference_id', 'test_multi_ref_' . time()),
                $request->input('city_of_payment', '1'),
                $totalValue
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Test creating a SANAD with Creditor details and full geographic fields
    Route::get('/create-sanad-with-creditor', function (Request $request) {
        try {
            $nafithService = app(NafithService::class);

            $creditorData = [
                'national_id' => $request->input('creditor_nin', '7001046324'),
            ];

            $debtorData = [
                'national_id' => $request->input('debtor_nin', '1000473387'),
                'phone_number' => $request->input('debtor_phone_number', '0546258295'),
            ];

            $sanadItems = [
                [
                    'due_type' => 'date',
                    'due_date' => '2025-12-28',
                    'total_value' => 200.00,
                    'reference_id' => 'sanad4_' . time(),
                ],
                [
                    'due_type' => 'upon request',
                    'total_value' => 920.44,
                    'reference_id' => 'sanad5_' . time(),
                ],
            ];

            $totalValue = 200.00 + 920.44;

            $result = $nafithService->createSanadWithCreditor(
                $creditorData,
                $debtorData,
                $sanadItems,
                $request->input('reference_id', 'test_creditor_ref_' . time()),
                $request->input('city_of_issuance', 1),
                $request->input('city_of_payment', 2),
                $totalValue,
                $request->input('country_of_issuance', 'SA'),
                $request->input('country_of_payment', 'SA')
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // 1. Cancel Single/Multiple Sanad Group
    Route::get('/cancel-sanad-group/{sanadGroupId}', function (string $sanadGroupId) {
        try {
            $nafithService = app(NafithService::class);
            $result = $nafithService->cancelSanadGroup($sanadGroupId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // 2. Close Single/Multiple Sanad Group (fully)
    Route::get('/close-sanad-group/{sanadGroupId}', function (string $sanadGroupId) {
        try {
            $nafithService = app(NafithService::class);
            $result = $nafithService->closeSingleSanadGroup($sanadGroupId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // 3. Close Individual Sanad inside Multiple Sanad Group
    Route::get('/close-individual-sanad/{sanadGroupId}', function (Request $request, string $sanadGroupId) {
        $sanadId = $request->input('sanad_id');

        if (empty($sanadId)) {
            return response()->json(['success' => false, 'error' => 'The sanad_id (UUID of the individual sanad) is required in the request body/query.'], 400);
        }

        try {
            $nafithService = app(NafithService::class);
            $result = $nafithService->closeIndividualSanad($sanadGroupId, $sanadId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Clear cached token
    Route::get('/clear-token', function () {
        try {
            $nafithService = app(NafithService::class);
            $nafithService->clearCachedToken();

            return response()->json([
                'success' => true,
                'message' => 'Token cleared'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::get('/get-sanad-by-number', function (Request $request) {
        try {
            $nafithService = app(NafithService::class);

            // Dummy SANAD number for testing — replace later
            $sanadNumber = $request->input('sanad_number', '10211025262474'); // number

            $result = $nafithService->getSanadByNumber($sanadNumber);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::get('/download-sanad-group', function (Request $request) {
        try {
            $nafithService = app(NafithService::class);

            // Dummy group ID for testing
            $sanadGroupId = $request->input('sanad_group', '83266b77-73cb-4c5d-a395-285a5cf7f293'); // id

            $result = $nafithService->downloadSanadGroup($sanadGroupId);

            // If the service returned a response() object (PDF/ZIP), return it directly
            if ($result instanceof \Illuminate\Http\Response) {
                return $result;
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });
});

Route::get('/test-silver-report', function () {
    ini_set('max_execution_time', 300);
    try {
        $simahService = app(SimahService::class);

        // Dummy data for testing
        $data = [
            'idNumber'    => '7035087050',
            'nationality' => 196,
            'familyName'  => 'ABC',
            'firstName'   => 'ABB',
            'secondName'  => 'BBC',
            'thirdName'   => 'CCD',
            'expiryDate'  => '30/10/2040',
            'gender'      => 1,
            'dateOfBirth' => '30/11/1970',
            'memberRefNo' => 'rRQgsi47pLDnWi_JKElEGWAwBY887',
        ];

        $result = $simahService->getSilverReport($data);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/test-consumer-score', function () {
    ini_set('max_execution_time', 300);
    try {
        $simahService = app(SimahService::class);

        // Dummy data for testing
        $data = [
            'language' => 'en',
            'identityInfo' => [
                'idType'   => 2,
                'idNumber' => '2583103284',
                'productId' => 23,
            ],
            'applicationDetails' => [
                'amount'      => 100,
                'productType' => 23,
            ],
            'demographicInfo' => [
                'isHijriIDExpiryDate' => true,
                'idExpiryDate'        => '30/05/1453',
                'nationality'         => 168,
                'maritalStatus'       => 1,
                'isHijriDateOfBirth'  => true,
                'dateOfBirth'         => '09/06/1930',
                'firstName'           => 'Asad',
                'gender'              => 1,
                'secondName'          => 'Mahmood',
                'thirdName'           => 'third',
                'familyName'          => 'family',
            ],
            'accept'          => true,
        ];

        $result = $simahService->consumerScore($data);

        return response()->json([
            'success' => true,
            'data'    => $result
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage()
        ], 500);
    }
});
