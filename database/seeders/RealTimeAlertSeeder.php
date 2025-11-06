<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RealTimeAlertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::take(5)->get();
        $transactions = \App\Models\Transaction::pluck('id');

        foreach ($users as $user) {
            \App\Models\RealTimeAlert::create([
                'user_id' => $user->id,
                'risk_score' => rand(10, 100),
                'suspicious_activity' => fake()->randomElement(['multiple logins', 'location mismatch', 'large withdrawal']),
                'transaction_id' => $transactions->random(),
            ]);
        }
    }
}
