<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeValueController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\BusinessTypeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstalmentPlanController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RefundRequestController;
use App\Http\Controllers\SchedulePaymentController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\StaticsController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\CheckAdmin;
use App\Models\Media;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    ['prefix' => LaravelLocalization::setLocale()],
    function () {

        Route::get('/', [DashboardController::class, 'home']);

        Route::get('/get-states/{country_id}', [DashboardController::class, 'getStates']);
        Route::get('/get-cities/{state_id}', [DashboardController::class, 'getCities']);


        Route::prefix('admin')->middleware(['auth:sanctum', CheckAdmin::class, config('jetstream.auth_session'), 'verified'])->group(function () {

            Route::controller(DashboardController::class)->group(function () {
                Route::get('/dashboard', 'index')->name('dashboard');
            });

            Route::resource('categories', CategoryController::class);
            Route::resource('brands', BrandController::class);
            Route::resource('countries', CountryController::class);
            Route::resource('states', StateController::class);
            Route::resource('cities', CityController::class);


            Route::resource('attributes', AttributeController::class);
            Route::get('attributes/{attribute}/edit-attribute-value', [AttributeController::class, 'editAttributeValue'])->name('attributes.editAttributeValue');

            Route::resource('attribute-values', AttributeValueController::class);
            Route::resource('products', ProductController::class);
            Route::get('product-approval', [ProductController::class, 'productApproval'])->name('productApproval');
            Route::get('product-reviews', [ProductController::class, 'productReviews'])->name('productReviews');


            Route::resource('coupons', CouponController::class);
            Route::get('/products/{userId}', [CouponController::class, 'getProductsForMerchant']);

            Route::resource('business-types', BusinessTypeController::class);
            Route::resource('business-categories', BusinessCategoryController::class);

            Route::resource('instalment-plans', InstalmentPlanController::class);


            Route::controller(AccountController::class)->group(function () {
                Route::get('customers', 'customers')->name('customers');
                Route::get('suppliers', 'suppliers')->name('suppliers');
                Route::get('supplier/{id}', 'supplierProfile')->name('supplierProfile');
                Route::get('supplier-products/{id}', 'supplierProducts')->name('supplierProducts');
                Route::put('supplier-status/{id}', 'updateSupplierStatus')->name('updateSupplierStatus');

                Route::get('customers-statics', 'customersStatics')->name('customers.statics');
                Route::get('suppliers-statics', 'suppliersStatics')->name('suppliers.statics');
            });

            Route::controller(OrderController::class)->group(function () {
                Route::get('/orders', 'orders')->name('orders');
                Route::get('/processing-orders', 'processing')->name('orders.processing');
                Route::get('/confirmed-orders', 'confirmed')->name('orders.confirmed');
                Route::get('/cancelled-orders', 'cancelled')->name('orders.cancelled');
                Route::get('/failed-orders', 'failed')->name('orders.failed');

                Route::get('/shipping-order/{status}', 'shippingOrder')->name('shippingOrder');
                Route::get('/shipping-orders', 'shippingOrders')->name('shippingOrders');
            });


            Route::controller(TransactionController::class)->group(function () {
                Route::get('/transaction-history', 'transactionHistory')->name('transactionHistory');
                Route::get('/payments', 'payments')->name('payments');
                Route::get('/pending-payments', 'pending')->name('pendingPayments');
                Route::get('/due-payments', 'due')->name('duePayments');
                Route::get('/late-payments', 'late')->name('latePayments');
                Route::get('/paid-payments', 'paid')->name('paidPayments');
            });

            // Route::controller(RefundRequestController::class)->prefix('refund-requests')->group(function () {
            //     Route::get('/', 'refundRequests')->name('refund-requests');
            //     Route::get('refund-requests/{status}', 'showRefundRequests')->name('refund-requests.status');
            //     Route::patch('/{id}/status', 'updateRefundStatus')->name('refund-requests.update-status');
            // });

            // Route::get('/schedule-payments', [SchedulePaymentController::class, 'index'])->name('schedulePayments');
            // Route::get('/{status}/payments', [SchedulePaymentController::class, 'filterByPaymentStatus'])->name('schedulePayment');


            Route::controller(StaticsController::class)
                ->prefix('statics')
                ->group(function () {
                    Route::get('/products', 'products')->name('products.statics');
                    Route::get('/brands', 'brands')->name('brands.statics');
                    Route::get('/categories', 'categories')->name('categories.statics');
                    Route::get('/reviews', 'reviews')->name('reviews.statics');
                });

            // Media routes
            Route::get('/media', [MediaController::class, 'index'])->name('media.index');
            Route::get('/media/lazy-load', [MediaController::class, 'lazyLoad'])->name('media.lazyLoad');

            Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
            Route::post('/media/bulk-delete', [MediaController::class, 'bulkDelete'])->name('media.bulkDelete');
        });


        Route::get('/media-picker', function () {
            $media = Media::latest()->get();
            return view('media.picker', compact('media'));
        })->name('media.picker');

        Route::get('/multi-media-picker', function () {
            $media = Media::latest()->get();
            return view('media.multi-media-picker', compact('media'));
        })->name('media.multi-media-picker');
    }
);
