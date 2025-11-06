<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // CountrySeeder::class,
            // StateSeeder::class,
            // CitySeeder::class,
            // UserSeeder::class,

            // AttributeSeeder::class,
            // AttributeValueSeeder::class,
            // CategorySeeder::class,
            // BrandSeeder::class,
            // ProductSeeder::class,

            // PickupPointSeeder::class,
            // OrderSeeder::class,

            // TransactionSeeder::class,
            // PaymentSeeder::class,
            // RefundRequestSeeder::class,

            // RiskManagementSeeder::class,
            // ProductWishlistSeeder::class,
        ]);
    }
}
