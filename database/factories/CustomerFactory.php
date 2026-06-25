<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'id_number' => fake()->unique()->numerify('############'),
            'id_owner' => fake()->name(),
            'cr_number' => fake()->unique()->numerify('########'),
            'tax_number' => fake()->unique()->numerify('########'),
            'cr_data' => [],
            'check_nafath' => false,
            'nafath_data' => json_encode(false),
            'date_of_birth' => fake()->date(),
            'purchasing_volume' => fake()->randomFloat(2, 1000, 50000),
            'purchasing_natures' => fake()->words(3, true),
            'other_purchasing_natures' => fake()->sentence(),
            'status' => 'approved',
        ];
    }
}
