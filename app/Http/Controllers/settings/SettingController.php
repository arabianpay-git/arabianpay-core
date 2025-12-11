<?php

namespace App\Http\Controllers\settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            // Core Settings Group
            'core' => [
                [
                    'title' => 'General',
                    'description' => 'Manage core system settings, site information, and basic configurations.',
                    'icon' => 'ki-setting-2',
                    'route' => route('settings.general')
                ],
                [
                    'title' => 'Email Rules',
                    'description' => 'Configure email validation, templates, and delivery settings.',
                    'icon' => 'ki-shield-tick',
                    'route' => route('settings.email')
                ],
                [
                    'title' => 'Media & Storage',
                    'description' => 'Manage file uploads, storage drivers, and media configurations.',
                    'icon' => 'ki-folder',
                    'route' => route('settings.media')
                ],
                [
                    'title' => 'API Settings',
                    'description' => 'Configure API keys, webhooks, and external integrations.',
                    'icon' => 'ki-code',
                    'route' => route('settings.api')
                ],
                [
                    'title' => 'Cache & Performance',
                    'description' => 'Optimize system performance and caching mechanisms.',
                    'icon' => 'ki-rocket',
                    'route' => route('settings.cache')
                ],
                [
                    'title' => 'Datatables',
                    'description' => 'Configure default sorting, pagination, and display options.',
                    'icon' => 'ki-graph-3',
                    'route' => route('settings.datatables')
                ],
                [
                    'title' => 'Analytics & Tracking',
                    'description' => 'Setup analytics, tracking codes, and monitoring tools.',
                    'icon' => 'ki-chart',
                    'route' => route('settings.analytics')
                ],
                [
                    'title' => 'Optimization',
                    'description' => 'Configure asset optimization and compression settings.',
                    'icon' => 'ki-arrows-circle',
                    'route' => route('settings.optimization')
                ],
                [
                    'title' => 'Sitemap',
                    'description' => 'Manage XML sitemap generation and configuration.',
                    'icon' => 'ki-map',
                    'route' => route('settings.sitemap')
                ],
            ],

            // Financial Settings Group
            'financial' => [
                [
                    'title' => 'Financial Settings',
                    'description' => 'Configure financial accounts, currencies, and transaction settings.',
                    'icon' => 'ki-wallet',
                    'route' => route('settings.financial')
                ],
                [
                    'title' => 'Payout Settings',
                    'description' => 'Manage payout schedules, methods, and thresholds.',
                    'icon' => 'ki-bank',
                    'route' => route('settings.payout')
                ],
                [
                    'title' => 'Tax Settings',
                    'description' => 'Configure tax rates, rules, and calculations.',
                    'icon' => 'ki-calculator',
                    'route' => route('settings.tax')
                ],
                [
                    'title' => 'Currency Settings',
                    'description' => 'Manage currencies, exchange rates, and conversions.',
                    'icon' => 'ki-dollar',
                    'route' => route('settings.currency')
                ],
                [
                    'title' => 'Payment Gateways',
                    'description' => 'Configure payment processors and gateway settings.',
                    'icon' => 'ki-credit-cart',
                    'route' => route('settings.payment-gateways')
                ],
            ],

            // Risk Management Settings Group
            'risk_management' => [
                [
                    'title' => 'Risk Weights',
                    'description' => 'Define risk scoring rules and weight configurations.',
                    'icon' => 'ki-abstract-22',
                    'route' => route('settings.risk-weights')
                ],
                [
                    'title' => 'Compliance Rules',
                    'description' => 'Configure compliance checks and regulatory settings.',
                    'icon' => 'ki-security-check-shield',
                    'route' => route('settings.compliance-rules')
                ],
                [
                    'title' => 'Fraud Detection',
                    'description' => 'Setup fraud detection rules and monitoring.',
                    'icon' => 'ki-shield-tick',
                    'route' => route('settings.fraud-detection')
                ],
                [
                    'title' => 'AML Settings',
                    'description' => 'Configure Anti-Money Laundering rules and monitoring.',
                    'icon' => 'ki-shield-search',
                    'route' => route('settings.aml')
                ],
                [
                    'title' => 'KYC Settings',
                    'description' => 'Manage Know Your Customer verification settings.',
                    'icon' => 'ki-user-check',
                    'route' => route('settings.kyc')
                ],
            ],

            // Credit & Collections Settings Group
            'credit_collections' => [
                [
                    'title' => 'Credit Scoring',
                    'description' => 'Configure credit scoring models and algorithms.',
                    'icon' => 'ki-chart-line',
                    'route' => route('settings.credit-scoring')
                ],
                [
                    'title' => 'Credit Limits',
                    'description' => 'Manage credit limit rules and calculations.',
                    'icon' => 'ki-dollar-circle',
                    'route' => route('settings.credit-limits')
                ],
                [
                    'title' => 'Repayment Rules',
                    'description' => 'Configure repayment schedules and rules.',
                    'icon' => 'ki-calendar-tick',
                    'route' => route('settings.repayment-rules')
                ],
                [
                    'title' => 'Collection Rules',
                    'description' => 'Manage collection processes and rules.',
                    'icon' => 'ki-call',
                    'route' => route('settings.collection-rules')
                ],
                [
                    'title' => 'Dunning Settings',
                    'description' => 'Configure dunning process and escalation rules.',
                    'icon' => 'ki-notification-status',
                    'route' => route('settings.dunning')
                ],
                [
                    'title' => 'Late Fees & Penalties',
                    'description' => 'Manage late payment fees and penalty rules.',
                    'icon' => 'ki-money-time',
                    'route' => route('settings.late-fees')
                ],
            ],

            // Orders & Shipping Settings Group
            'orders_shipping' => [
                [
                    'title' => 'Order Settings',
                    'description' => 'Configure order processing and workflow settings.',
                    'icon' => 'ki-shopping-bag',
                    'route' => route('settings.order')
                ],
                [
                    'title' => 'Shipping Settings',
                    'description' => 'Manage shipping methods, rates, and configurations.',
                    'icon' => 'ki-delivery',
                    'route' => route('settings.shipping')
                ],
                [
                    'title' => 'Delivery Settings',
                    'description' => 'Configure delivery options and timeframes.',
                    'icon' => 'ki-truck',
                    'route' => route('settings.delivery')
                ],
                [
                    'title' => 'Refund Settings',
                    'description' => 'Manage refund policies and processing rules.',
                    'icon' => 'ki-rotate',
                    'route' => route('settings.refund')
                ],
            ],

            // Products Settings Group
            'products' => [
                [
                    'title' => 'Product Settings',
                    'description' => 'Configure product catalog and display settings.',
                    'icon' => 'ki-box',
                    'route' => route('settings.product')
                ],
                [
                    'title' => 'Inventory Settings',
                    'description' => 'Manage inventory tracking and stock settings.',
                    'icon' => 'ki-shop',
                    'route' => route('settings.inventory')
                ],
                [
                    'title' => 'Attribute Settings',
                    'description' => 'Configure product attributes and variations.',
                    'icon' => 'ki-tag',
                    'route' => route('settings.attributes')
                ],
                [
                    'title' => 'Review Settings',
                    'description' => 'Manage product review rules and moderation.',
                    'icon' => 'ki-star',
                    'route' => route('settings.reviews')
                ],
            ],

            // Supplier Settings Group
            'supplier' => [
                [
                    'title' => 'Supplier Settings',
                    'description' => 'Configure supplier management and onboarding.',
                    'icon' => 'ki-people',
                    'route' => route('settings.supplier')
                ],
                [
                    'title' => 'Commission Settings',
                    'description' => 'Manage commission rates and calculations.',
                    'icon' => 'ki-percentage',
                    'route' => route('settings.commission')
                ],
                [
                    'title' => 'Payout Schedule',
                    'description' => 'Configure supplier payout schedules.',
                    'icon' => 'ki-calendar-2',
                    'route' => route('settings.payout-schedule')
                ],
            ],

            // Customer Settings Group
            'customer' => [
                [
                    'title' => 'Customer Settings',
                    'description' => 'Configure customer management and profiles.',
                    'icon' => 'ki-user',
                    'route' => route('settings.customer')
                ],
                [
                    'title' => 'Onboarding Settings',
                    'description' => 'Manage customer onboarding flow and requirements.',
                    'icon' => 'ki-user-square',
                    'route' => route('settings.onboarding')
                ],
                [
                    'title' => 'Verification Settings',
                    'description' => 'Configure customer verification processes.',
                    'icon' => 'ki-shield-user',
                    'route' => route('settings.verification')
                ],
                [
                    'title' => 'Package Settings',
                    'description' => 'Manage customer package configurations.',
                    'icon' => 'ki-badge',
                    'route' => route('settings.packages')
                ],
            ],

            // Marketing Settings Group
            'marketing' => [
                [
                    'title' => 'Marketing Settings',
                    'description' => 'Configure marketing campaigns and promotions.',
                    'icon' => 'ki-megaphone',
                    'route' => route('settings.marketing')
                ],
                [
                    'title' => 'Notification Settings',
                    'description' => 'Manage notification rules and templates.',
                    'icon' => 'ki-notification',
                    'route' => route('settings.notifications')
                ],
                [
                    'title' => 'Email Templates',
                    'description' => 'Configure system email templates.',
                    'icon' => 'ki-message-text',
                    'route' => route('settings.email-templates')
                ],
                [
                    'title' => 'SMS Settings',
                    'description' => 'Manage SMS gateway and templates.',
                    'icon' => 'ki-message',
                    'route' => route('settings.sms')
                ],
                [
                    'title' => 'Push Notifications',
                    'description' => 'Configure mobile push notifications.',
                    'icon' => 'ki-notification-bing',
                    'route' => route('settings.push-notifications')
                ],
            ],

            // Support & Tickets Settings Group
            'support' => [
                [
                    'title' => 'Support Settings',
                    'description' => 'Configure support system and help center.',
                    'icon' => 'ki-support',
                    'route' => route('settings.support')
                ],
                [
                    'title' => 'Ticket Settings',
                    'description' => 'Manage ticket workflow and categorization.',
                    'icon' => 'ki-ticket',
                    'route' => route('settings.ticket')
                ],
                [
                    'title' => 'SLA Settings',
                    'description' => 'Configure Service Level Agreements.',
                    'icon' => 'ki-clock',
                    'route' => route('settings.sla')
                ],
            ],

            // Employee & Roles Settings Group
            'employee_roles' => [
                [
                    'title' => 'Employee Settings',
                    'description' => 'Configure employee management and permissions.',
                    'icon' => 'ki-profile-user',
                    'route' => route('settings.employee')
                ],
                [
                    'title' => 'Department Settings',
                    'description' => 'Manage departments and team structures.',
                    'icon' => 'ki-building',
                    'route' => route('settings.departments')
                ],
                [
                    'title' => 'Permission Settings',
                    'description' => 'Configure role-based access controls.',
                    'icon' => 'ki-shield-security',
                    'route' => route('settings.permissions')
                ],
                [
                    'title' => 'Workflow Settings',
                    'description' => 'Manage approval workflows and processes.',
                    'icon' => 'ki-diagram',
                    'route' => route('settings.workflow')
                ],
            ],

            // System Settings Group
            'system' => [
                [
                    'title' => 'System Settings',
                    'description' => 'Configure system-wide settings and configurations.',
                    'icon' => 'ki-monitor',
                    'route' => route('settings.system')
                ],
                [
                    'title' => 'Security Settings',
                    'description' => 'Manage security policies and access controls.',
                    'icon' => 'ki-lock',
                    'route' => route('settings.security')
                ],
                [
                    'title' => 'Maintenance Settings',
                    'description' => 'Configure maintenance mode and schedules.',
                    'icon' => 'ki-setting',
                    'route' => route('settings.maintenance')
                ],
                [
                    'title' => 'Backup Settings',
                    'description' => 'Manage backup schedules and configurations.',
                    'icon' => 'ki-data-backup',
                    'route' => route('settings.backup')
                ],
                [
                    'title' => 'Log Settings',
                    'description' => 'Configure logging levels and retention.',
                    'icon' => 'ki-document',
                    'route' => route('settings.logs')
                ],
            ],

            // Third Party Services Group
            'third_party' => [
                [
                    'title' => 'Third Party Services',
                    'description' => 'Manage integrations with external services.',
                    'icon' => 'ki-puzzle',
                    'route' => route('settings.third-party')
                ],
                [
                    'title' => 'SMS Gateway',
                    'description' => 'Configure SMS service providers.',
                    'icon' => 'ki-messages',
                    'route' => route('settings.sms-gateway')
                ],
                [
                    'title' => 'Email Service',
                    'description' => 'Configure email service providers.',
                    'icon' => 'ki-message-text-2',
                    'route' => route('settings.email-service')
                ],
                [
                    'title' => 'Payment Processors',
                    'description' => 'Manage payment gateway integrations.',
                    'icon' => 'ki-card',
                    'route' => route('settings.payment-processors')
                ],
                [
                    'title' => 'Shipping Services',
                    'description' => 'Configure shipping carrier integrations.',
                    'icon' => 'ki-ship',
                    'route' => route('settings.shipping-services')
                ],
            ],
        ];

        return view('settings.index', compact('settings'));
    }

    // General Settings
    public function general()
    {
        return view('settings.general');
    }

    public function generalUpdate(Request $request)
    {
        $data = [
            'site_name'         => $request->site_name,
            'site_title'        => $request->site_title,
            'site_description'  => $request->site_description,

            'contact_email'     => $request->contact_email,
            'contact_phone'     => $request->contact_phone,
            'address_line'      => $request->address_line,

            'default_language'  => $request->default_language,
            'timezone'          => $request->timezone,
            'date_format'       => $request->date_format,
            'time_format'       => $request->time_format,

            'logo'              => $request->logo,
            'favicon'           => $request->favicon,
        ];

        Setting::setByKey('general', $data, 'general');
        updateEnvValue('APP_NAME', $request->site_name);

        return back()->with('success', 'Settings Updated Successfully');
    }

    // Email Settings
    public function email()
    {
        return view('settings.email');
    }

    /**
     * Update email settings
     */
    public function emailUpdate(Request $request)
    {
        // Validate all email settings
        $validated = $this->validateEmailSettings($request);

        // Dynamically handle all checkbox fields
        // Convention: any input in request with value 'on' or type checkbox will be normalized
        $checkboxes = collect($request->all())->filter(function ($value, $key) use ($request) {
            // Laravel unchecked checkboxes won't be in $request, so we detect manually
            return $request->has($key) && in_array($value, ['1', 'on', 0, 1, null], true);
        });

        foreach ($checkboxes as $key => $value) {
            $validated[$key] = $request->has($key) ? 1 : 0;
        }

        // Alternatively, if you want all known checkboxes starting with 'notify_' or specific known names
        $checkboxKeys = ['email_enabled', 'notify_new_user', 'notify_password_reset', 'notify_order_confirmation', 'notify_contact_form', 'use_queue'];

        foreach ($checkboxKeys as $key) {
            $validated[$key] = $request->has($key) ? 1 : 0;
        }

        // Store all settings as JSON
        Setting::updateOrCreate(
            ['key' => 'email'],
            ['value' => json_encode($validated)]
        );

        // Update .env file (optional)
        $this->updateEnvSettings($validated);

        return redirect()->route('settings.email')
            ->with('success', 'Email settings updated successfully!');
    }


    /**
     * Validate email settings
     */
    private function validateEmailSettings(Request $request)
    {
        return $request->validate([
            'email_enabled' => 'nullable|boolean',
            'mail_driver' => 'required|in:smtp,sendmail,mailgun,ses,postmark',
            'mail_host' => 'required_if:mail_driver,smtp|string|max:255',
            'mail_port' => 'required_if:mail_driver,smtp|integer|min:1|max:65535',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:500',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            'notify_new_user' => 'nullable|boolean',
            'notify_password_reset' => 'nullable|boolean',
            'notify_order_confirmation' => 'nullable|boolean',
            'notify_contact_form' => 'nullable|boolean',
            'test_email' => 'nullable|email',
            'timeout' => 'nullable|integer|min:5|max:300',
            'retry_attempts' => 'nullable|integer|min:1|max:10',
            'use_queue' => 'nullable|boolean',
            'charset' => 'nullable|in:utf-8,iso-8859-1',
            'mailgun_domain' => 'nullable|string|max:255',
            'mailgun_secret' => 'nullable|string|max:255',
            'ses_key' => 'nullable|string|max:255',
            'ses_secret' => 'nullable|string|max:255',
            'postmark_token' => 'nullable|string|max:255',
        ], [
            'mail_host.required_if' => 'SMTP host is required when using SMTP driver.',
            'mail_port.required_if' => 'SMTP port is required when using SMTP driver.',
            'from_address.required' => 'From email address is required.',
            'from_name.required' => 'From name is required.',
        ]);
    }

    /**
     * Update .env file with email settings
     */
    private function updateEnvSettings(array $settings)
    {
        try {
            $envPath = base_path('.env');

            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);

                $updates = [
                    'MAIL_MAILER' => $settings['mail_driver'] ?? 'smtp',
                    'MAIL_HOST' => $settings['mail_host'] ?? 'smtp.mailgun.org',
                    'MAIL_PORT' => $settings['mail_port'] ?? '587',
                    'MAIL_USERNAME' => $settings['mail_username'] ?? '',
                    'MAIL_PASSWORD' => $settings['mail_password'] ?? '',
                    'MAIL_ENCRYPTION' => $settings['mail_encryption'] ?? 'tls',
                    'MAIL_FROM_ADDRESS' => $settings['from_address'] ?? 'hello@example.com',
                    'MAIL_FROM_NAME' => '"' . addslashes($settings['from_name'] ?? 'Laravel') . '"',
                    'MAILGUN_DOMAIN' => $settings['mailgun_domain'] ?? '',
                    'MAILGUN_SECRET' => $settings['mailgun_secret'] ?? '',
                    'AWS_ACCESS_KEY_ID' => $settings['ses_key'] ?? '',
                    'AWS_SECRET_ACCESS_KEY' => $settings['ses_secret'] ?? '',
                    'MAIL_PRETEND' => ($settings['email_enabled'] ?? 1) ? 'false' : 'true',
                ];

                foreach ($updates as $key => $value) {
                    $pattern = "/^{$key}=.*/m";

                    if (preg_match($pattern, $envContent)) {
                        $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
                    } else {
                        $envContent .= "\n{$key}={$value}";
                    }
                }

                file_put_contents($envPath, $envContent);
            }
        } catch (\Exception $e) {
            // Log error but don't break the flow
            Log::error('Failed to update .env file: ' . $e->getMessage());
        }
    }

    /**
     * Send test email
     */
    public function emailTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address'
            ], 422);
        }

        try {
            $email = $request->email;

            // Send test email
            Mail::raw('This is a test email from ' . config('app.name') . '. If you received this, your email configuration is working correctly.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email from ' . config('app.name'));
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }

    // Media Settings
    public function media()
    {
        return view('settings.media');
    }

    // API Settings
    public function api()
    {
        return view('settings.api');
    }

    // Add more methods for each setting group...

    // Update setting
    public function update(Request $request, $group)
    {
        $validated = $request->validate([
            // Add validation rules based on group
        ]);

        foreach ($validated as $key => $value) {
            Setting::setByKey($key, $value);
        }

        // Clear cache if needed
        Cache::forget('settings');

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
