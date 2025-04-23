<?php

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
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\StaticsController;
use App\Models\Media;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    ['prefix' => LaravelLocalization::setLocale()],
    function () {

        Route::get('/', [DashboardController::class, 'home']);

        Route::get('/get-states/{country_id}', [DashboardController::class, 'getStates']);
        Route::get('/get-cities/{state_id}', [DashboardController::class, 'getCities']);


        Route::prefix('admin')->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

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

            Route::resource('intalment-plans', InstalmentPlanController::class);






            Route::controller(StaticsController::class)
                ->prefix('statics')
                ->group(function () {
                    Route::get('/products', 'products')->name('products.statics');
                    Route::get('/brands', 'brands')->name('brands.statics');
                    Route::get('/categories', 'categories')->name('categories.statics');
                    Route::get('/reviews', 'reviews')->name('reviews.statics');
                });
        });

        // Media routes
        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::get('/media/lazy-load', [MediaController::class, 'lazyLoad'])->name('media.lazyLoad');

        Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
        Route::post('/media/bulk-delete', [MediaController::class, 'bulkDelete'])->name('media.bulkDelete');

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
