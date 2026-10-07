<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'name' => 'Router '.fake()->word(),
            'category' => fake()->randomElement(['perangkat jaringan', 'kabel', 'ups', 'aksesoris']),
            'quantity' => fake()->numberBetween(1, 5),
            'received_at' => now()->subDays(fake()->numberBetween(1, 60))->toDateString(),
            'status' => InventoryItem::DIPAKAI,
            'created_by' => User::factory()->withRole('EOS'),
        ];
    }

    public function damaged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InventoryItem::RUSAK,
            'status_reason' => 'Port WAN burnt after power surge.',
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InventoryItem::HILANG,
            'status_reason' => 'Not found during monthly stock take.',
        ]);
    }
}
