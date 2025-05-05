<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 15; $i++) {
            Payment::create([
                'user_id' => User::inRandomOrder()->first()->id,
                'seller_id' => User::inRandomOrder()->first()->id,
                'order_id' => Order::inRandomOrder()->first()->id,
                'amount' => $faker->randomFloat(2, 100, 1000),
                'payment_details' => json_encode(['gateway' => 'Stripe', 'transaction_id' => $faker->uuid]),
                'invoice_number' => $faker->word(),
                'txn_code' => $faker->word(),
                'tax_number' => $faker->word(),
                'payment_status' => $faker->randomElement(['pending', 'due', 'late', 'paid', 'failed']),
            ]);
        }
    }
}
