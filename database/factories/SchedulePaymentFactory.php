<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\SchedulePayment;
use App\Models\User;
use Database\Factories\Concerns\ManagesApprovalContext;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchedulePaymentFactory extends Factory
{
    use ManagesApprovalContext;

    protected $model = SchedulePayment::class;

    public function definition(): array
    {
        $order = Order::factory();

        return [
            'order_id' => $order,
            'user_id' => fn (array $attributes) => Order::find($attributes['order_id'])?->user_id ?? User::factory(),
            'seller_id' => fn (array $attributes) => Order::find($attributes['order_id'])?->seller_id ?? User::factory(),
            'instalment_number' => fake()->numberBetween(1, 3),
            'due_date' => now()->addDays(fake()->numberBetween(1, 90)),
            'instalment_amount' => fake()->randomFloat(2, 100, 1000),
            'principle_amount' => fn (array $attributes) => $attributes['instalment_amount'],
            'payment_status' => 'pending',
            'payment_method' => 'Card Payment',
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

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
