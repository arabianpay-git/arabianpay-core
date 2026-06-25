<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'seller_id' => User::factory(),
            'grand_total' => fake()->randomFloat(2, 100, 5000),
            'commission_amount' => fake()->randomFloat(2, 5, 500),
            'commission_percent' => fake()->randomFloat(2, 1, 20),
            'shipping_cost' => fake()->randomFloat(2, 10, 200),
            'coupon_discount' => 0,
            'product_details' => '[]',
            'payment_details' => [],
            'shipping_first_name' => fake()->firstName(),
            'shipping_last_name' => fake()->lastName(),
            'shipping_address_line1' => fake()->streetAddress(),
            'shipping_address_line2' => fake()->secondaryAddress(),
            'shipping_city' => fake()->city(),
            'shipping_state' => fake()->state(),
            'shipping_country' => fake()->country(),
            'shipping_postal_code' => fake()->postcode(),
            'shipping_type' => 'standard',
            'order_from' => 'app',
            'payment_type' => 'installment',
            'code' => fake()->unique()->numerify('ORD######'),
            'reference_id' => fake()->unique()->numerify('REF########'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Order $order) {
            $order->forceFill([
                'general_status' => $order->general_status ?? 'processing',
                'delivery_status' => $order->delivery_status ?? 'pending',
                'payment_status' => $order->payment_status ?? 'pending',
            ])->save();
        });
    }

    public function accepted(): static
    {
        return $this->afterCreating(function (Order $order) {
            $order->forceFill(['general_status' => 'accepted'])->save();
        });
    }

    public function delivered(): static
    {
        return $this->afterCreating(function (Order $order) {
            $order->forceFill(['delivery_status' => 'delivered'])->save();
        });
    }

    public function unsettled(): static
    {
        return $this->afterCreating(function (Order $order) {
            $order->forceFill(['settlement_id' => null])->save();
        });
    }
}
