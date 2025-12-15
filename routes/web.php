<?php

use App\Http\Controllers\{
    ActivityLogsController,
    AccountController,
    AttributeController,
    AttributeValueController,
    BranchController,
    BrandController,
    BusinessCategoryController,
    BusinessTypeController,
    CaseManagementController,
    CategoryController,
    CityController,
    CollectionController,
    CountryController,
    CouponController,
    CreditManagmentController,
    CustomerAndSalesController,
    DashboardController,
    DepartmentController,
    DeviceTokenController,
    DunningTemplateController,
    EmployeeController,
    FahmanController,
    FirebaseController,
    Financial\FinancialAccounts,
    Financial\FinancialTransactions,
    Financial\FinancialDashboardController,
    Financial\ExpenseSettingController,
    Admin\InvestmentPoolsController,
    ChatController,
    InstalmentPlanController,
    LeanController,
    MediaController,
    MerchantUpdateController,
    NoteController,
    NotificationController,
    OrderController,
    OtpVerificationController,
    PackageController,
    PartialPaymentController,
    PasskeyController,
    PermissionController,
    ProductBulkUploadController,
    ProductController,
    PromiseController,
    RealTimeAlertController,
    RefundRequestController,
    ReminderController,
    ReportController,
    RiskAnalyticsController,
    RiskController,
    RiskExportController,
    RiskWeightController,
    RoleController,
    RolePermissionController,
    SanadController,
    SchedulePaymentController,
    SimahController,
    SingleViewController,
    StateController,
    StaticsController,
    SupplierAndSalesController,
    SupplierRoleController,
    SupportTicketController,
    ThirdPatryController,
    TransactionController,
    TransferRequestController,
    UserRoleController,
};
use App\Http\Controllers\Admin\PayoutPortalController;
use App\Http\Controllers\Auth\MicrosoftController;
use App\Http\Middleware\{
    CheckAdmin,
    EnsureOtpVerified,
    PreventBackHistory,
    SecureHeaders
};
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::get('/auth/microsoft/redirect', [MicrosoftController::class, 'redirect'])->name('auth.microsoft.redirect');
Route::get('/auth/microsoft/callback', [MicrosoftController::class, 'callback']);

