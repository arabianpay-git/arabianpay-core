<?php

namespace Database\Seeders;

use App\Models\SchedulePayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SchedulePaymentSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {

            SchedulePayment::create([
                'uuid' => Str::uuid(),
                'user_id' => User::inRandomOrder()->first()->id,
                'seller_id' => User::inRandomOrder()->first()->id,
                'order_id' => Order::inRandomOrder()->first()->id,
                'instalment_number' => $i,
                'due_date' => now()->addDays(rand(5, 60)),
                'instalment_amount' => rand(100, 500),
                'principle_amount' => rand(80, 300),
                'late_fee' => rand(0, 50),
                'subscription_fee' => rand(0, 30),
                'shipping_amount' => rand(0, 20),
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
