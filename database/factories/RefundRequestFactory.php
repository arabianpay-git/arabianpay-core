<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use Database\Factories\Concerns\ManagesApprovalContext;
use Illuminate\Database\Eloquent\Factories\Factory;

class RefundRequestFactory extends Factory
{
    use ManagesApprovalContext;

    protected $model = RefundRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'seller_id' => User::factory(),
            'order_id' => Order::factory(),
            'refund_amount' => fake()->randomFloat(2, 10, 500),
            'reason' => fake()->sentence(),
            'refund_status' => 'pending',
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
