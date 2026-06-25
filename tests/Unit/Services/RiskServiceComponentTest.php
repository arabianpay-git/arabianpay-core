<?php

namespace Tests\Unit\Services;

use App\Models\BusinessCategory;
use App\Models\BusinessType;
use App\Models\City;
use App\Models\Customer;
use App\Models\Order;
use App\Models\SchedulePayment;
use App\Models\User;
use App\Services\CreditAssessmentService;
use App\Services\RiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskServiceComponentTest extends TestCase
{
    use RefreshDatabase;

    private RiskService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RiskService::class);
    }

    private function invokePrivateMethod(string $method, ...$args): mixed
    {
        $ref = new \ReflectionMethod($this->service, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->service, ...$args);
    }

    // ---- LPS ----

    public function test_lps_with_valid_cr_data_scores_high(): void
    {
        $crData = [
            'issueDateGregorian' => now()->subYears(5)->format('Y-m-d'),
            'crNumber' => '1234567890',
            'status' => ['name' => 'Active'],
        ];

        $customer = Customer::factory()->create([
            'cr_data' => $crData,
            'nafath_data' => json_encode(false),
        ]);

        $result = $this->invokePrivateMethod('computeLPS', $customer, 'customer');

        $this->assertGreaterThan(80, $result['score']);
        $this->assertEquals(100, $result['components']['doc_score']);
        $this->assertEquals(100, $result['components']['cr_score']);
    }

    public function test_lps_without_cr_data_returns_low_score(): void
    {
        $customer = Customer::factory()->create([
            'cr_data' => null,
            'nafath_data' => json_encode(false),
        ]);

        $result = $this->invokePrivateMethod('computeLPS', $customer, 'customer');

        $this->assertLessThan(50, $result['score']);
        $this->assertEquals(0, $result['components']['doc_score']);
        $this->assertEquals(0, $result['components']['cr_score']);
    }

    public function test_lps_with_new_business_scores_lower(): void
    {
        $crData = [
            'issueDateGregorian' => now()->subMonths(3)->format('Y-m-d'),
            'crNumber' => '1234567890',
            'status' => ['name' => 'Active'],
        ];

        $customer = Customer::factory()->create([
            'cr_data' => $crData,
            'nafath_data' => json_encode(false),
        ]);

        $result = $this->invokePrivateMethod('computeLPS', $customer, 'customer');

        $this->assertLessThan(60, $result['components']['age_score']);
    }

    // ---- BPS ----

    public function test_bps_computes_sector_and_region_scores(): void
    {
        $businessType = BusinessType::create([
            'name' => 'Retail',
            'slug' => 'retail',
            'risk_level' => 'medium',
            'order_level' => '1',
        ]);
        $category = BusinessCategory::create([
            'business_type_id' => $businessType->id,
            'name' => 'Electronics',
            'slug' => 'electronics',
            'risk' => '3',
            'order_level' => '1',
        ]);
        $city = City::create([
            'name' => 'Riyadh',
            'risk' => '2',
        ]);

        $user = User::factory()->create(['city_id' => $city->id]);
        $merchant = \App\Models\Merchant::factory()->create([
            'user_id' => $user->id,
            'business_category_id' => $category->id,
        ]);

        $result = $this->invokePrivateMethod('computeBPS', $merchant, 'merchant');

        $this->assertArrayHasKey('sector_score', $result['components']);
        $this->assertArrayHasKey('region_score', $result['components']);
        $this->assertGreaterThan(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_bps_unknown_sector_uses_default(): void
    {
        $user = User::factory()->create(['city_id' => null]);
        $merchant = \App\Models\Merchant::factory()->create([
            'user_id' => $user->id,
            'business_category_id' => null,
        ]);

        $result = $this->invokePrivateMethod('computeBPS', $merchant, 'merchant');

        $this->assertEquals(3, $result['components']['sector_risk_class']);
        $this->assertEquals(2, $result['components']['region_risk_class']);
    }

    // ---- BES ----

    public function test_bes_with_high_dpd_scores_low(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $order = Order::factory()->create(['user_id' => $user->id, 'seller_id' => User::factory()]);
        SchedulePayment::factory()->count(2)->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'late_days' => 45,
            'payment_status' => 'late',
        ]);

        $result = $this->invokePrivateMethod('computeBES', $customer, 'customer');

        $this->assertEquals(60, $result['components']['ap_dpd_score']);
        $this->assertArrayHasKey('dpd_ap_max', $result['components']);
    }

    public function test_bes_without_history_returns_default(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $result = $this->invokePrivateMethod('computeBES', $customer, 'customer');

        $this->assertStringContainsString('No AP history', $result['notes']);
        $this->assertContains('no_behavior_history', $result['flags']);
    }

    public function test_bes_computes_turnover_trend(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        Order::factory()->count(3)->create([
            'user_id' => $user->id,
            'seller_id' => User::factory(),
            'grand_total' => 1000,
            'created_at' => now()->subMonth(),
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'seller_id' => User::factory(),
            'grand_total' => 500,
            'created_at' => now()->subMonths(9),
        ]);

        $result = $this->invokePrivateMethod('computeBES', $customer, 'customer');

        $this->assertGreaterThan(0, $result['components']['turnover_trend_pct']);
    }

    // ---- CreditAssessmentService ----

    public function test_risk_level_mapping(): void
    {
        $service = app(CreditAssessmentService::class);

        $method = new \ReflectionMethod($service, 'determineRiskLevel');
        $method->setAccessible(true);

        $this->assertSame('High', $method->invoke($service, 30));
        $this->assertSame('Medium', $method->invoke($service, 50));
        $this->assertSame('Medium', $method->invoke($service, 79));
        $this->assertSame('Low', $method->invoke($service, 80));
        $this->assertSame('Low', $method->invoke($service, 100));
    }
}
