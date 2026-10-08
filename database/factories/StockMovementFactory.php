<?php

namespace Database\Factories;

use App\Models\MaterialStock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        $before = fake()->numberBetween(0, 50);

        return [
            'material_stock_id' => MaterialStock::factory(),
            'user_id' => User::factory()->withRole('EOS'),
            'type' => StockMovement::RECEIPT,
            'quantity' => fake()->numberBetween(1, 20),
            'before_balance' => $before,
            'after_balance' => $before,
            'reason' => null,
        ];
    }
}
