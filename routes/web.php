<?php

use App\Http\Controllers\{
    ActivityLogsController,
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
    DepartmentController,
    DeviceTokenController,
    EmployeeController,
    FahmanController,
    FirebaseController,
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
use App\Http\Middleware\{
    CheckAdmin,
    EnsureOtpVerified,
    PreventBackHistory,
    SecureHeaders
};
use App\Models\{
    Customer,
    Media,
    Merchant,
    SupplierBank,
};
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
        Route::get('/', function () {
            return redirect('/login');
        });
        Route::get('/register', function () {
            return redirect('/login');
        });
        Route::get('/get-states/{country}',  'getStates');
        Route::get('/get-cities/{state}',    'getCities');
    });

    //
    // Admin area (all routes under /{locale}/admin)
    //
    Route::prefix('admin')
        ->middleware(['auth:sanctum', PreventBackHistory::class, SecureHeaders::class, CheckAdmin::class, config('jetstream.auth_session'), 'verified'])
        ->group(function () {

            Route::post('/device-token', [DeviceTokenController::class, 'store']);


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
            // Risk Analytics
            //
            Route::controller(RiskAnalyticsController::class)->prefix('risk')->group(function () {
                Route::get('score-engine', 'score')->name('risk.score');
                Route::post('score-update', 'scoreUpdate')->name('risk.scoreUpdate')->middleware(EnsureOtpVerified::class);
                Route::get('export/pdf', 'exportPdf')->name('risk.exportPdf');
                Route::get('export/csv', 'exportCsv')->withoutMiddleware([PreventBackHistory::class])->name('risk.exportCsv');
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

//
// Firebase Realtime Notification
//

Route::post('/send-fcm', [FirebaseController::class, 'sendNotification']);

Route::get('/fcm-test', function () {
    return view('fcm');
})->name('fcm');


Route::get('/google-reviews', [ReportController::class, 'index'])->name('google.reviews.form');
Route::post('/google-reviews', [ReportController::class, 'getReviews'])->name('google.reviews.fetch');

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Route::get('/merchant-transfer', function () {
//     $key = base64_decode('dlzHBZOPN+4ZZ2Jnfkll/iVUJ1GuwfwCRnvxxuCMXdg=');
//     $iv = base64_decode('l2kMAFuEh7jazjgDCfIKUg==');

//     $users = DB::connection('arabianoay_old')
//         ->table('users')
//         ->where('user_type', 'seller')
//         ->get();

//     $decryptedData = [];
//     $submittedCount = 0;
//     $skippedCount = 0;
//     $skippedEntries = [];

//     foreach ($users as $user) {
//         $decryptedName  = decryptWithArabicSupport($user->name, $key, $iv);
//         $decryptedPhone = decryptWithArabicSupport($user->phone, $key, $iv);

//         // Split full name into first_name and last_name
//         $firstName = $decryptedName;
//         $lastName = null;

//         if (is_string($decryptedName)) {
//             $nameParts = explode(' ', trim($decryptedName), 2);
//             $firstName = $nameParts[0] ?? '';
//             $lastName  = $nameParts[1] ?? null;
//         }

//         // Check for duplicate phone number with different email
//         $existingUserWithPhone = User::where('phone_number', $decryptedPhone)
//             ->where('email', '!=', $user->email)
//             ->first();

//         if ($existingUserWithPhone) {
//             $skippedCount++;
//             $skippedEntries[] = [
//                 'reason' => 'duplicate_phone',
//                 'existing_user_id' => $existingUserWithPhone->id,
//                 'conflict_email' => $existingUserWithPhone->email,
//                 'conflict_phone' => $decryptedPhone,
//                 'new_email' => $user->email,
//             ];
//             continue;
//         }

//         $result = User::updateOrCreate(
//             ['email' => $user->email],
//             [
//                 'first_name'   => $firstName,
//                 'last_name'    => $lastName ?? ' ',
//                 'email'        => $user->email,
//                 'password'     => Hash::make('arabianpay@123'),
//                 'phone_number' => $decryptedPhone,
//             ]
//         );

//         if ($result) {
//             $submittedCount++;
//         }

//         $decryptedData[] = [
//             'id'         => $user->id,
//             'first_name' => $firstName,
//             'last_name'  => $lastName,
//             'email'      => $user->email,
//             'phone'      => $decryptedPhone,
//         ];
//     }

//     return response()->json([
//         'fetched_total'   => $users->count(),
//         'submitted_total' => $submittedCount,
//         'skipped_total'   => $skippedCount,
//         'skipped'         => $skippedEntries,
//         'data'            => $decryptedData,
//     ], 200, [], JSON_UNESCAPED_UNICODE);
// });

// // Decryption function stays the same
// function decryptWithArabicSupport($encrypted, $key, $iv)
// {
//     $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
//     $cleaned = $decrypted;

//     if (json_decode($cleaned) !== null || $cleaned === 'null') {
//         $decoded = json_decode($cleaned, true);
//         if (is_array($decoded) || is_object($decoded)) {
//             $cleaned = $decoded;
//         } else {
//             $cleaned = json_decode($cleaned);
//         }
//     }

//     if (is_string($cleaned) && str_contains($cleaned, '\\u')) {
//         $cleaned = json_decode('"' . addslashes(str_replace('\\\\', '\\', $cleaned)) . '"');
//     }

//     return $cleaned;
// }

// Route::get('/sellers', function () {
//     // Step 1: Get all users from old DB
//     $oldUsers = DB::connection('arabianoay_old')->table('users')->get();

//     // Step 2: Get unique emails from old users
//     $oldEmails = $oldUsers->pluck('email')->filter()->unique();

//     // Step 3: Get users from new DB whose emails match old ones
//     $newUsers = User::whereIn('email', $oldEmails)->get();

//     // Step 4: Map emails to new user IDs
//     $emailToNewUserId = $newUsers->pluck('id', 'email'); // [email => id]

//     // Step 5: Get all sellers from old DB
//     $oldSellers = DB::connection('arabianoay_old')
//         ->table('sellers')
//         ->whereIn('user_id', $oldUsers->pluck('id'))
//         ->get();

//     $migratedSellers = [];

//     foreach ($oldSellers as $oldSeller) {
//         // Match the old user
//         $oldUser = $oldUsers->firstWhere('id', $oldSeller->user_id);
//         if (!$oldUser) continue;

//         // Find corresponding new user ID
//         $newUserId = $emailToNewUserId[$oldUser->email] ?? null;
//         if (!$newUserId) continue;

//         // Skip if merchant with this CR already exists
//         if (
//             Merchant::where('cr_number', $oldSeller->cr_number)->exists() ||
//             Merchant::where('owner_iqama_number', $oldSeller->id_number)->exists()
//         ) {
//             continue;
//         }


//         // Step 6: Create new Merchant record
//         $newMerchant = new Merchant();
//         $newMerchant->user_id = $newUserId;
//         $newMerchant->cr_number = $oldSeller->cr_number;
//         $newMerchant->goverment_data = is_string($oldSeller->cr_data) ? $oldSeller->cr_data : json_encode($oldSeller->cr_data);
//         $newMerchant->vat_register_number = $oldSeller->vat_cr;
//         $newMerchant->return_day_count = $oldSeller->return_days ?? 0;
//         $newMerchant->exchange_day_count = $oldSeller->exchange_days ?? 0;
//         $newMerchant->cancel_day_count = 0;
//         $newMerchant->term_status = 'accepted';
//         $newMerchant->status = 'pending';
//         $newMerchant->owner_iqama_number = $oldSeller->id_number;
//         $newMerchant->save();

//         // Step 7: Update business name in new user
//         $user = User::find($newUserId);
//         if ($user) {
//             $user->business_name = $oldSeller->business_owner;
//             $user->save();
//         }

//         // Step 8: Create new SupplierBank record
//         $bank = new SupplierBank();
//         $bank->user_id = $newUserId;
//         $bank->bank_name = $oldSeller->bank_id ?? 'N/A';
//         $bank->account_name = $oldSeller->beneficiary_name ?? 'N/A';
//         $bank->iban = $oldSeller->account_ibn ?? 'N/A';
//         $bank->save();

//         // Add to migrated list
//         $migratedSellers[] = $newMerchant;
//     }

//     // Step 9: Return result
//     return response()->json([
//         'migrated_sellers_count' => count($migratedSellers),
//         'migrated_sellers' => $migratedSellers,
//     ]);
// });

// Route::get('/cr_data', function () {

//     dd(hijriToGregorian('1446/01/09'));
//     $merchants = Merchant::WhereNotIn('id', [1, 12, 15, 17, 19])->get();

//     foreach ($merchants as $merchant) {
//         if (!$merchant->goverment_data) {
//             continue; // skip if no data
//         }

//         $old = json_decode($merchant->goverment_data, true);
//         if (!$old) {
//             continue; // skip if invalid JSON
//         }

//         // Map old to new format (same mapping you gave)
//         $newFormat = [
//             "name" => $old['crName'] ?? null,
//             "isMain" => true,

//             "status" => [
//                 "id" => $old['status']['id'] ?? null,
//                 "name" => $old['status']['nameEn'] ?? null,
//                 "deletionDate" => $old['status']['deletionDate'] ?? [],
//                 "suspensionDate" => $old['status']['suspensionDate'] ?? [],
//                 "confirmationDate" => [
//                     "hijri" => $old['expiryDate'] ?? null,
//                     "gregorian" => hijriToGregorian($old['expiryDate']),
//                 ],
//                 "reactivationDate" => $old['status']['reactivationDate'] ?? [],
//             ],

//             "capital" => [
//                 "share" => $old['capital']['share'] ?? null,
//                 "paidAmount" => $old['capital']['paidAmount'] ?? null,
//                 "announcedAmount" => $old['capital']['announcedAmount'] ?? null,
//                 "subscribedAmount" => $old['capital']['subscribedAmount'] ?? null,
//             ],

//             "parties" => $old['parties'] ?? [],

//             "crNumber" => $old['crNumber'] ?? null,

//             "crCapital" => $old['capital']['paidAmount'] ?? null,

//             "eCommerce" => $old['isEcommerce'] ?? false,

//             "versionNo" => null,

//             "activities" => $old['activities'] ?? [],

//             "entityType" => $old['businessType'] ?? [],

//             "fiscalYear" => $old['fiscalYear'] ?? null,

//             "management" => null,

//             "nameLangId" => null,

//             "contactInfo" => [
//                 "email" => $old['address']['general']['email'] ?? null,
//                 "phoneNo" => $old['address']['general']['telephone1'] ?? null,
//                 "address" => $old['address']['general']['address'] ?? null,
//                 "website" => $old['address']['general']['website'] ?? null,
//                 "zipcode" => $old['address']['general']['zipcode'] ?? null,
//                 "postalBox1" => $old['address']['general']['postalBox1'] ?? null,
//                 "postalBox2" => $old['address']['general']['postalBox2'] ?? null,
//             ],

//             "hasEcommerce" => $old['isEcommerce'] ?? false,

//             "mainCrNumber" => $old['crMainNumber'] ?? null,

//             "nameLangDesc" => null,

//             "isLicenseBased" => false,

//             "issueDateHijri" => $old['issueDate'] ?? null,

//             "companyDuration" => null,

//             "crNationalNumber" => $old['crMainNumber'],

//             "headquarterCityId" => $old['location']['id'] ?? null,

//             "licenseIssuerName" => null,

//             "issueDateGregorian" => null,

//             "headquarterCityName" => $old['location']['name'] ?? null,

//             "inLiquidationProcess" => false,

//             "mainCrNationalNumber" => $old['crMainEntityNumber'],

//             "partnersNationalityId" => null,

//             "PartnersNationalityName" => null,

//             "licenseIssuerNationalNumber" => null,
//         ];

//         // Save the new format JSON back to the database
//         $merchant->goverment_data = json_encode($newFormat);
//         $merchant->save();
//     }

//     return response()->json(['message' => 'All merchants government_data updated successfully']);
// });

use Illuminate\Support\Str;

// Route::get('/customer-transfer', function () {
//     $key = base64_decode('dlzHBZOPN+4ZZ2Jnfkll/iVUJ1GuwfwCRnvxxuCMXdg=');
//     $iv = base64_decode('l2kMAFuEh7jazjgDCfIKUg==');

//     $users = DB::connection('arabianoay_old')
//         ->table('users')
//         ->where('user_type', 'customer')
//         ->get();

//     $decryptedData = [];
//     $submittedCount = 0;
//     $skippedCount = 0;
//     $skippedEntries = [];

//     foreach ($users as $user) {
//         $decryptedName  = decryptWithArabicSupport($user->name, $key, $iv);
//         $decryptedPhone = decryptWithArabicSupport($user->phone, $key, $iv);

//         // Split full name into first_name and last_name
//         $firstName = $decryptedName;
//         $lastName = null;

//         if (is_string($decryptedName)) {
//             $nameParts = explode(' ', trim($decryptedName), 2);
//             $firstName = $nameParts[0] ?? '';
//             $lastName  = $nameParts[1] ?? null;
//         }

//         // Check for duplicate phone number with different email
//         $existingUserWithPhone = User::where('phone_number', $decryptedPhone)
//             ->where('email', '!=', $user->email)
//             ->first();

//         if ($existingUserWithPhone) {
//             $skippedCount++;
//             $skippedEntries[] = [
//                 'reason' => 'duplicate_phone',
//                 'existing_user_id' => $existingUserWithPhone->id,
//                 'conflict_email' => $existingUserWithPhone->email,
//                 'conflict_phone' => $decryptedPhone,
//                 'new_email' => $user->email,
//             ];
//             continue;
//         }

//         $email = $user?->email;

//         if (empty($email)) {
//             // fallback to firstname based email
//             $baseEmail = Str::slug($firstName, '_') . '@arabianpay.net';
//             $email = $baseEmail;
//             $counter = 1;

//             // keep increasing suffix until unique email is found
//             while (User::where('email', $email)->exists()) {
//                 $email = Str::slug($firstName, '_') . $counter . '@arabianpay.net';
//                 $counter++;
//             }
//         }

//         $result = User::updateOrCreate(
//             ['email' => $user->email],
//             [
//                 'first_name'   => $firstName,
//                 'last_name'    => $lastName ?? ' ',
//                 'email'        => $email,
//                 'password'     => Hash::make('arabianpay@123'),
//                 'phone_number' => $decryptedPhone,
//             ]
//         );

//         if ($result) {
//             $submittedCount++;
//         }

//         $decryptedData[] = [
//             'id'         => $user->id,
//             'first_name' => $firstName,
//             'last_name'  => $lastName,
//             'email'      => $user->email,
//             'phone'      => $decryptedPhone,
//         ];
//     }

//     return response()->json([
//         'fetched_total'   => $users->count(),
//         'submitted_total' => $submittedCount,
//         'skipped_total'   => $skippedCount,
//         'skipped'         => $skippedEntries,
//         'data'            => $decryptedData,
//     ], 200, [], JSON_UNESCAPED_UNICODE);
// });

Route::get('/customers', function () {
    // Step 1: Get all users from old DB
    $oldUsers = DB::connection('arabianoay_old')->table('users')->where('user_type', 'customer')->get();

    // Step 2: Get unique emails from old users
    $oldEmails = $oldUsers->pluck('email')->filter()->unique();

    // Step 3: Get users from new DB whose emails match old ones
    $newUsers = User::whereIn('email', $oldEmails)->get();

    // Step 4: Map emails to new user IDs
    $emailToNewUserId = $newUsers->pluck('id', 'email'); // [email => id]

    // Step 5: Get all sellers from old DB
    $oldCustomers = DB::connection('arabianoay_old')
        ->table('customers')
        ->whereIn('user_id', $oldUsers->pluck('id'))
        ->get();

    $migratedSellers = [];

    foreach ($oldCustomers as $oldCustomer) {
        // Match the old user
        $oldUser = $oldUsers->firstWhere('id', $oldCustomer->user_id);
        if (!$oldUser) continue;

        // Find corresponding new user ID
        $newUserId = $emailToNewUserId[$oldUser->email] ?? null;
        if (!$newUserId) continue;

        // Skip if merchant with this CR already exists
        if (
            Customer::where('cr_number', $oldCustomer->cr_number)->exists() ||
            Customer::where('id_number', $oldCustomer->id_number)->exists() ||
            Customer::where('tax_number', $oldCustomer->tax_number)->exists()
        ) {
            continue;
        }

        // Step 6: Create new Merchant record
        $newCustomer = new Customer();
        $newCustomer->user_id = $newUserId;
        $newCustomer->package_id = 1;
        $newCustomer->id_number = $oldCustomer->id_number;
        $newCustomer->cr_number = $oldCustomer->cr_number;
        $newCustomer->cr_data = is_string($oldCustomer->cr_data) ? $oldCustomer->cr_data : json_encode($oldCustomer->cr_data);
        $newCustomer->tax_number = $oldCustomer->tax_number;
        $newCustomer->check_nafath = $oldCustomer->check_nafath ?? 0;
        $newCustomer->nafath_data = $oldCustomer->data;
        $newCustomer->date_of_birth = $oldCustomer->date_of_birth;
        $newCustomer->purchasing_volume = $oldCustomer->purchasing_volume;
        $newCustomer->purchasing_natures = $oldCustomer->purchasing_natures;
        $newCustomer->other_purchasing_natures = $oldCustomer->other_purchasing_natures;
        $newCustomer->status = 'pending';

        $newCustomer->save();

        // Step 7: Update business name in new user
        $user = User::find($newUserId);
        if ($user) {
            $user->business_name = $oldCustomer->trade_name;
            $user->save();
        }

        // Add to migrated list
        $migratedCustomers[] = $newCustomer;
    }

    // Step 9: Return result
    return response()->json([
        'migrated_customer_count' => count($migratedCustomers),
        'migrated_customers' => $migratedCustomers,
    ]);
});

Route::get('/media', function () {
    // Step 1: Get all old users with type customer or seller
    $oldUsers = DB::connection('arabianoay_old')
        ->table('users')
        ->whereIn('user_type', ['customer', 'seller'])
        ->get();

    // Step 2: Get unique emails from old users
    $oldEmails = $oldUsers->pluck('email')->filter()->unique();

    // Step 3: Get users from new DB whose emails match old ones
    $newUsers = User::whereIn('email', $oldEmails)->get();

    // Step 4: Map emails to new user IDs
    $emailToNewUserId = $newUsers->pluck('id', 'email'); // [email => id]

    // Step 5: Get all document uploads of those users
    $oldMedia = DB::connection('arabianoay_old')
        ->table('uploads')
        ->whereIn('user_id', $oldUsers->pluck('id'))
        ->where('type', 'document')
        ->get();

    $migratedData = [];

    foreach ($oldMedia as $old) {
        // Find old user from $oldUsers using user_id
        $oldUser = $oldUsers->firstWhere('id', $old->user_id);

        // Skip if no user or email not found
        if (!$oldUser || !$oldUser->email) {
            continue;
        }

        // Get new user ID by email
        $newUserId = $emailToNewUserId[$oldUser->email] ?? null;

        if ($newUserId) {
            $newMedia = new Media();
            $newMedia->name = $old->file_original_name;
            $newMedia->file_name = $old->file_name;
            $newMedia->user_id = $newUserId;
            $newMedia->size = $old->file_size;
            $newMedia->mime_type = $old->extension;
            $newMedia->disk = 'public';
            $newMedia->folder = 'media';
            $newMedia->save();

            $migratedData[] = $newMedia;
        }
    }

    return response()->json([
        'total_old_media' => count($oldMedia),
        'migrated_count' => count($migratedData),
    ]);
});

Route::get('/banks', function () {
    // Get all SupplierBanks
    $userBanks = SupplierBank::get();

    // Get old bank names from old DB
    $oldBanks = DB::connection('arabianoay_old')->table('banks')->get()->keyBy('id');

    $updated = [];

    foreach ($userBanks as $bank) {
        // Check if bank_name is numeric (i.e., an ID)
        if (is_numeric($bank->bank_name)) {
            $oldBank = $oldBanks[$bank->bank_name] ?? null;

            if ($oldBank && !empty($oldBank->name)) {
                $bank->bank_name = $oldBank->name_en;
                $bank->save();
                $updated[] = $bank;
            }
        }
    }

    return response()->json([
        'updated_count' => count($updated),
        'updated_items' => $updated,
    ]);
});


Route::get('/unencrypted-users', function () {

    $users = DB::connection('arabianpay_unencrypted')->table('users')->get();

    $updated = [];

    foreach ($users as $user) {

        $newUser = User::find($user->id);


        if ($newUser) {
            $newUser->first_name = $user->first_name;
            $newUser->last_name = $user->last_name;
            $newUser->user_type = $user->user_type;
            $newUser->email = $user->email;
            $newUser->business_name = $user->business_name;
            $newUser->phone_number = $user->phone_number;
            $newUser->email_verified_at = $user->email_verified_at;
            $newUser->save(); // Save changes to DB
            $updated[] = $newUser;
        }
    }

    return response()->json([
        'updated_count' => count($updated),
        'updated_items' => $updated,
    ]);
});

Route::get('/unencrypted-merchants', function () {

    $users = DB::connection('arabianpay_unencrypted')->table('merchants')->get();

    $updated = [];

    foreach ($users as $user) {

        $newUser = Merchant::where('user_id', $user->user_id)->first();

        if ($newUser) {
            $newUser->cr_number = $user->cr_number;
            $newUser->pos_revenue = $user->pos_revenue;
            $newUser->vat_register_number = $user->vat_register_number;
            $newUser->return_day_count = $user->return_day_count;
            $newUser->exchange_day_count = $user->exchange_day_count;
            $newUser->cancel_day_count = $user->cancel_day_count;
            $newUser->owner_name = $user->owner_name;
            $newUser->owner_iqama_number = $user->owner_iqama_number;
            $newUser->save(); // Save changes to DB
            $updated[] = $newUser;
        }
    }

    return response()->json([
        'updated_count' => count($updated),
        'updated_items' => $updated,
    ]);
});

Route::get('/unencrypted-customers', function () {

    $users = DB::connection('arabianpay_unencrypted')->table('customers')->get();

    $updated = [];

    foreach ($users as $user) {

        $newUser = Customer::where('user_id', $user->user_id)->first();

        if ($newUser) {
            $newUser->id_number = $user->id_number;
            $newUser->id_owner = $user->id_owner;
            $newUser->cr_number = $user->cr_number;
            $newUser->tax_number = $user->tax_number;
            $newUser->purchasing_volume = $user->purchasing_volume;
            $newUser->purchasing_natures = $user->purchasing_natures;
            $newUser->other_purchasing_natures = $user->other_purchasing_natures;
            $newUser->save(); // Save changes to DB
            $updated[] = $newUser;
        }
    }

    return response()->json([
        'updated_count' => count($updated),
        'updated_items' => $updated,
    ]);
});

// L2NobGZBMkdockppdzFvM1plaTR4QT09

Route::get('/old-categories', function () {

    $users = DB::connection('arabianpay_odl')->table('customers')->get();

    $updated = [];

    foreach ($users as $user) {

        $newUser = Customer::where('user_id', $user->user_id)->first();

        if ($newUser) {
            $newUser->id_number = $user->id_number;
            $newUser->id_owner = $user->id_owner;
            $newUser->cr_number = $user->cr_number;
            $newUser->tax_number = $user->tax_number;
            $newUser->purchasing_volume = $user->purchasing_volume;
            $newUser->purchasing_natures = $user->purchasing_natures;
            $newUser->other_purchasing_natures = $user->other_purchasing_natures;
            $newUser->save();
            $updated[] = $newUser;
        }
    }

    return response()->json([
        'updated_count' => count($updated),
        'updated_items' => $updated,
    ]);
});

Route::get('/admin', function () {
    $admin = User::where('email', 'admin@gmail.com')->first();
    $admin->email = 'admin@gmail.com';
    $admin->save();
    dd('ok');
});
