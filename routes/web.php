<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeValueController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StateController;
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
            Route::resource('attribute-values', AttributeValueController::class);
            Route::resource('products', ProductController::class);
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

// public function changeLang($locale)
// {
//     session(['locale' => $locale]);
//     App::setLocale($locale);
//     return back();
// }


// <ul>
//     @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
//         <li>
//             <a rel="alternate" hreflang="{{ $localeCode }}"
//                href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
//                 {{ $properties['native'] }}
//             </a>
//         </li>
//     @endforeach
// </ul>



// $(document).on('click', '.delete-btn', function(e) {
//     e.preventDefault();
//     var stateId = $(this).data('id');
//     var url = $(this).attr('href');

//     if (confirm('Are you sure you want to delete this state?')) {
//         $.ajax({
//             url: url,
//             type: 'POST',
//             data: {
//                 '_method': 'DELETE',
//                 '_token': $('meta[name="csrf-token"]').attr('content')
//             },
//             success: function(response) {
//                 alert(response.success);
//                 location.reload(); // Reload the page to reflect changes
//             },
//             error: function(response) {
//                 alert('Something went wrong');
//             }
//         });
//     }
// });
