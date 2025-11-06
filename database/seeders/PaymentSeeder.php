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

        $orders = Order::all();

        foreach ($orders as $order) {
            Payment::create([
                'user_id' => $order->user_id,
                'seller_id' => $order->seller_id,
                'order_id' => $order->id,
                'amount' => $order->grand_total,
                'payment_details' => json_encode([
                    'gateway' => 'Stripe',
                    'transaction_id' => $faker->uuid()
                ]),
                'invoice_number' => $order->invoice_number,
                'txn_code' => strtoupper('TXN' . rand(1000, 9999)),
                'tax_number' => strtoupper('TAX' . rand(100, 999)),
                'payment_status' => $faker->randomElement(['pending', 'due', 'late', 'paid', 'failed']),
            ]);
        }
    }
}
