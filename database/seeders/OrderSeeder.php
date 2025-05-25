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

        $users = User::where('user_type', 'user')->get();
        $sellers = User::where('user_type', 'merchant')->get();
        $pickupPoints = PickupPoint::all();
        $products = Product::all();

        for ($i = 0; $i < 2; $i++) {
            $user = $users->random();
            $seller = $sellers->random();
            $pickupPoint = $pickupPoints->random();
            $selectedProducts = $products->random(2);

            $productItems = [];
            $calculatedTotal = 0;

            foreach ($selectedProducts as $product) {
                $variants = $product->variants;

                if (is_string($variants)) {
                    $variants = json_decode($variants, true);
                }

                $selectedVariantAttributes = null;
                $attributePrice = 0;

                if (is_array($variants) && count($variants)) {
                    $randomVariant = collect($variants)->random();
                    $selectedVariantAttributes = $randomVariant['attributes'] ?? null;

                    if (!empty($randomVariant['price'])) {
                        $attributePrice = (float) $randomVariant['price'];
                    }
                }

                $quantity = rand(1, 3);
                $calculatedTotal += $attributePrice * $quantity;

                $productItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'attributes' => $selectedVariantAttributes,
                ];
            }

            $order = new Order();
            $order->user()->associate($user);
            $order->seller()->associate($seller);
            $order->pickupPoint()->associate($pickupPoint);
            $order->product_details = json_encode($productItems);

            $order->shipping_first_name = $faker->firstName();
            $order->shipping_last_name = $faker->lastName();
            $order->shipping_address_line1 = $faker->address();
            $order->shipping_address_line2 = $faker->optional()->address();
            $order->shipping_city = 'Riyadh';
            $order->shipping_state = 'Riyadh';
            $order->shipping_country = 'Saudi Arabia';
            $order->shipping_postal_code = $faker->postcode();

            $order->shipping_type = 'Standard';
            $order->order_from = 'Website';
            $order->payment_type = 'Instalment Plan';
            $order->shipping_cost = 20.00;

            $order->payment_status = 'completed';
            $order->payment_details = json_encode([
                'transaction_id' => $faker->uuid(),
                'gateway' => 'Instalment Plan',
            ]);

            $order->coupon_discount = 10.00;
            $order->grand_total = $calculatedTotal + $order->shipping_cost - $order->coupon_discount;
            $order->code = 'ORDER-' . strtoupper($faker->unique()->bothify('??###'));
            $order->tracking = strtoupper($faker->unique()->bothify('TRACK###??'));

            $order->delivery_status = 'shipped';
            $order->general_status = 'processing';

            $order->created_at = now();
            $order->updated_at = now();

            $order->save();
        }
    }
}
