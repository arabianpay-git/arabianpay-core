<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductWishlist;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductWishlistSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::where('user_type', 'user')->inRandomOrder()->take(15)->pluck('id');
        $sellerIds = User::where('user_type', 'merchant')->inRandomOrder()->take(15)->pluck('id');
        $productIds = Product::inRandomOrder()->take(15)->pluck('id');

        for ($i = 0; $i < 15; $i++) {
            ProductWishlist::create([
                'user_id' => $userIds[$i % $userIds->count()],
                'seller_id' => $sellerIds[$i % $sellerIds->count()],
                'product_id' => $productIds[$i % $productIds->count()],
            ]);
        }
    }
}
