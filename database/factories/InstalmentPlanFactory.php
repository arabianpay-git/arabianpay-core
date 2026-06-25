<?php

namespace Database\Factories;

use App\Models\InstalmentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstalmentPlanFactory extends Factory
{
    protected $model = InstalmentPlan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'duration' => 3,
            'finance_limit' => 10000,
            'patch_days' => '30,60,90',
            'late_fee' => 50,
            'transaction_fee' => 10,
            'installments' => 3,
            'status' => 'active',
        ];
    }
}
