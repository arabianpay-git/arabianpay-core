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

        // Create permissions
        $permissions = [
            // Dashboard
            'dashboard.view',

            // Accounts
            'customer.view',
            'customer.manage',
            'supplier.view',
            'supplier.manage',

            // Products
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

            // Orders
            'order.manage',
            'shipping_order.manage',

            // Financial
            'transaction.view',
            'plan.read',
            'plan.create',
            'plan.update',
            'plan.delete',
            'schedule_payment.view',
            'supplier_and_sales.view',
            'customer_and_sales.view',

            // Marketing
            'coupon.read',
            'coupon.create',
            'coupon.update',
            'coupon.delete',
            'abandoned_cart.view',
            'notification.manage',
            'ad.manage',

            // Support & Tickets
            'ticket.view',
            'ticket.create',
            'ticket.reply',
            'ticket.manage',

            // Customer Packages
            'package.manage',

            // Media
            'media.manage',

            // Reports
            'report.view',

            // Refunds
            'refund.manage',

            // Roles & Permissions
            'role.manage',
            'role.permission',
            'role.assign',
            'role.user',

            // Employee
            'employee.read',
            'employee.create',
            'employee.edit',
            'employee.delete',

            // Supplier Management
            'supplier_entitlement.manage',
            'supplier_account.manage',
            'supplier_payout.manage',

            // Locations
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

            // Business Types & Categories
            'business_type.read',
            'business_type.create',
            'business_type.update',
            'business_type.delete',
            'business_category.read',
            'business_category.create',
            'business_category.update',
            'business_category.delete',

            // Settings
            'settings.manage',
            'faq.manage',
            'page.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin Role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Assign all permissions to admin
        $adminRole->syncPermissions(Permission::all());
    }
}
