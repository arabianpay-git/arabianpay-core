<?php

use App\Http\Controllers\{
    AccountController,
    AttributeController,
    AttributeValueController,
    BrandController,
    BusinessCategoryController,
    BusinessTypeController,
    CategoryController,
    CityController,
    CountryController,
    CouponController,
    DashboardController,
    InstalmentPlanController,
    MediaController,
    OrderController,
    PackageController,
    ProductController,
    RefundRequestController,
    SchedulePaymentController,
    StateController,
    StaticsController,
    SupplierAndSalesController,
    TransactionController,
};
use App\Http\Middleware\CheckAdmin;
use App\Models\Media;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix'     => LaravelLocalization::setLocale()
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
        ->middleware(['auth:sanctum', CheckAdmin::class, config('jetstream.auth_session'), 'verified'])
        ->group(function () {

            //
            // Dashboard
            //
            Route::controller(DashboardController::class)->group(function () {
                Route::get('dashboard', 'index')->name('dashboard');
                Route::get('/dashboard/data', 'filterData');
            });

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
            Route::controller(AccountController::class)->group(function () {
                Route::get('customers',            'customers')->name('customers');
                Route::get('customer/{id}',        'customerProfile')->name('customerProfile');
                Route::get('customer-business/{id}',        'customerBusiness')->name('customerBusiness');
                Route::get('customer-simah/{id}',        'customerSimah')->name('customerSimah');
                Route::get('customer-finance/{id}',        'customerFinance')->name('customerFinance');
                Route::get('customer-transactions/{id}', 'transactions')->name('customerTransactions');
                Route::get('customer-orders/{id}', 'orders')->name('customerOrders');
                Route::get('customer-payments/{id}', 'payments')->name('customerPayments');

                Route::put('customer-status/{id}', 'updateCustomerStatus')->name('updateCustomerStatus');
                Route::post('customer/{user}/upgrade-package', 'upgradePackage')->name('updateCustomerPackage');
                Route::post('customer/upgrade-limit', 'upgradeLimit')->name('customerUpgradeLimit');
                Route::post('customer/create-limit', 'createCreditLimit')->name('createCreditLimit');
                Route::get('custoemr-transactions', 'transactions')->name('transactions');


                Route::get('suppliers',            'suppliers')->name('suppliers');
                Route::get('supplier/{id}',        'supplierProfile')->name('supplierProfile');
                Route::get('supplier-products/{id}', 'supplierProducts')->name('supplierProducts');
                Route::put('supplier-status/{id}', 'updateSupplierStatus')->name('updateSupplierStatus');

                Route::get('customers-statics',    'customersStatics')->name('customers.statics');
                Route::get('suppliers-statics',    'suppliersStatics')->name('suppliers.statics');
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
                Route::get('detail-purchases', 'detailPurchases')->name('detailPurchases');
                Route::get('total-purchases', 'totalPurchases')->name('totalPurchases');
                Route::get('payment-of-supplier', 'paymentOfSupplier')->name('paymentOfSupplier');
                Route::get('detailed-supplier-debt', 'detailedSupplierDebt')->name('detailedSupplierDebt');
                Route::get('total-supplier-debt', 'totalSupplierDebt')->name('totalSupplierDebt');
                Route::get('supplier-account-statment', 'supplierAccountStatment')->name('supplierAccountStatment');
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
