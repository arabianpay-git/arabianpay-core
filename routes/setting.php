<?php

use App\Http\Controllers\settings\RiskWeightController;
use App\Http\Controllers\settings\SettingController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\SecureHeaders;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix'     => LaravelLocalization::setLocale(),
    'middleware' => ThrottleRequests::class,
], function () {
    Route::prefix('admin')
        ->middleware(['auth:sanctum', PreventBackHistory::class, SecureHeaders::class, CheckAdmin::class, config('jetstream.auth_session'), 'verified'])
        ->group(function () {

            Route::controller(SettingController::class)->prefix('settings')->as('settings.')->group(function () {
                Route::get('/', 'index')->name('index');

                // Core Settings
                Route::get('/general', 'general')->name('general');
                Route::put('/general', 'generalUpdate')->name('general.update');
                Route::post('/general/reset', 'generalReset')->name('general.reset');

                Route::get('/email', 'email')->name('email');
                Route::put('/email', 'emailUpdate')->name('email.update');
                Route::post('email/test', 'emailTest')->name('email.test');
                Route::post('email/reset', 'emailReset')->name('email.reset');


                Route::get('/media', 'media')->name('media');
                Route::get('/api', 'api')->name('api');
                Route::get('/cache', 'cache')->name('cache');
                Route::get('/datatables', 'datatables')->name('datatables');
                Route::get('/analytics', 'analytics')->name('analytics');
                Route::get('/optimization', 'optimization')->name('optimization');
                Route::get('/sitemap', 'sitemap')->name('sitemap');

                // Financial Settings
                Route::get('/financial', 'financial')->name('financial');
                Route::get('/payout', 'payout')->name('payout');
                Route::get('/tax', 'tax')->name('tax');
                Route::get('/currency', 'currency')->name('currency');
                Route::get('/payment-gateways', 'paymentGateways')->name('payment-gateways');

                // Credit & Collections Settings
                Route::get('/credit-scoring', 'creditScoring')->name('credit-scoring');
                Route::get('/credit-limits', 'creditLimits')->name('credit-limits');
                Route::get('/repayment-rules', 'repaymentRules')->name('repayment-rules');
                Route::get('/collection-rules', 'collectionRules')->name('collection-rules');
                Route::get('/dunning', 'dunning')->name('dunning');
                Route::get('/late-fees', 'lateFees')->name('late-fees');

                // Orders & Shipping Settings
                Route::get('/order', 'order')->name('order');
                Route::get('/shipping', 'shipping')->name('shipping');
                Route::get('/delivery', 'delivery')->name('delivery');
                Route::get('/refund', 'refund')->name('refund');

                // Products Settings
                Route::get('/product', 'product')->name('product');
                Route::get('/inventory', 'inventory')->name('inventory');
                Route::get('/attributes', 'attributes')->name('attributes');
                Route::get('/reviews', 'reviews')->name('reviews');

                // Supplier Settings
                Route::get('/supplier', 'supplier')->name('supplier');
                Route::get('/commission', 'commission')->name('commission');
                Route::get('/payout-schedule', 'payoutSchedule')->name('payout-schedule');

                // Customer Settings
                Route::get('/customer', 'customer')->name('customer');
                Route::get('/onboarding', 'onboarding')->name('onboarding');
                Route::get('/verification', 'verification')->name('verification');
                Route::get('/packages', 'packages')->name('packages');

                // Marketing Settings
                Route::get('/marketing', 'marketing')->name('marketing');
                Route::get('/notifications', 'notifications')->name('notifications');
                Route::get('/email-templates', 'emailTemplates')->name('email-templates');
                Route::get('/sms', 'sms')->name('sms');
                Route::get('/push-notifications', 'pushNotifications')->name('push-notifications');

                // Support & Tickets Settings
                Route::get('/support', 'support')->name('support');
                Route::get('/ticket', 'ticket')->name('ticket');
                Route::get('/sla', 'sla')->name('sla');

                // Employee & Roles Settings
                Route::get('/employee', 'employee')->name('employee');
                Route::get('/departments', 'departments')->name('departments');
                Route::get('/permissions', 'permissions')->name('permissions');
                Route::get('/workflow', 'workflow')->name('workflow');

                // System Settings
                Route::get('/system', 'system')->name('system');
                Route::get('/security', 'security')->name('security');
                Route::get('/maintenance', 'maintenance')->name('maintenance');
                Route::get('/backup', 'backup')->name('backup');
                Route::get('/logs', 'logs')->name('logs');

                // Third Party Services
                Route::get('/third-party', 'thirdParty')->name('third-party');
                Route::get('/sms-gateway', 'smsGateway')->name('sms-gateway');
                Route::get('/email-service', 'emailService')->name('email-service');
                Route::get('/payment-processors', 'paymentProcessors')->name('payment-processors');
                Route::get('/shipping-services', 'shippingServices')->name('shipping-services');
            });

            Route::controller(RiskWeightController::class)->prefix('settings')->group(function () {
                Route::get('/risk-weight', 'index')->name('settings.risk-weights');
                Route::post('settings/risk-weights', 'store')->name('settings.risk-weight.store');


                Route::get('/compliance-rules', 'complianceRules')->name('settings.compliance-rules');
                Route::get('/fraud-detection', 'fraudDetection')->name('settings.fraud-detection');
                Route::get('/aml', 'aml')->name('settings.aml');
                Route::get('/kyc', 'kyc')->name('settings.kyc');
            });
        });
});
