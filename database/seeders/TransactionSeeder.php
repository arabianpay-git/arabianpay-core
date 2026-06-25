<?php

namespace Database\Seeders;

use App\Models\InstalmentPlan;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        $orders = Order::all();
        $plan = InstalmentPlan::first();

        if (! $plan) {
            return; // skip if no plan
        }

        foreach ($orders as $order) {
            $productIds = [];

            $productDetails = json_decode($order->product_details, true);

            if (is_array($productDetails)) {
                foreach ($productDetails as $item) {
                    if (isset($item['product_id'])) {
                        $productIds[] = (int) $item['product_id'];
                    }
                }
            }

            Transaction::create([
                'uuid' => Str::uuid(),
                'refrence_payment' => 'TXN-'.strtoupper(Str::random(6)).'-'.$order->id,
                'user_id' => $order->user_id,
                'seller_id' => $order->seller_id,
                'order_id' => $order->id,
                'product_ids' => $productIds,
                'plan_id' => $plan->id,
                'collected' => $order->grand_total,
                'retrieved' => rand(0, 1) ? rand(50, 300) : null,
                'canceled' => rand(0, 1) ? rand(50, 300) : null,
                'loan_amount' => $order->grand_total,
                'loan_start_date' => now()->subDays(rand(0, 30)),
                'loan_end_date' => now()->addDays(rand(30, 120)),
                'loan_term' => rand(6, 36),
                'subscription_fees' => rand(0, 1) ? rand(10, 100) : null,
                'credit_limit_at_time' => rand(1000, 5000),
                'remaining_credit_limit' => rand(100, 4000),
                'payment_status' => collect(['pending', 'due', 'late', 'paid', 'failed'])->random(),
                'settlement_status' => collect(['pending', 'settled', 'failed'])->random(),
                'general_status' => collect(['active', 'inactive', 'cancelled'])->random(),
                'resource' => fake()->optional()->word(),
            ]);
        }
    }
}
