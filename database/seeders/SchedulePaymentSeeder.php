<?php

namespace Database\Seeders;

use App\Models\InstalmentPlan;
use App\Models\Order;
use App\Models\SchedulePayment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SchedulePaymentSeeder extends Seeder
{
    public function run(): void
    {
        $plans = InstalmentPlan::all();
        $orders = Order::all();

        foreach ($orders as $order) {
            $plan = $plans->random();

            if (! $plan || ! $plan->installments || ! $plan->patch_days) {
                continue;
            }

            $installments = (int) $plan->installments;
            $patchDays = (int) $plan->patch_days;
            $instalmentAmount = round($order->grand_total / $installments, 2);
            $shippingAmount = round($order->shipping_cost / $installments, 2);

            for ($i = 1; $i <= $installments; $i++) {
                // Randomize due_date: some in past (overdue), some in future
                $dueDate = Carbon::now()->addDays($patchDays * $i);

                // Make 40% of due_dates in the past to simulate overdue payments
                if (rand(1, 100) <= 40) {
                    $dueDate = Carbon::now()->subDays(rand(1, 90));
                }

                // principle_amount: slightly less than instalment amount but never negative
                $principleAmount = max(0, $instalmentAmount - rand(5, 15));

                // payment status distribution weighted to allow for testing overdue and paid
                $paymentStatuses = [
                    'pending' => 30,
                    'due' => 25,
                    'late' => 20,
                    'paid' => 20,
                    'failed' => 5,
                ];
                $paymentStatus = $this->weightedRandom($paymentStatuses);

                SchedulePayment::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $order->user_id,
                    'seller_id' => $order->seller_id,
                    'order_id' => $order->id,
                    'instalment_number' => $i,
                    'due_date' => $dueDate,
                    'instalment_amount' => $instalmentAmount,
                    'principle_amount' => $principleAmount,
                    'late_fee' => rand(0, 20),
                    'subscription_fee' => rand(0, 10),
                    'shipping_amount' => $shippingAmount,
                    'additional_amount' => rand(0, 15),
                    'difference_amount' => rand(0, 5),
                    'deducted_amount' => rand(0, 30),
                    'is_late' => in_array($paymentStatus, ['late']),
                    'late_days' => $paymentStatus === 'late' ? rand(1, 15) : 0,
                    'payment_status' => $paymentStatus,
                ]);
            }
        }
    }

    /**
     * Helper function to pick weighted random value.
     */
    private function weightedRandom(array $weights)
    {
        $rand = rand(1, array_sum($weights));
        foreach ($weights as $key => $weight) {
            if ($rand <= $weight) {
                return $key;
            }
            $rand -= $weight;
        }
    }
}
