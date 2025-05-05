<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Order;
use App\Models\InstalmentPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        // DB::table('transactions')->truncate();

        // Fetch all IDs first
        $userIds = User::pluck('id')->toArray();
        $orderIds = Order::pluck('id')->toArray();
        $planIds = InstalmentPlan::pluck('id')->toArray();

        for ($i = 0; $i < 15; $i++) {
            Transaction::create([
                'uuid' => Str::uuid(),
                'refrence_payment' => strtoupper(Str::random(10)),
                'user_id' => fake()->randomElement($userIds),
                'seller_id' => fake()->randomElement($userIds),
                'order_id' => fake()->randomElement($orderIds),
                'product_ids' => [rand(1, 50), rand(51, 100)],
                'plan_id' => fake()->randomElement($planIds),
                'collected' => fake()->randomFloat(2, 100, 5000),
                'retrieved' => fake()->optional()->randomFloat(2, 50, 5000),
                'canceled' => fake()->optional()->randomFloat(2, 50, 5000),
                'loan_amount' => fake()->optional()->randomFloat(2, 500, 10000),
                'loan_start_date' => fake()->optional()->date(),
                'loan_end_date' => fake()->optional()->date(),
                'loan_term' => fake()->optional()->numberBetween(6, 36),
                'subscription_fees' => fake()->optional()->randomFloat(2, 10, 100),
                'credit_limit_at_time' => fake()->optional()->randomFloat(2, 1000, 5000),
                'remaining_credit_limit' => fake()->optional()->randomFloat(2, 100, 4000),
                'payment_status' => fake()->randomElement(['pending', 'due', 'late', 'paid', 'failed']),
                'settlement_status' => fake()->randomElement(['pending', 'settled', 'failed']),
                'general_status' => fake()->randomElement(['active', 'inactive', 'cancelled']),
                'resource' => fake()->optional()->word(),
            ]);
        }
    }
}
