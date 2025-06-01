<?php

use App\Http\Controllers\{
    AccountController,
    AttributeController,
    AttributeValueController,
    BrandController,
    BusinessCategoryController,
    BusinessTypeController,
    CaseManagementController,
    CategoryController,
    CityController,
    CountryController,
    CouponController,
    CreditManagmentController,
    CustomerAndSalesController,
    DashboardController,
    EmployeeController,
    InstalmentPlanController,
    MediaController,
    OrderController,
    OtpVerificationController,
    PackageController,
    PermissionController,
    ProductBulkUploadController,
    ProductController,
    RealTimeAlertController,
    RefundRequestController,
    ReportController,
    RiskAnalyticsController,
    RiskController,
    RoleController,
    RolePermissionController,
    SchedulePaymentController,
    StateController,
    StaticsController,
    SupplierAndSalesController,
    SupportTicketController,
    TransactionController,
    TransferRequestController,
    UserRoleController,
};
use App\Http\Controllers\ActivityLogsController;
use App\Http\Controllers\FahmanController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\EnsureOtpVerified;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\SecureHeaders;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix'     => LaravelLocalization::setLocale(),
    'middleware' => ThrottleRequests::class,
], function () {

    //
    // Public pages + AJAX lookups
    //
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/',                      'home');
        Route::get('/get-states/{country}',  'getStates');
        Route::get('/get-cities/{state}',    'getCities');
    });

    //
    // Admin area (all routes under /{locale}/admin)
    //
    Route::prefix('admin')
        ->middleware(['auth:sanctum', PreventBackHistory::class, SecureHeaders::class, CheckAdmin::class, config('jetstream.auth_session'), 'verified'])
        ->group(function () {

            //
            // Dashboard
            //
            Route::controller(DashboardController::class)->group(function () {
                Route::get('/dashboard', 'index')->name('dashboard');
                Route::get('/dashboard/data', 'filterData');
            });

            //
            // Role and Permission
            //
            Route::resource('roles', RoleController::class);
            Route::resource('permissions', PermissionController::class);
            Route::resource('role-permissions', RolePermissionController::class);
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
            ]);

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
            Route::get('fahman-results/{id}', [FahmanController::class,'fahmanResults'])->name('fahmanResults');
            Route::get('fahman-details/{id}', [FahmanController::class, 'fahmanDetails']);
            
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
                Route::get('supplier/{id}',        'supplierProfile')->name('supplierProfile');
                Route::get('supplier-transactions/{id}', 'supplierTransactions')->name('supplierTransactions');
                Route::get('supplier-finance/{id}', 'supplierFinance')->name('supplierFinance');
                Route::get('supplier-orders/{id}', 'supplierOrders')->name('supplierOrders');
                Route::get('supplier-payments/{id}', 'supplierPayments')->name('supplierPayments');
                Route::get('supplier-products/{id}', 'supplierProducts')->name('supplierProducts');
                Route::get('supplier-sales/{id}', 'supplierSales')->name('supplierSales');
                Route::put('supplier-status/{id}', 'updateSupplierStatus')->name('updateSupplierStatus');
                Route::get('supplier-compliance/{id}', 'supplierCompliance')->name('supplierCompliance');

                Route::get('customers-statics',    'customersStatics')->name('customers.statics');
                Route::get('suppliers-statics',    'suppliersStatics')->name('suppliers.statics');
            });

            //
            // Risk Analytics
            //
            Route::controller(RiskAnalyticsController::class)->prefix('risk')->group(function () {
                Route::get('score-engine', 'score')->name('risk.score');
                Route::post('score-update', 'scoreUpdate')->name('risk.scoreUpdate')->middleware(EnsureOtpVerified::class);
                Route::get('export/pdf', 'exportPdf')->name('risk.exportPdf');
                Route::get('export/csv', 'exportCsv')->name('risk.exportCsv');
            });

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

                Route::get('/details/{id}',         'orderDetails')->name('orders.details');
                Route::put('/orders/{id}/status', 'updateStatus')->name('order.updateStatus');
                Route::get('/orders/{order}/shipping-label', 'downloadShippingLabel')->name('order.downloadShippingLabel');
                Route::get('/track/{tracking}', 'trackShipment')->name('trackShipment');

                Route::get('order/{orderId}/download-invoice',  'downloadInvoice')->name('order.downloadInvoice');
            });

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
                Route::get('support-ticket/{id}', 'show')->name('showTickets');
                Route::post('/support-ticket/{ticket}/reply', 'reply')->name('ticketReply');
                Route::post('/tickets/{id}/update-status', 'updateStatus')->name('ticketUpdateStatus');
            });

            //
            // Reports
            //
            Route::controller(ReportController::class)->group(function () {
                Route::get('product-stock', 'productStock')->name('productStock');
                Route::get('product-wishlist', 'productWishlist')->name('productWishlist');
                Route::get('user-search', 'userSearch')->name('userSearch');
            });

            //
            // Media management
            //
            Route::controller(MediaController::class)->prefix('media')->group(function () {
                Route::get('/',              'index')->name('media.index');
                Route::get('lazy-load',      'lazyLoad')->name('media.lazyLoad');
                Route::post('upload',        'upload')->name('media.upload');
                Route::post('bulk-delete',   'bulkDelete')->name('media.bulkDelete');
            });
        });
});


Route::get('/google-reviews', [ReportController::class, 'index'])->name('google.reviews.form');
Route::post('/google-reviews', [ReportController::class, 'getReviews'])->name('google.reviews.fetch');
