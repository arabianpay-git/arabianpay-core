<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'user_type' => 'admin',
            'email' => 'admin@gmail.com',
            'business_name' => 'Arabianpay',
            'phone_number' => '0555555555',
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'password' => Hash::make('123456'),
        ]);

        User::create([
            'first_name' => 'Merchant',
            'last_name' => 'User',
            'user_type' => 'merchant',
            'email' => 'merchant@gmail.com',
            'business_name' => 'Merchant Inc',
            'phone_number' => '0555555555',
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'password' => Hash::make('123456'),
        ]);
    }
}
