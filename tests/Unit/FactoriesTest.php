<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\InstalmentPlan;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\SchedulePayment;
use App\Models\Settlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider modelFactoryProvider
     */
    public function test_factory_can_create_model(string $modelClass): void
    {
        $model = $modelClass::factory()->create();

        $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
    }

    public static function modelFactoryProvider(): array
    {
        return [
            'order' => [Order::class],
            'schedule_payment' => [SchedulePayment::class],
            'settlement' => [Settlement::class],
            'customer' => [Customer::class],
            'merchant' => [Merchant::class],
            'refund_request' => [RefundRequest::class],
            'payment' => [Payment::class],
            'instalment_plan' => [InstalmentPlan::class],
        ];
    }
}
