<?php

namespace Database\Seeders;

use App\Models\RefundRequest;
use App\Models\User;
use App\Models\Order;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class RefundRequestSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Generate 15 refund requests
        foreach (range(1, 15) as $index) {
            RefundRequest::create([
                'user_id' => User::inRandomOrder()->first()->id,
                'seller_id' => User::inRandomOrder()->first()->id,
                'order_id' => Order::inRandomOrder()->first()->id,
                'seller_approval' => $faker->boolean,
                'admin_approval' => $faker->boolean,
                'refund_amount' => $faker->randomFloat(2, 10, 500),
                'reason' => $faker->sentence,
                'reject_reason' => $faker->optional()->sentence,
                'refund_status' => $faker->randomElement(['pending', 'approved', 'rejected']),
            ]);
        }
    }
}
