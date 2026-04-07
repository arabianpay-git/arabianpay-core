<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // ── Dashboard ──
            'dashboard.view',

            // ── Accounts ──
            'customer.view',
            'customer.manage',
            'customer.update-status',
            'supplier.view',
            'supplier.manage',
            'supplier.update-status',

            // ── Products ──
            'product.read',
            'product.create',
            'product.update',
            'product.delete',
            'product.approval',
            'product.reviews',
            'category.read',
            'category.create',
            'category.update',
            'category.delete',
            'brand.read',
            'brand.create',
            'brand.update',
            'brand.delete',
            'attribute.read',
            'attribute.create',
            'attribute.update',
            'attribute.delete',
            'statics.product',
            'statics.category',
            'statics.brand',
            'statics.reviews',

            // ── Orders ──
            'order.view',
            'order.manage',
            'order.accept',
            'order.reject',
            'shipping_order.manage',

            // ── Settlements (PHASE-1) ──
            'settlement.view',
            'settlement.create',
            'settlement.approve',
            'settlement.cancel',
            'settlement.pay',
            'settlement.export',

            // ── Payouts (PHASE-1) ──
            'payout.view',
            'payout.process',
            'payout.complete',

            // ── Payments (PHASE-1) ──
            'payment.view',
            'payment.process',

            // ── Schedule Payments (PHASE-1) ──
            'schedule_payment.view',
            'schedule_payment.update',
            'schedule_payment.pay-now',

            // ── Credit Limits ──
            'credit-limit.view',
            'credit-limit.create',
            'credit-limit.update',
            'credit-limit.approve',  // Maker-Checker
            'credit-limit.reject',   // Maker-Checker

            // ── Refunds ──
            'refund.view',
            'refund.manage',
            'refund.approve',
            'refund.reject',  // Maker-Checker
            'refund.submit',  // Maker-Checker

            // ── Risk Overrides (Maker-Checker) ──
            'risk-override.request',
            'risk-override.approve',
            'risk-override.reject',

            // ── Approvals (generic) ──
            'approval.view',
            'approval.manage',

            // ── Merchants (PHASE-1) ──
            'merchant.view',
            'merchant.update',

            // ── Sensitive Data (PHASE-1) ──
            'sensitive-data.access',
            'sensitive-data.approve',

            // ── Risk & Compliance (PHASE-1) ──
            'risk.view',
            'risk.manage',
            'risk.export',
            'risk.update-score',
            'risk-weight.view',
            'risk-weight.manage',
            'compliance.view',
            'compliance.manage',

            // ── Collections (PHASE-1) ──
            'collection.view',
            'collection.manage',
            'collection.allocate',
            'claim.view',
            'claim.manage',

            // ── Financial Accounting (PHASE-1) ──
            'financial.view',
            'financial.manage',
            'financial.export',

            // ── Investment Pools (PHASE-1) ──
            'investment-pool.view',
            'investment-pool.manage',

            // ── Checkouts (PHASE-1) ──
            'checkout.view',
            'checkout.manage',

            // ── Reports (PHASE-1) ──
            'report.view',
            'report.export',

            // ── Transactions ──
            'transaction.view',
            'plan.read',
            'plan.create',
            'plan.update',
            'plan.delete',
            'supplier_and_sales.view',
            'customer_and_sales.view',

            // ── Marketing ──
            'coupon.read',
            'coupon.create',
            'coupon.update',
            'coupon.delete',
            'abandoned_cart.view',
            'notification.manage',
            'ad.manage',

            // ── Support ──
            'ticket.manage',

            // ── Packages ──
            'package.manage',

            // ── Media ──
            'media.manage',

            // ── Roles & Permissions ──
            'role.manage',
            'role.permission',
            'role.assign',
            'role.user',

            // ── Employees ──
            'employee.read',
            'employee.create',
            'employee.edit',
            'employee.delete',

            // ── Supplier Management ──
            'supplier_entitlement.manage',
            'supplier_account.manage',
            'supplier_payout.manage',

            // ── Locations ──
            'country.read',
            'country.create',
            'country.update',
            'country.delete',
            'state.read',
            'state.create',
            'state.update',
            'state.delete',
            'city.read',
            'city.create',
            'city.update',
            'city.delete',

            // ── Business Types & Categories ──
            'business_type.read',
            'business_type.create',
            'business_type.update',
            'business_type.delete',
            'business_category.read',
            'business_category.create',
            'business_category.update',
            'business_category.delete',

            // ── Settings (PHASE-1) ──
            'settings.manage',
            'settings.financial',
            'settings.security',
            'faq.manage',
            'page.manage',

            // ── Audit (PHASE-1) ──
            'audit.view',
            'audit.export',

            // ── Tools (PHASE-1) ──
            'tools.email',
            'tools.sms',
            'tools.fcm',
            'tools.third-party',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ── Roles ──

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

        // Finance role: settlements, payouts, payments, financial accounting, reports
        $financeRole = Role::firstOrCreate(['name' => 'finance']);
        $financeRole->syncPermissions([
            'dashboard.view',
            'settlement.view', 'settlement.create', 'settlement.approve', 'settlement.cancel',
            'settlement.pay', 'settlement.export',
            'payout.view', 'payout.process', 'payout.complete',
            'payment.view', 'payment.process',
            'schedule_payment.view', 'schedule_payment.update', 'schedule_payment.pay-now',
            'transaction.view',
            'financial.view', 'financial.manage', 'financial.export',
            'supplier_and_sales.view', 'customer_and_sales.view',
            'report.view', 'report.export',
            'order.view',
            'customer.view', 'supplier.view',
            'refund.view', 'refund.manage', 'refund.approve',
        ]);

        // Risk role: risk analytics, credit limits, compliance
        $riskRole = Role::firstOrCreate(['name' => 'risk']);
        $riskRole->syncPermissions([
            'dashboard.view',
            'risk.view', 'risk.manage', 'risk.export', 'risk.update-score',
            'risk-weight.view', 'risk-weight.manage',
            'credit-limit.view', 'credit-limit.create', 'credit-limit.update',
            'compliance.view', 'compliance.manage',
            'customer.view', 'supplier.view',
            'report.view', 'report.export',
            'order.view',
            'sensitive-data.access',
        ]);

        // Compliance role: compliance, audit, sensitive data
        $complianceRole = Role::firstOrCreate(['name' => 'compliance']);
        $complianceRole->syncPermissions([
            'dashboard.view',
            'compliance.view', 'compliance.manage',
            'audit.view', 'audit.export',
            'sensitive-data.access', 'sensitive-data.approve',
            'customer.view', 'supplier.view',
            'risk.view',
            'report.view', 'report.export',
            'order.view',
        ]);

        // Collections role: collections, claims, schedule payments
        $collectionsRole = Role::firstOrCreate(['name' => 'collections']);
        $collectionsRole->syncPermissions([
            'dashboard.view',
            'collection.view', 'collection.manage', 'collection.allocate',
            'claim.view', 'claim.manage',
            'schedule_payment.view', 'schedule_payment.update',
            'customer.view',
            'order.view',
            'report.view',
        ]);

        // Support role: tickets, basic views
        $supportRole = Role::firstOrCreate(['name' => 'support']);
        $supportRole->syncPermissions([
            'dashboard.view',
            'ticket.manage',
            'customer.view',
            'supplier.view',
            'order.view',
            'schedule_payment.view',
            'refund.view',
        ]);
    }
}
