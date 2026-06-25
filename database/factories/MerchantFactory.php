<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cr_number' => fake()->unique()->numerify('########'),
            'owner_name' => fake()->name(),
            'owner_iqama_number' => fake()->unique()->numerify('############'),
            'vat_register_number' => fake()->unique()->numerify('########'),
            'pos_revenue' => fake()->randomFloat(2, 10000, 500000),
            'goverment_data' => '[]',
            'term_status' => 'accepted',
            'status' => 'active',
            'is_integration' => false,
        ];
    }
}
