<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Order;
use App\Models\InstalmentPlan;
use Faker\Factory as Faker;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $users = User::pluck('id')->toArray();
        $orders = Order::pluck('id')->toArray();
        $instalments = InstalmentPlan::pluck('id')->toArray();

        for ($i = 0; $i < 30; $i++) {
            Wallet::create([
                'user_id' => $faker->randomElement($users),
                'seller_id' => $faker->randomElement($users),
                'order_id' => $faker->optional()->randomElement($orders),
                'instalment_id' => $faker->optional()->randomElement($instalments),

                'transaction_type' => $faker->randomElement(['loan_disbursement', 'user_repayment', 'seller_payment']),
                'amount' => $faker->randomFloat(2, 100, 5000),
                'balance_after' => $faker->randomFloat(2, 1000, 10000),

                'status' => $faker->randomElement(['active', 'pending', 'closed']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
