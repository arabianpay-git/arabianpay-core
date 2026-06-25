<?php

namespace Tests\Feature\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionGateSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_manage_route_requires_permission(): void
    {
        $this->actingAsAdmin()->get(route('orders'))->assertForbidden();

        $this->actingAsWithPermission('orders.manage')->get(route('orders'))->assertOk();
    }

    public function test_settlements_manage_route_requires_permission(): void
    {
        $this->actingAsAdmin()->get(route('settlements.index'))->assertForbidden();

        $this->actingAsWithPermission('settlements.manage')->get(route('settlements.index'))->assertOk();
    }

    public function test_refunds_manage_route_requires_permission(): void
    {
        $this->actingAsAdmin()->get(route('refund-requests'))->assertForbidden();

        $this->actingAsWithPermission('refunds.manage')->get(route('refund-requests'))->assertOk();
    }

    public function test_customers_manage_route_requires_permission(): void
    {
        $this->actingAsAdmin()->get(route('customers'))->assertForbidden();

        $this->actingAsWithPermission('customers.manage')->get(route('customers'))->assertOk();
    }

    public function test_merchants_manage_route_requires_permission(): void
    {
        $this->actingAsAdmin()->get(route('suppliers'))->assertForbidden();

        $this->actingAsWithPermission('merchants.manage')->get(route('suppliers'))->assertOk();
    }

    public function test_risk_view_route_requires_permission(): void
    {
        $this->actingAsAdmin()->get(route('risk.dashboard'))->assertForbidden();

        $this->actingAsWithPermission('risk.view')->get(route('risk.dashboard'))->assertOk();
    }

    public function test_collections_manage_route_requires_permission(): void
    {
        $this->actingAsAdmin()->get(route('collections.index'))->assertForbidden();

        $this->actingAsWithPermission('collections.manage')->get(route('collections.index'))->assertOk();
    }

    public function test_unauthenticated_requests_are_redirected_to_login(): void
    {
        $this->get(route('orders'))->assertRedirectToRoute('login');
    }
}
