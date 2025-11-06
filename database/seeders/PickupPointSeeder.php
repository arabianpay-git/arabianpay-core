<?php

namespace Database\Seeders;

use App\Models\PickupPoint;
use Illuminate\Database\Seeder;

class PickupPointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PickupPoint::create([
            'user_id' => 1,
            'name' => 'Main Pickup Point',
            'address' => json_encode([
                'address' => 'Al Maarefa, Al Maathar Municipality, Riyadh governorate, Riyadh Region, 12311, Saudi Arabia',
                'latitude' => '24.681181310871693',
                'longitude' => '46.68660521507264',
            ]),
            'phone_number' => '0501234567',
            'pick_up_status' => 'active',
            'cash_on_pickup_status' => 'active',
        ]);
    }
}
