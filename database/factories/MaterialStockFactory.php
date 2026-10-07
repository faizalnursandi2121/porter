<?php

namespace Database\Factories;

use App\Models\MaterialStock;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialStock>
 */
class MaterialStockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'name' => fake()->randomElement(['kabel UTI', 'tie cable', 'connector SC', 'mika tirai']),
            'unit' => fake()->randomElement(['meter', 'pcs', 'lembar']),
            'balance' => fake()->numberBetween(0, 100),
        ];
    }
}
