<?php

use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\Finance\AccountController;
use App\Http\Controllers\Api\V1\Finance\ClaimController;
use App\Http\Controllers\Api\V1\Finance\CollectionController;
use App\Http\Controllers\Api\V1\Finance\CreditController;
use App\Http\Controllers\Api\V1\Finance\DashboardController;
use App\Http\Controllers\Api\V1\Finance\ExpenseSettingController;
use App\Http\Controllers\Api\V1\Finance\InvestmentPoolController;
use App\Http\Controllers\Api\V1\Finance\LoanTransactionController;
use App\Http\Controllers\Api\V1\Finance\PayoutController;
use App\Http\Controllers\Api\V1\Finance\RefundRequestController;
use App\Http\Controllers\Api\V1\Finance\ReportController;
use App\Http\Controllers\Api\V1\Finance\SchedulePaymentController;
use App\Http\Controllers\Api\V1\Finance\SettlementController;
use App\Http\Controllers\Api\V1\Finance\TransactionController;
use App\Http\Controllers\Api\V1\Finance\TransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    Route::get('/user', [UserController::class, 'show']);

    Route::middleware(['check_admin_api'])->group(function () {

        Route::prefix('finance')->middleware('permission:finance.view')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);
            Route::get('/trial-balance', [DashboardController::class, 'trialBalance']);
            Route::get('/trial-balance/export', [DashboardController::class, 'exportTrialBalance']);

            Route::get('/accounts', [AccountController::class, 'index']);
            Route::post('/accounts', [AccountController::class, 'store']);
            Route::get('/accounts/{account}', [AccountController::class, 'show']);
            Route::put('/accounts/{account}', [AccountController::class, 'update']);
            Route::delete('/accounts/{account}', [AccountController::class, 'destroy']);
            Route::get('/accounts/{account}/ledger', [AccountController::class, 'ledger']);

            Route::get('/transactions', [TransactionController::class, 'index']);
            Route::post('/transactions', [TransactionController::class, 'store']);
            Route::get('/transactions/{id}', [TransactionController::class, 'show']);
            Route::put('/transactions/{id}', [TransactionController::class, 'update']);
            Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
            Route::get('/transactions/create/form-data', [TransactionController::class, 'createFormData']);

            Route::get('/expense-settings', [ExpenseSettingController::class, 'index']);
            Route::post('/expense-settings', [ExpenseSettingController::class, 'store']);
            Route::get('/expense-settings/{expenseSetting}', [ExpenseSettingController::class, 'show']);
            Route::put('/expense-settings/{expenseSetting}', [ExpenseSettingController::class, 'update']);
            Route::delete('/expense-settings/{expenseSetting}', [ExpenseSettingController::class, 'destroy']);
        });

        Route::prefix('settlements')->middleware('permission:settlements.manage')->group(function () {
            Route::get('/', [SettlementController::class, 'index']);
            Route::post('/generate', [SettlementController::class, 'generate']);
            Route::get('/{settlement}', [SettlementController::class, 'show']);
            Route::post('/{settlement}/approve', [SettlementController::class, 'approve']);
            Route::post('/{settlement}/pay', [SettlementController::class, 'pay']);
            Route::post('/{settlement}/cancel', [SettlementController::class, 'cancel']);
            Route::post('/batch/approve', [SettlementController::class, 'batchApprove']);
            Route::post('/batch/cancel', [SettlementController::class, 'batchCancel']);
            Route::post('/batch/payout', [SettlementController::class, 'batchPayout']);
            Route::get('/report', [SettlementController::class, 'report']);
            Route::get('/bank-transfer-file', [SettlementController::class, 'bankTransferFile']);
        });

        Route::prefix('payouts')->middleware('permission:payouts.manage')->group(function () {
            Route::get('/', [PayoutController::class, 'index']);
            Route::post('/', [PayoutController::class, 'store']);
            Route::post('/{payout}/complete', [PayoutController::class, 'complete']);
            Route::post('/{payout}/fail', [PayoutController::class, 'fail']);
            Route::get('/status-by-order/{order}', [PayoutController::class, 'statusByOrder']);
        });

        Route::prefix('loan-transactions')->middleware('permission:transactions.view')->group(function () {
            Route::get('/', [LoanTransactionController::class, 'index']);
            Route::get('/wallet', [LoanTransactionController::class, 'wallet']);
            Route::get('/invoice/{order}', [LoanTransactionController::class, 'invoice']);
            Route::get('/{status}', [LoanTransactionController::class, 'byStatus']);
        });

        Route::prefix('schedule-payments')->middleware('permission:schedule_payments.manage')->group(function () {
            Route::get('/', [SchedulePaymentController::class, 'index']);
            Route::get('/detail/{schedulePayment}', [SchedulePaymentController::class, 'show']);
            Route::post('/pay-now', [SchedulePaymentController::class, 'payNow']);
            Route::put('/{schedulePayment}', [SchedulePaymentController::class, 'update']);
            Route::get('/{status}', [SchedulePaymentController::class, 'byStatus']);
        });

        Route::prefix('collections')->middleware('permission:collections.manage')->group(function () {
            Route::get('/dashboard', [CollectionController::class, 'dashboard']);
            Route::get('/installments', [CollectionController::class, 'installments']);
            Route::get('/installments/calendar', [CollectionController::class, 'installmentsCalendar']);
            Route::get('/installments/{order}', [CollectionController::class, 'installmentDetail']);
            Route::get('/alerts', [CollectionController::class, 'alerts']);
            Route::get('/flags', [CollectionController::class, 'flags']);
            Route::get('/allocations', [CollectionController::class, 'allocations']);
            Route::get('/promises', [CollectionController::class, 'promises']);
            Route::get('/partial-payments', [CollectionController::class, 'partialPayments']);
            Route::post('/partial-payments/{partialPayment}/status', [CollectionController::class, 'updatePartialPaymentStatus']);
            Route::get('/pending-installments/{user}', [CollectionController::class, 'pendingInstallments']);
        });

        Route::prefix('refund-requests')->middleware('permission:refunds.manage')->group(function () {
            Route::get('/', [RefundRequestController::class, 'index']);
            Route::get('/{status}', [RefundRequestController::class, 'byStatus']);
            Route::patch('/{refundRequest}/status', [RefundRequestController::class, 'updateStatus']);
        });

        Route::prefix('reports')->middleware('permission:reports.view')->group(function () {
            Route::get('/portfolio-performance', [ReportController::class, 'portfolioPerformance']);
            Route::get('/financial-summary', [ReportController::class, 'financialSummary']);
            Route::get('/delinquency-aging', [ReportController::class, 'delinquencyAging']);
            Route::get('/supplier-transaction', [ReportController::class, 'supplierTransaction']);
            Route::get('/collection-efficiency', [ReportController::class, 'collectionEfficiency']);
            Route::get('/risk-exposure', [ReportController::class, 'riskExposure']);
            Route::get('/instalment-repayment', [ReportController::class, 'instalmentRepayment']);
            Route::get('/merchant-credit-history', [ReportController::class, 'merchantCreditHistory']);
            Route::get('/onboarding-funnel', [ReportController::class, 'onboardingFunnel']);
        });

        Route::prefix('claims')->middleware('permission:collections.manage')->group(function () {
            Route::get('/', [ClaimController::class, 'index']);
            Route::post('/', [ClaimController::class, 'store']);
            Route::get('/{claim}', [ClaimController::class, 'show']);
            Route::post('/{claim}/attempt', [ClaimController::class, 'attempt']);
            Route::post('/{claim}/resolve', [ClaimController::class, 'resolve']);
            Route::post('/{claim}/escalate', [ClaimController::class, 'escalate']);
            Route::get('/by-schedule-payment/{schedulePayment}', [ClaimController::class, 'bySchedulePayment']);
        });

        Route::prefix('credit')->middleware('permission:credit.manage')->group(function () {
            Route::get('/profiles', [CreditController::class, 'profiles']);
            Route::get('/limits', [CreditController::class, 'limits']);
            Route::get('/repayment-schedule', [CreditController::class, 'repaymentSchedule']);
            Route::get('/assessment/{customer}', [CreditController::class, 'assessment']);
            Route::put('/limits/{customer}', [CreditController::class, 'updateLimit']);
            Route::get('/customers/search', [CreditController::class, 'searchCustomers']);
        });

        Route::get('/transfers', [TransferController::class, 'index'])
            ->middleware('permission:finance.transfers.manage');
        Route::post('/transfers', [TransferController::class, 'store'])
            ->middleware('permission:finance.transfers.manage');
        Route::post('/transfers/bulk', [TransferController::class, 'bulkStore'])
            ->middleware('permission:finance.transfers.manage');
        Route::get('/transfers/by-model', [TransferController::class, 'byModel'])
            ->middleware('permission:finance.transfers.manage');

        Route::prefix('investment-pools')->middleware('permission:pools.manage')->group(function () {
            Route::get('/', [InvestmentPoolController::class, 'index']);
            Route::post('/', [InvestmentPoolController::class, 'store']);
            Route::get('/{pool}', [InvestmentPoolController::class, 'show']);
            Route::put('/{pool}', [InvestmentPoolController::class, 'update']);
            Route::delete('/{pool}', [InvestmentPoolController::class, 'destroy']);
            Route::get('/calendar-events', [InvestmentPoolController::class, 'calendarEvents']);
        });
    });
});
