<?php

namespace Tests\Feature\Orders;

use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Models\SchedulePayment;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSchedulePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_accept_order_creates_three_installment_payments(): void
    {
        $this->actingAs(User::factory()->create());
        $this->enterApprovalContext();

        $order = Order::factory()->create([
            'grand_total' => 900,
        ]);

        $controller = new OrderController(app(AuditTrailService::class));
        $method = new \ReflectionMethod($controller, 'createSchedulePayments');
        $method->setAccessible(true);

        $method->invoke($controller, $order);

        $payments = SchedulePayment::where('order_id', $order->id)
            ->orderBy('instalment_number')
            ->get();

        $this->assertCount(3, $payments);

        $expectedAmounts = [300.00, 300.00, 300.00];
        $expectedDueDays = [30, 60, 90];

        foreach ($payments as $index => $payment) {
            $this->assertEquals($index + 1, $payment->instalment_number);
            $this->assertEquals($expectedAmounts[$index], $payment->instalment_amount);
            $this->assertEquals($expectedAmounts[$index], $payment->principle_amount);
            $this->assertEquals('pending', $payment->payment_status);
            $this->assertEquals($order->user_id, $payment->user_id);
            $this->assertEquals($order->seller_id, $payment->seller_id);

            $expectedDueDate = now()->addDays($expectedDueDays[$index])->toDateString();
            $this->assertEquals($expectedDueDate, $payment->due_date->toDateString());
        }
    }

    public function test_installment_creation_is_idempotent(): void
    {
        $this->actingAs(User::factory()->create());
        $this->enterApprovalContext();

        $order = Order::factory()->create(['grand_total' => 900]);

        $controller = new OrderController(app(AuditTrailService::class));
        $method = new \ReflectionMethod($controller, 'createSchedulePayments');
        $method->setAccessible(true);

        $method->invoke($controller, $order);
        $method->invoke($controller, $order);

        $this->assertCount(3, SchedulePayment::where('order_id', $order->id)->get());
    }

    public function test_installment_amounts_round_to_two_decimals(): void
    {
        $this->actingAs(User::factory()->create());
        $this->enterApprovalContext();

        $order = Order::factory()->create(['grand_total' => 1000]);

        $controller = new OrderController(app(AuditTrailService::class));
        $method = new \ReflectionMethod($controller, 'createSchedulePayments');
        $method->setAccessible(true);

        $method->invoke($controller, $order);

        $payments = SchedulePayment::where('order_id', $order->id)
            ->orderBy('instalment_number')
            ->pluck('instalment_amount');

        // 1000 / 3 = 333.33 (round); all three are 333.33
        foreach ($payments as $amount) {
            $this->assertEquals(333.33, $amount);
        }
    }
}
