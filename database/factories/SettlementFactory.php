<?php

namespace Database\Factories;

use App\Models\Settlement;
use App\Models\User;
use Database\Factories\Concerns\ManagesApprovalContext;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SettlementFactory extends Factory
{
    use ManagesApprovalContext;

    protected $model = Settlement::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'supplier_user_id' => User::factory(),
            'settlement_number' => 'STL-'.fake()->unique()->numerify('######'),
            'start_date' => now()->subWeek(),
            'end_date' => now(),
            'settlement_date' => now(),
            'total_amount' => 0,
            'commission_amount' => 0,
            'payable_amount' => 0,
            'status' => 'draft',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function () {
            $this->ensureApprovalContext();
        })->afterCreating(function () {
            $this->restoreApprovalContext();
        });
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