Route::group([
    'prefix'     => LaravelLocalization::setLocale(),
    'middleware' => ThrottleRequests::class,
], function () {

    //
    // Public pages + AJAX lookups
    //
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/', function () {
            return redirect('/login');
        });
        Route::get('/register', function () {
            return redirect('/login');
        });
        Route::get('/get-states/{country}',  'getStates');
        Route::get('/get-cities/{state}',    'getCities');
    });

    // Add this to your routes/web.php
    Route::get('/admin/chat/debug', function () {
        return response()->json([
            'echo_loaded' => class_exists(\Illuminate\Support\Facades\Broadcast::class),
            'reverb_config' => [
                'app_id' => config('reverb.apps.0.id'),
                'app_key' => config('reverb.apps.0.key'),
                'host' => config('reverb.servers.0.host'),
                'port' => config('reverb.servers.0.port'),
            ],
            'broadcast_driver' => config('broadcasting.default'),
            'auth_user' => Auth::user() ? Auth::user()->id : null,
        ]);
    })->middleware(['auth:sanctum']);


    //
    // Admin area (all routes under /{locale}/admin)
    //
    Route::prefix('admin')
        ->middleware(['auth:sanctum', PreventBackHistory::class, SecureHeaders::class, CheckAdmin::class, config('jetstream.auth_session'), 'verified'])
        ->group(function () {

            // Chat page
            Route::get('/chat/{user}', function (App\Models\User $user) {
                return view('chat.index', ['otherUser' => $user]);
            })->name('chat');

            // Web routes for chat (session auth)
            Route::get('/messages/{user}', [ChatController::class, 'fetchMessages']);
            Route::post('/messages', [ChatController::class, 'sendMessage']);
            Route::post('/messages/{user}/read', [ChatController::class, 'markAsRead']);

            // Typing indicator
            Route::post('/typing', [ChatController::class, 'typing']);
            Route::post('/typing/stop', [ChatController::class, 'stopTyping']);

            // Sidebar AJAX users
            Route::get('/chat-users', [ChatController::class, 'listUsers']);

            Route::post('/device-token', [DeviceTokenController::class, 'store']);

            //
            // Dashboard
            //
            Route::controller(DashboardController::class)->group(function () {
                Route::get('/dashboard', 'index')->name('dashboard');
                Route::get('/dashboard/data', 'filterData');
                Route::get('/impersonate-user/{id}', 'redirectToPartner')->name('impersonate.redirect');
            });


            //
            // Role and Permission
            //
            Route::get('/roles-by-department/{departmentId}', [RolePermissionController::class, 'getRolesByDepartment'])
                ->name('roles.by.department');

            Route::get('/permissions-by-department/{department}/{role}', [RolePermissionController::class, 'getPermissionsByDepartment']);
            Route::get('role-permissions/{role}/{department}/edit', [RolePermissionController::class, 'edit'])->name('role-permissions.edit');
            Route::put('role-permissions/{role}/{department}', [RolePermissionController::class, 'update'])->name('role-permissions.update');
            Route::delete('role-permissions/{role}/{department}', [RolePermissionController::class, 'destroy'])->name('role-permissions.destroy');
            Route::get('role-permissions/create', [RolePermissionController::class, 'create'])->name('role-permissions.create');
            Route::post('role-permissions', [RolePermissionController::class, 'store'])->name('role-permissions.store');
            Route::get('role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');

            Route::resource('roles', RoleController::class);
            Route::resource('permissions', PermissionController::class);
            // Route::resource('role-permissions', RolePermissionController::class);
            Route::get('user-roles', [UserRoleController::class, 'index'])->name('user-roles.index');
            Route::get('user-roles/create', [UserRoleController::class, 'create'])->name('user-roles.create');
            Route::post('user-roles', [UserRoleController::class, 'store'])->name('user-roles.store');
            Route::get('user-roles/{user}/edit', [UserRoleController::class, 'edit'])->name('user-roles.edit');
            Route::put('user-roles/{user}', [UserRoleController::class, 'update'])->name('user-roles.update');

            //
            // Request Transfer and managment
            //
            Route::get('/transfer-requests', [TransferRequestController::class, 'index'])->name('transferRequests.index');
            Route::post('/transfer-requests', [TransferRequestController::class, 'store'])->name('transfer-requests.store');
            Route::post('/transfer-requests/bulk', [TransferRequestController::class, 'bulkStore'])
                ->name('transfer-requests.bulk');
            Route::get('/get-transfer-requests', [TransferRequestController::class, 'fetch'])->name('transfer.requests.fetch');

            // Product bulk upload
            Route::get('/products/bulk-upload', [ProductBulkUploadController::class, 'bulkUploadForm'])->name('productsBulkUpload');
            Route::post('/products/bulk-upload', [ProductBulkUploadController::class, 'bulkUpload'])->name('products.bulk-upload');
            Route::post('/products/bulk-upload/store', [ProductBulkUploadController::class, 'bulkStore'])->name('productsBulkStore');

            Route::get('/departments/{department}/access', [EmployeeController::class, 'getDepartmentAccess'])
                ->name('departments.access');

            Route::get('/get-category-units/{id}', [CategoryController::class, 'getUnits']);
            Route::get('categories/search', [CategoryController::class, 'search'])->name('categories.search');
            Route::get('brands/search', [BrandController::class, 'search'])->name('brands.search');

            //
            // Master-data CRUD
            //
            Route::resources([
                'categories'        => CategoryController::class,
                'brands'            => BrandController::class,
                'countries'         => CountryController::class,
                'states'            => StateController::class,
                'cities'            => CityController::class,
                'attributes'        => AttributeController::class,
                'attribute-values'  => AttributeValueController::class,
                'products'          => ProductController::class,
                'coupons'           => CouponController::class,
                'business-types'    => BusinessTypeController::class,
                'business-categories' => BusinessCategoryController::class,
                'instalment-plans'  => InstalmentPlanController::class,
                'packages'          => PackageController::class,
                'employees'         => EmployeeController::class,
                'risk-register'     => RiskController::class,
                'case-management'     => CaseManagementController::class,
                'activity-logs'     => ActivityLogsController::class,
                'departments'       => DepartmentController::class,
                'supplier_roles'    => SupplierRoleController::class,

            ]);

            //
            // Financial Management Routes
            //
            Route::prefix('financial')->name('financial.')->group(function () {
                Route::get('/', [FinancialDashboardController::class, 'index'])->name('dashboard');
                Route::get('/dashboard/chart-data', [FinancialDashboardController::class, 'getChartDataJson'])->name('dashboard.chart-data');
                Route::get('/trial-balance', [FinancialDashboardController::class, 'trialBalance'])->name('trial-balance');
                Route::get('/trial-balance/export', [FinancialDashboardController::class, 'exportTrialBalance'])->name('trial-balance.export');
                Route::resource('accounts', FinancialAccounts::class);
                Route::get('accounts/{account}/ledger', [FinancialAccounts::class, 'ledger'])->name('accounts.ledger');
                Route::resource('transactions', FinancialTransactions::class);
                Route::get('transactions/{id}/modal-data', [FinancialTransactions::class, 'getModalData'])->name('transactions.modal-data');
                Route::resource('expense-settings', ExpenseSettingController::class);
            });

            //
            // Investment Pools Routes
            //
            Route::prefix('investment-pools')->name('investment-pools.')->group(function () {
                Route::get('/calendar', [InvestmentPoolsController::class, 'calendar'])->name('calendar');
                Route::get('/calendar-events', [InvestmentPoolsController::class, 'calendarEvents'])->name('calendar-events');
                Route::post('/', [InvestmentPoolsController::class, 'store'])->name('store');
                Route::get('/{pool}', [InvestmentPoolsController::class, 'show'])->name('show');
                Route::put('/{pool}', [InvestmentPoolsController::class, 'update'])->name('update');
                Route::delete('/{pool}', [InvestmentPoolsController::class, 'destroy'])->name('destroy');
            });

            //
            // Claims Routes
            //
            Route::prefix('claims')->name('claims.')->controller(\App\Http\Controllers\Admin\ClaimsController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{claim}', 'show')->name('show');
                Route::post('/{claim}/attempt', 'updateAttempt')->name('attempt');
                Route::post('/{claim}/resolve', 'resolve')->name('resolve');
                Route::post('/{claim}/escalate', 'escalate')->name('escalate');
                Route::get('/schedule-payment/{schedulePayment}', 'forSchedulePayment')->name('schedule-payment');
                Route::get('/schedule-payment/{schedulePayment}/details', 'showSchedulePayment')->name('schedule-payment.details');
            });

            //
            // Checkout Routes  
            //
            Route::prefix('checkouts')->name('checkouts.')->controller(\App\Http\Controllers\Admin\CheckoutController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{checkout}', 'show')->name('show');
                Route::get('/{checkout}/edit', 'edit')->name('edit');
                Route::put('/{checkout}', 'update')->name('update');
                Route::delete('/{checkout}', 'destroy')->name('destroy');
                Route::get('/{checkout}/summary', 'summary')->name('summary');
                Route::get('/{checkout}/timeline', 'timeline')->name('timeline');
            });

            // Branch Routes
            Route::prefix('branches')->name('branches.')->controller(BranchController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{branch}/edit', 'edit')->name('edit');
                Route::put('/{branch}', 'update')->name('update');
                Route::delete('/{branch}', 'destroy')->name('destroy');
            });

            // one-off attribute route
            Route::get(
                'attributes/{attribute}/edit-attribute-value',
                [AttributeController::class, 'editAttributeValue']
            )->name('attributes.editAttributeValue');

            // product-specific extras
            Route::get('product-approval',      [ProductController::class, 'productApproval'])->name('productApproval');
            Route::get('product-reviews',       [ProductController::class, 'productReviews'])->name('productReviews');

            // coupon → merchant products
            Route::get('products/{userId}',     [CouponController::class, 'getProductsForMerchant']);

            //
            // Account management
            //
            Route::get('fahman-results/{id}', [FahmanController::class, 'fahmanResults'])->name('fahmanResults');
            Route::get('fahman-details/{id}', [FahmanController::class, 'fahmanDetails']);
            Route::get('fahman-supplier-results/{id}', [FahmanController::class, 'fahmanSupplierResults'])->name('fahmanSupplierResults');
            Route::get('fahman-supplier-details/{id}', [FahmanController::class, 'fahmanSupplierDetails']);

            Route::controller(AccountController::class)->group(function () {
                Route::get('customers',            'customers')->name('customers');
                Route::get('customer/{id}',        'customerProfile')->name('customerProfile');
                Route::get('customer-business/{id}',        'customerBusiness')->name('customerBusiness');
                Route::get('customer-simah/{id}',        'customerSimah')->name('customerSimah');
                Route::get('customer-finance/{id}',        'customerFinance')->name('customerFinance');
                Route::get('customer-transactions/{id}', 'transactions')->name('customerTransactions');
                Route::get('customer-orders/{id}', 'orders')->name('customerOrders');
                Route::get('customer-payments/{id}', 'payments')->name('customerPayments');
                Route::get('customer-compliance/{id}', 'customerCompliance')->name('customerCompliance');
                Route::get('customer-log/{id}', 'log')->name('customerLog');
                Route::get('customer-credit-assesment/{id}', 'customerCreditAssessment')->name('customerCreditAssessment');

                Route::put('customer-status/{id}', 'updateCustomerStatus')->name('updateCustomerStatus');
                Route::post('customer/{user}/upgrade-package', 'upgradePackage')->name('updateCustomerPackage');
                Route::post('customer/upgrade-limit', 'upgradeLimit')->name('customerUpgradeLimit');
                Route::post('customer/create-limit', 'createCreditLimit')->name('createCreditLimit');
                Route::get('custoemr-transactions', 'transactions')->name('transactions');

                Route::get('suppliers',            'suppliers')->name('suppliers');
                Route::post('update-commission',            'updateCommission')->name('updateCommission');
                Route::get('supplier/{id}',        'supplierProfile')->name('supplierProfile');
                Route::get('supplier-shop-settings/{id}',        'supplierShop')->name('supplierShop');
                Route::post('shop-settings', 'supplierShopSubmit')->name('supplierShopSubmit');
                Route::get('supplier-transactions/{id}', 'supplierTransactions')->name('supplierTransactions');
                Route::get('supplier-finance/{id}', 'supplierFinance')->name('supplierFinance');
                Route::get('supplier-orders/{id}', 'supplierOrders')->name('supplierOrders');
                Route::get('supplier-payments/{id}', 'supplierPayments')->name('supplierPayments');
                Route::get('supplier-products/{id}', 'supplierProducts')->name('supplierProducts');
                Route::get('supplier-sales/{id}', 'supplierSales')->name('supplierSales');
                Route::put('supplier-status/{id}', 'updateSupplierStatus')->name('updateSupplierStatus');
                Route::put('supplier-status/approve/{id}', 'updateSupplierStatusApprove')->name('updateSupplierStatusApprove');
                Route::get('supplier-compliance/{id}', 'supplierCompliance')->name('supplierCompliance');

                Route::get('customers-statics',    'customersStatics')->name('customers.statics');
                Route::get('suppliers-statics',    'suppliersStatics')->name('suppliers.statics');

                Route::get('nafath', 'nafath')->name('nafath');
            });

            //
            // LEAN routes
            //
            Route::controller(LeanController::class)->prefix('lean')->name('lean.')->group(function () {
                Route::get('/{id}', 'index')->name('index');
                Route::get('/{id}/banks', 'getBanks')->name('banks');
                Route::get('/{id}/entities', 'getEntities')->name('entities');
                Route::get('/{id}/bank-statement/{reportId}', 'getBankStatement')->name('bank-statement');

                Route::post('/test-connection', 'testConnection')->name('test-connection');
                Route::post('/clear-cache', 'clearCache')->name('clear-cache');
            });

            //
            // SIMAH routes
            //
            Route::post('admin/accounts/customer-simah/fetch', [SimahController::class, 'fetchCustomerSimah'])
                ->name('customer.simah.fetch');
            Route::post('/customer/simah/consumer-score', [SimahController::class, 'fetchConsumerScore'])
                ->name('customer.simah.consumer-score');

            //
            // Single View
            //
            Route::controller(SingleViewController::class)->prefix('singleview')->group(function () {
                Route::get('/{id}', 'index')->name('singleview.index');
                Route::get('/{id}/fetch-accounts', 'fetchAccountsAjax')->name('singleview.fetchAccounts');
                Route::get('/{id}/fetch-accounts-balance', 'fetchAccountsBalance')->name('singleview.fetchAccountsBalance');
                Route::get('/{id}/fetch-credit-check', 'fetchCreditCheck')->name('singleview.fetchCreditCheck');

                Route::post('/consent', 'createConsent')->name('singleview.createConsent');
                Route::get('/consent/{bankCode}/{consentId}', 'getConsentDetails')->name('singleview.getConsentDetails');
                Route::get('/accounts/{bankCode}/{consentId}', 'getAccounts')->name('singleview.getAccounts');
                Route::get('/e-statements/{bankCode}/{consentId}/{accountId}', 'getEStatements')->name('singleview.getEStatements');
                Route::get('/credit-check-basic/{bankCode}/{consentId}', 'creditCheckBasic')->name('singleview.creditCheckBasic');
                Route::get('/credit-check-advanced/{bankCode}/{consentId}', 'creditCheckAdvanced')->name('singleview.creditCheckAdvanced');

                Route::get('/{id}/fetch-income-check-advanced', 'fetchIncomeCheckAdvanced')->name('singleview.fetchIncomeCheckAdvanced');
                Route::get('/income-check-advanced/{bankCode}/{consentId}', 'incomeCheckAdvanced')->name('singleview.incomeCheckAdvanced');
            });

            //
            // Risk Analytics
            //
            Route::controller(RiskAnalyticsController::class)->prefix('risk')->group(function () {
                Route::get('dashboard', 'dashboard')->name('risk.dashboard');
                Route::get('alerts', 'allAlerts')->name('risk.alerts');
                // Route::get('score-engine', 'score')->name('risk.merchantScore');
                Route::post('score-update', 'scoreUpdate')->name('risk.merchantScoreUpdate')->middleware(EnsureOtpVerified::class);
                Route::get('export/pdf', 'exportPdf')->name('risk.exportPdf');
                Route::get('export/csv', 'exportCsv')->withoutMiddleware([PreventBackHistory::class])->name('risk.exportCsv');

                Route::get('/merchant-score', 'merchantScore')->name('risk.merchantScore');

                // Risk Analysis Routes
                Route::get('/risk-analysis/{user}/{type}', 'show')->name('risk.analysis.details');
                Route::get('/risk-analysis/{user}/{type}/components', 'components')->name('risk.analysis.components');
            });

            Route::controller(RiskExportController::class)->prefix('risk')->group(function () {
                Route::post('risk/export', 'startExport')->name('risk.export');
                Route::get('risk/export/status/{id}', 'status')->name('risk.export.status');
                Route::get('risk/export/download/{id}', 'download')->name('risk.export.download');
            });

            Route::prefix('risk-weights')->group(function () {
                Route::post('/', [RiskWeightController::class, 'store'])->name('risk-weights.store');
                Route::get('/get', [RiskWeightController::class, 'getWeights'])->name('risk-weights.get');
                Route::post('/reset', [RiskWeightController::class, 'resetToDefault'])->name('risk-weights.reset');
                Route::get('/history', [RiskWeightController::class, 'getWeightHistory'])->name('risk-weights.history');
            });


            //
            // Collection Department
            //
            Route::prefix('collections')->as('collections.')->controller(CollectionController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/installments', 'installments')->name('installments');
                Route::get('/installments-calander', 'installmentsCalander')->name('installmentsCalander');
                Route::get('/installment/{id}', 'installmentDetails')->name('installmentDetails');
                Route::get('/promise-to-pay', 'promisetopay')->name('promisetopay');
                Route::get('/allocations', 'allocations')->name('allocations');
                Route::get('/penalties', 'penalties')->name('penalties');
                Route::get('alerts', 'viewAlerts')->name('alerts');
                Route::get('flags', 'viewFlags')->name('flags');
                Route::get('partial-payments', 'partialPayments')->name('partialPayments');
                Route::post('partial-payments/update-status', 'updatePartialPaymentStatus')->name('partialPayments.updateStatus');
            });
            Route::post('/reminders/send', [ReminderController::class, 'send'])->name('reminders.send');

            Route::get('promisetopay/unpaid-installments/{user}', [CollectionController::class, 'getUnpaidInstallments']);

            Route::get('/dunning-templates/{id}', [DunningTemplateController::class, 'show'])->name('dunning.template.show');
            Route::post('/dunning-templates/{id}', [DunningTemplateController::class, 'update'])->name('dunning.update');
            Route::prefix('dunning')->name('dunning.')->group(function () {
                Route::get('/', [DunningTemplateController::class, 'index'])->name('index');
                Route::post('/store', [DunningTemplateController::class, 'store'])->name('store');

                Route::delete('/delete/{id}', [DunningTemplateController::class, 'destroy'])->name('destroy');
            });

            Route::get('/user/{userId}/notes', [NoteController::class, 'index'])->name('notes.index');
            Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
            Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
            Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

            Route::post('/promises', [PromiseController::class, 'store'])->name('promises.store');
            Route::put('/promises/{promise}', [PromiseController::class, 'update'])->name('promises.update');
            Route::delete('/promises/{promise}', [PromiseController::class, 'destroy'])->name('promises.destroy');

            Route::prefix('partial-payments')->controller(PartialPaymentController::class)->group(function () {
                Route::post('/', 'store')->name('partial-payments.store');
                Route::get('/{id}/edit', 'edit')->name('partial-payments.edit');
                Route::put('/{id}', 'update')->name('partial-payments.update');
                Route::delete('/{id}', 'destroy')->name('partial-payments.destroy');
            });

            Route::put('schedule-payments/{id}', [SchedulePaymentController::class, 'update'])
                ->name('schedule-payments.update');

            Route::post('/schedule-payments/pay-now', [SchedulePaymentController::class, 'payNow'])
                ->name('schedule-payments.pay-now');

            //
            // Activity logs
            //
            Route::controller(ActivityLogsController::class)->prefix('activity-logs')->group(function () {
                Route::get('/', 'index')->name('activity-logs.index');
                Route::get('/export/csv', 'exportCsv')->name('activity-logs.exportCsv');
                Route::get('/export/pdf', 'exportPdf')->name('activity-logs.exportPdf');
            });

            Route::get('/otp/send', [OtpVerificationController::class, 'send'])->name('otp.send');
            Route::get('otp', [OtpVerificationController::class, 'showVerifyForm'])->name('otp.verify.form');
            Route::post('/otp/verify', [OtpVerificationController::class, 'verifyOtp'])->name('otp.verify.confirm');


            Route::get('real-time-alerts', [RealTimeAlertController::class, 'index'])
                ->name('real-time-alerts.index');

            //
            // Credit Managment
            //
            Route::controller(CreditManagmentController::class)->prefix('credit')->group(function () {
                Route::get('credit-profiles', 'creditProfile')->name('creditProfile');
                Route::get('credit-limit', 'creditLimit')->name('creditLimit');
                Route::get('repayment-schedule', 'repaymentSchedule')->name('repaymentSchedule');
                Route::get('export/pdf', 'exportPdf')->name('credit.exportPdf');
                Route::get('export/csv', 'exportCsv')->name('credit.exportCsv');
            });

            //
            // Orders + shipping
            //
            Route::controller(OrderController::class)->prefix('orders')->group(function () {
                Route::get('/',               'orders')->name('orders');
                Route::get('processing',      'processing')->name('orders.processing');
                Route::get('confirmed',       'confirmed')->name('orders.confirmed');
                Route::get('cancelled',       'cancelled')->name('orders.cancelled');
                Route::get('failed',          'failed')->name('orders.failed');

                Route::get('shipping-orders',           'shippingOrders')->name('shippingOrders');
                Route::get('shipping-order/{status}',   'shippingOrder')->name('shippingOrder');

                // Modal data endpoint for AJAX
                Route::get('{id}/modal-data', 'getModalData')->name('orders.modal-data');

                Route::get('/details/{id}',         'orderDetails')->name('orders.details');
                Route::put('/orders/{id}/status', 'updateStatus')->name('order.updateStatus');
                Route::get('/orders/{order}/shipping-label', 'downloadShippingLabel')->name('order.downloadShippingLabel');
                Route::get('/track/{tracking}', 'trackShipment')->name('trackShipment');

                Route::get('order/{orderId}/download-invoice',  'downloadInvoice')->name('order.downloadInvoice');

                Route::post('/orders/accept', 'acceptOrder')->name('orders.accept');
                Route::post('/orders/reject', 'rejectOrder')->name('orders.reject');
            });

            Route::get('/sanad/order/{order}/detail', [SanadController::class, 'detail'])->name('sanad.detail');
            Route::get('/sanad/order/{order}/download', [SanadController::class, 'download'])->name('sanad.download');

            //
            // Supplier and Sales
            //
            Route::controller(SupplierAndSalesController::class)->group(function () {
                Route::get('supplier-detail-purchases', 'detailPurchases')->name('detailPurchases');
                Route::get('supplier-total-purchases', 'totalPurchases')->name('totalPurchases');
                Route::get('supplier-payment-of-supplier', 'paymentOfSupplier')->name('paymentOfSupplier');
                Route::get('supplier-detailed-debt', 'detailedSupplierDebt')->name('detailedSupplierDebt');
                Route::get('supplier-total-debt', 'totalSupplierDebt')->name('totalSupplierDebt');

                Route::get('supplier-entitilements', 'supplierEntitilements')->name('supplierEntitilements');
                Route::post('seller-payment-from-admin', 'sellerPaymentFromAdmin')->name('sellerPaymentFromAdmin');

                Route::get('supplier-accounts', 'supplierAccounts')->name('supplierAccounts');
                Route::get('supplier-payouts', 'supplierPayouts')->name('supplierPayouts');
            });

            //
            // Customer and Sales
            //
            Route::controller(CustomerAndSalesController::class)->group(function () {
                Route::get('customer-sale-report', 'saleReport')->name('saleReport');
                Route::get('customer-total-sale-report', 'totalSaleReport')->name('totalSaleReport');
                Route::get('customer-collection-report', 'collectionReport')->name('collectionReport');
                Route::get('customer-detailed-debt', 'detailedCustomerDebt')->name('detailedCustomerDebt');
                Route::get('customer-total-customer-debt', 'totalCustomerDebt')->name('totalCustomerDebt');
            });

            //
            // Transactions
            //
            Route::controller(TransactionController::class)->prefix('transactions')->group(function () {
                Route::get('history',         'transactionHistory')->name('transactionHistory');
                Route::get('payments',        'payments')->name('payments');
                Route::get('pending',         'pending')->name('pendingPayments');
                Route::get('due',             'due')->name('duePayments');
                Route::get('late',            'late')->name('latePayments');
                Route::get('paid',            'paid')->name('paidPayments');

                Route::get('wallet', 'wallet')->name('wallet');
                Route::get('invoice/generate/{order}', 'generate')->name('merchant.invoice.generate');
            });

            //
            // Refund requests
            //
            Route::controller(RefundRequestController::class)->prefix('refund-requests')->group(function () {
                Route::get('/',               'refundRequests')->name('refund-requests');
                Route::get('{status}',        'showRefundRequests')->name('refund-requests.status');
                Route::patch('{id}/status',   'updateRefundStatus')->name('refund-requests.update-status');
            });

            //
            // Scheduled payments
            //
            Route::controller(SchedulePaymentController::class)->prefix('schedule-payments')->group(function () {
                Route::get('/',               'index')->name('schedulePayments');
                Route::get('{status}',        'filterByPaymentStatus')->name('schedulePayment');
                Route::get('{schedulePayment}/details', 'show')->name('schedulePayments.details');
                Route::get('{schedulePayment}/payment-json', 'paymentJson')->name('schedulePayments.payment.json');
            });

            //
            // Supplier Payouts Portal
            //
            Route::prefix('payouts')->name('payouts.')->controller(PayoutPortalController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::post('/{payout}/complete', 'markCompleted')->name('complete');
                Route::post('/{payout}/fail', 'markFailed')->name('fail');
                Route::get('/status-by-order/{order}', 'statusByOrder')->name('status-by-order');
                Route::get('/{payout}/details-modal-data', 'detailsModalData')->name('details-modal-data');
            });

            //
            // Statics
            //
            Route::controller(StaticsController::class)->prefix('statics')->group(function () {
                Route::get('products',       'products')->name('products.statics');
                Route::get('brands',         'brands')->name('brands.statics');
                Route::get('categories',     'categories')->name('categories.statics');
                Route::get('reviews',        'reviews')->name('reviews.statics');
            });

            //
            // Support Tickets
            //
            Route::controller(SupportTicketController::class)->group(function () {
                Route::get('support-tickets', 'index')->name('tickets');
                Route::get('support-tickets-create', 'create')->name('ticketCreate');
                Route::post('support-tickets-store', 'store')->name('ticketStore');
                Route::get('support-ticket/{id}', 'show')->name('showTickets');
                Route::post('/support-ticket/{ticket}/reply', 'reply')->name('ticketReply');
                Route::post('/tickets/{id}/update-status', 'updateStatus')->name('ticketUpdateStatus');

                // internel tickets
                Route::get('internel-tickets', 'internelTickets')->name('internelTickets');
            });

            //
            // Reports
            //
            Route::controller(ReportController::class)->prefix('reports')->group(function () {
                Route::get('product-stock', 'productStock')->name('productStock');
                Route::get('product-wishlist', 'productWishlist')->name('productWishlist');
                Route::get('user-search', 'userSearch')->name('userSearch');

                // Report routes
                Route::get('portfolio-performance', 'portfolioPerformanceReport')->name('portfolioPerformanceReport');
                Route::get('merchant-credit-history', 'merchantCreditHistoryReport')->name('merchantCreditHistoryReport');
                Route::get('supplier-transaction', 'supplierTransactionReport')->name('supplierTransactionReport');
                Route::get('instalment-repayment', 'instalmentRepaymentReport')->name('instalmentRepaymentReport');
                Route::get('risk-exposure', 'riskExposureAnalysis')->name('riskExposureAnalysis');
                Route::get('aml-activity', 'amlActivityReport')->name('amlActivityReport');
                Route::get('collection-efficiency', 'collectionEfficiencyReport')->name('collectionEfficiencyReport');
                Route::get('onboarding-funnel', 'onboardingFunnelReport')->name('onboardingFunnelReport');
                Route::get('regulatory-compliance', 'regulatoryComplianceReport')->name('regulatoryComplianceReport');
                Route::get('system-activity-audit', 'systemActivityAuditReport')->name('systemActivityAuditReport');
                Route::get('financial-summary', 'financialSummaryReport')->name('financialSummaryReport');
                Route::get('delinquency-aging', 'delinquencyAgingReport')->name('delinquencyAgingReport');
                Route::get('product-sku-performance', 'productSkuPerformanceReport')->name('productSkuPerformanceReport');
                Route::get('support-ticket-resolution', 'supportTicketResolutionReport')->name('supportTicketResolutionReport');
                Route::get('campaign-effectiveness', 'campaignEffectivenessReport')->name('campaignEffectivenessReport');
            });

            //
            // Media management
            //
            Route::controller(MediaController::class)->prefix('media')->group(function () {
                Route::get('/',              'index')->name('media.index');
                Route::get('lazy-load',      'lazyLoad')->name('media.lazyLoad');
                Route::post('upload',        'upload')->name('media.upload');
                Route::post('bulk-delete',   'bulkDelete')->name('media.bulkDelete');
                Route::get('refresh', 'refresh')->name('media.refresh');
            });

            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('notifications/{id}/read', [NotificationController::class, 'markOneRead'])->name('notifications.markOneRead');

            Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
            Route::delete('/notifications/delete-all', [NotificationController::class, 'deleteAll'])->name('notifications.deleteAll');
        });

    // Third party api control
    Route::get('/third-party', [ThirdPatryController::class, 'index'])->name('thirdParty.index');

    Route::get('/merchants', [MerchantUpdateController::class, 'index'])->name('merchants.index');
    Route::get('/merchants/search', [MerchantUpdateController::class, 'search'])->name('merchants.search');
    Route::post('/merchants/{id}/update', [MerchantUpdateController::class, 'update'])->name('merchants.update');
});

