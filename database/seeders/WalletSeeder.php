<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Transaction;
use Faker\Factory as Faker;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $orders = Order::all();
        $lastBalance = 0.00; // initial wallet balance

        foreach ($orders as $order) {
            // Get related payment for amount
            $payment = Payment::where('order_id', $order->id)->first();

            // Get related transaction to fetch the instalment_id
            $transaction = Transaction::where('order_id', $order->id)->first();

            // Skip if either is missing
            if (!$payment || !$transaction) {
                continue;
            }

            $amount = $payment->amount;
            $balanceAfter = $lastBalance + $amount;

            Wallet::create([
                'user_id' => $order->user_id,
                'seller_id' => $order->seller_id,
                'order_id' => $order->id,
                'instalment_id' => $transaction->plan_id, // real instalment reference from transaction
                'transaction_type' => 'user_repayment', // using fixed type for clarity
                'amount' => $amount, // pulled from payment for that order
                'balance_after' => $balanceAfter, // cumulative running total
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update last balance for the next entry
            $lastBalance = $balanceAfter;
        }
    }
}
