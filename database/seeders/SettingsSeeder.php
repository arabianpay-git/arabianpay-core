<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            // Core Settings
            ['key' => 'site_name', 'value' => config('app.name'), 'group' => 'core', 'type' => 'text', 'description' => 'Website name', 'order' => 1],
            ['key' => 'site_title', 'value' => config('app.name'), 'group' => 'core', 'type' => 'text', 'description' => 'Website title tag', 'order' => 2],
            ['key' => 'site_description', 'value' => 'E-commerce platform', 'group' => 'core', 'type' => 'textarea', 'description' => 'Website meta description', 'order' => 3],
            ['key' => 'contact_email', 'value' => 'info@example.com', 'group' => 'core', 'type' => 'email', 'description' => 'Primary contact email', 'order' => 4],
            ['key' => 'contact_phone', 'value' => '+966500000000', 'group' => 'core', 'type' => 'text', 'description' => 'Contact phone number', 'order' => 5],
            ['key' => 'default_language', 'value' => 'en', 'group' => 'core', 'type' => 'select', 'options' => ['en' => 'English', 'ar' => 'Arabic'], 'description' => 'Default language', 'order' => 6],
            ['key' => 'timezone', 'value' => config('app.timezone'), 'group' => 'core', 'type' => 'select', 'description' => 'System timezone', 'order' => 7],
            ['key' => 'date_format', 'value' => 'd/m/Y', 'group' => 'core', 'type' => 'select', 'options' => ['d/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'Y-m-d' => 'YYYY-MM-DD'], 'description' => 'Date format', 'order' => 8],
            ['key' => 'time_format', 'value' => '12', 'group' => 'core', 'type' => 'select', 'options' => ['12' => '12 Hour', '24' => '24 Hour'], 'description' => 'Time format', 'order' => 9],
            ['key' => 'logo', 'value' => 'assets/media/images/default-logo.svg', 'group' => 'core', 'type' => 'image', 'description' => 'Website logo', 'order' => 10],
            ['key' => 'favicon', 'value' => 'favicon.ico', 'group' => 'core', 'type' => 'image', 'description' => 'Website favicon', 'order' => 11],

            // Email Settings
            ['key' => 'mail_driver', 'value' => 'smtp', 'group' => 'email', 'type' => 'select', 'options' => ['smtp' => 'SMTP', 'mailgun' => 'Mailgun', 'ses' => 'SES'], 'description' => 'Mail driver', 'order' => 1],
            ['key' => 'mail_host', 'value' => 'smtp.mailtrap.io', 'group' => 'email', 'type' => 'text', 'description' => 'Mail host', 'order' => 2],
            ['key' => 'mail_port', 'value' => '2525', 'group' => 'email', 'type' => 'number', 'description' => 'Mail port', 'order' => 3],
            ['key' => 'mail_username', 'value' => '', 'group' => 'email', 'type' => 'text', 'description' => 'Mail username', 'order' => 4],
            ['key' => 'mail_password', 'value' => '', 'group' => 'email', 'type' => 'password', 'is_encrypted' => true, 'description' => 'Mail password', 'order' => 5],
            ['key' => 'mail_encryption', 'value' => 'tls', 'group' => 'email', 'type' => 'select', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL'], 'description' => 'Mail encryption', 'order' => 6],
            ['key' => 'mail_from_address', 'value' => 'noreply@example.com', 'group' => 'email', 'type' => 'email', 'description' => 'From email address', 'order' => 7],
            ['key' => 'mail_from_name', 'value' => config('app.name'), 'group' => 'email', 'type' => 'text', 'description' => 'From name', 'order' => 8],

            // Financial Settings
            ['key' => 'currency', 'value' => 'SAR', 'group' => 'financial', 'type' => 'select', 'options' => ['SAR' => 'Saudi Riyal', 'USD' => 'US Dollar', 'EUR' => 'Euro'], 'description' => 'Default currency', 'order' => 1],
            ['key' => 'currency_symbol', 'value' => 'ر.س', 'group' => 'financial', 'type' => 'text', 'description' => 'Currency symbol', 'order' => 2],
            ['key' => 'currency_position', 'value' => 'right', 'group' => 'financial', 'type' => 'select', 'options' => ['left' => 'Left', 'right' => 'Right'], 'description' => 'Currency position', 'order' => 3],
            ['key' => 'decimal_points', 'value' => '2', 'group' => 'financial', 'type' => 'number', 'description' => 'Decimal points', 'order' => 4],
            ['key' => 'vat_percentage', 'value' => '15', 'group' => 'financial', 'type' => 'number', 'description' => 'VAT percentage', 'order' => 5],
            ['key' => 'payout_minimum', 'value' => '100', 'group' => 'financial', 'type' => 'number', 'description' => 'Minimum payout amount', 'order' => 6],
            ['key' => 'payout_days', 'value' => '7', 'group' => 'financial', 'type' => 'number', 'description' => 'Payout processing days', 'order' => 7],

            // Risk Management Settings
            ['key' => 'risk_score_threshold', 'value' => '70', 'group' => 'risk_management', 'type' => 'number', 'description' => 'Risk score threshold for approval', 'order' => 1],
            ['key' => 'fraud_check_enabled', 'value' => '1', 'group' => 'risk_management', 'type' => 'checkbox', 'description' => 'Enable fraud detection', 'order' => 2],
            ['key' => 'aml_check_enabled', 'value' => '1', 'group' => 'risk_management', 'type' => 'checkbox', 'description' => 'Enable AML checks', 'order' => 3],
            ['key' => 'kyc_required', 'value' => '1', 'group' => 'risk_management', 'type' => 'checkbox', 'description' => 'Require KYC verification', 'order' => 4],

            // Add more settings for each module...
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
