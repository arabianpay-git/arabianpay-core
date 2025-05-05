<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;
use App\Models\PickupPoint;
use App\Models\Product;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        $users = User::pluck('id')->toArray();
        $pickupPoints = PickupPoint::pluck('id')->toArray();
        $products = Product::pluck('id')->toArray();

        foreach (range(1, 10) as $index) {
            $selectedProducts = $faker->randomElements($products, 2);
            $productItems = [];

            foreach ($selectedProducts as $productId) {
                $product = Product::find($productId);
                $hasAttributes = $faker->boolean(70); // 70% chance of having attributes

                $productItems[] = [
                    'product_id' => $productId,
                    'quantity' => rand(1, 5),
                    'attributes' => $hasAttributes ? [
                        [
                            'attribute' => 'Material',
                            'value' => $faker->randomElement(['Cotton', 'Leather', 'Plastic']),
                            'price' => rand(100, 5000),
                        ],
                        [
                            'attribute' => 'Color',
                            'value' => $faker->randomElement(['Red', 'Blue', 'Brown', 'Black']),
                            'price' => rand(100, 5000),
                        ],
                        [
                            'attribute' => 'Size',
                            'value' => $faker->randomElement(['S', 'M', 'L', 'XL']),
                            'price' => rand(100, 5000),
                        ],
                    ] : null,
                ];
            }

            Order::create([
                'user_id' => $faker->randomElement($users),
                'seller_id' => $faker->randomElement($users),
                'pickup_point_id' => $faker->randomElement($pickupPoints),

                'product_details' => json_encode($productItems),

                'shipping_first_name' => $faker->firstName(),
                'shipping_last_name' => $faker->lastName(),
                'shipping_address_line1' => $faker->address(),
                'shipping_address_line2' => $faker->optional()->address(),
                'shipping_city' => $faker->city(),
                'shipping_state' => $faker->state(),
                'shipping_country' => $faker->country(),
                'shipping_postal_code' => $faker->postcode(),

                'shipping_type' => $faker->randomElement(['Standard', 'Express']),
                'order_from' => $faker->randomElement(['Website', 'Mobile App']),
                'payment_type' => $faker->randomElement(['Credit Card', 'PayPal', 'Cash']),
                'shipping_cost' => $faker->randomFloat(2, 5, 30),
                'payment_status' => $faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
                'payment_details' => json_encode([
                    'transaction_id' => $faker->uuid(),
                    'gateway' => $faker->randomElement(['PayPal', 'Stripe']),
                ]),

                'grand_total' => $faker->randomFloat(2, 50, 500),
                'coupon_discount' => $faker->randomFloat(2, 0, 50),
                'code' => $faker->optional()->word(),
                'tracking' => $faker->optional()->uuid(),

                'delivery_status' => $faker->randomElement(['pending', 'shipped', 'delivered', 'returned']),
                'general_status' => $faker->randomElement(['processing', 'completed', 'cancelled', 'failed']),

                'created_at' => $faker->dateTimeThisYear(),
                'updated_at' => now(),
            ]);
        }
    }
}
