<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SchedulePayment;
use App\Models\User;
use Database\Factories\Concerns\ManagesApprovalContext;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    use ManagesApprovalContext;

    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => fn (array $attributes) => Order::find($attributes['order_id'])?->user_id ?? User::factory(),
            'seller_id' => fn (array $attributes) => Order::find($attributes['order_id'])?->seller_id ?? User::factory(),
            'schedule_payment_id' => SchedulePayment::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'payment_status' => 'paid',
            'payment_details' => '[]',
            'invoice_number' => fake()->unique()->numerify('INV######'),
            'txn_code' => fake()->unique()->numerify('TXN########'),
            'tax_number' => fake()->unique()->numerify('TAX########'),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function () {
            $this->ensureApprovalContext();
        })->afterCreating(function () {
            $this->restoreApprovalContext();
        });
    }
}
