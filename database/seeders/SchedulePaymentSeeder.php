<?php

namespace Database\Seeders;

use App\Models\InstalmentPlan;
use App\Models\SchedulePayment;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SchedulePaymentSeeder extends Seeder
{
    public function run(): void
    {
        $plans = InstalmentPlan::all();
        $orders = Order::all();

        foreach ($orders as $order) {
            $plan = $plans->random(); // randomly assign a plan to the order


            // Skip if invalid values
            if (!$plan || !$plan->installments || !$plan->patch_days) {
                // dd($plan->installments);
                continue;
            }

            $installments = (int) $plan->installments;
            $patchDays = (int) $plan->patch_days;
            $instalmentAmount = round($order->grand_total / $installments, 2);
            $shippingAmount = round($order->shipping_cost / $installments, 2);

            for ($i = 1; $i <= $installments; $i++) {

                SchedulePayment::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $order->user_id,
                    'seller_id' => $order->seller_id,
                    'order_id' => $order->id,
                    'instalment_number' => $i,
                    'due_date' => now()->addDays($patchDays * $i),
                    'instalment_amount' => $instalmentAmount,
                    'principle_amount' => $instalmentAmount - 10,
                    'late_fee' => rand(0, 50),
                    'subscription_fee' => rand(0, 30),
                    'shipping_amount' => $shippingAmount,
                    'additional_amount' => rand(0, 50),
                    'difference_amount' => rand(0, 15),
                    'deducted_amount' => rand(0, 100),
                    'is_late' => (bool)rand(0, 1),
                    'late_days' => rand(0, 10),
                    'payment_status' => collect(['pending', 'due', 'late', 'paid', 'failed'])->random(),
                ]);
            }
        }
    }
}