//
// Firebase Realtime Notification
//

Route::post('/send-fcm', [FirebaseController::class, 'sendNotification']);

Route::get('/fcm-test', function () {
    return view('fcm');
})->name('fcm');

Route::get('/send-fcm', function () {
    return view('send-fcm');
})->name('fcm.send');


Route::get('/google-reviews', [ReportController::class, 'index'])->name('google.reviews.form');
Route::post('/google-reviews', [ReportController::class, 'getReviews'])->name('google.reviews.fetch');

Route::middleware(['auth'])->group(function () {
    Route::get('/passkeys-register', [PasskeyController::class, 'create'])->name('passkeys.create');
    Route::post('/passkeys-register', [PasskeyController::class, 'store'])->name('passkeys.store');
    Route::get('/passkeys/manage', [PasskeyController::class, 'manage'])->name('passkeys.manage');
    Route::delete('/passkeys/{id}', [PasskeyController::class, 'destroy'])->name('passkeys.destroy');
    Route::post('/passkeys/registration-options', [PasskeyController::class, 'getRegistrationOptions'])->name('passkeys.registrationOptions');
});

Route::get('/passkeys-login', [PasskeyController::class, 'login'])->name('passkeys.login');
Route::post('/passkeys-phone', [PasskeyController::class, 'getPublicKey'])->name('passkeys.getPublicKey');
Route::post('/passkeys-login', [PasskeyController::class, 'authenticate'])->name('passkeys.authenticate');

use App\Http\Controllers\EmailController;
use App\Http\Controllers\SmsController;

Route::get('/send-email', [EmailController::class, 'create'])->name('email.create');
Route::post('/send-email', [EmailController::class, 'send'])->name('email.send');
Route::get('/send-sms', [SmsController::class, 'create'])->name('sms.create');
Route::post('/send-sms', [SmsController::class, 'send'])->name('sms.send');

// routes/web.php
require __DIR__ . '/test.php';
require __DIR__ . '/setting.php';
