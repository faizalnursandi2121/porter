<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\InventoryPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryPhoto>
 */
class InventoryPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'disk' => 'local',
            'path' => 'inventory/'.fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(100_000, 5_000_000),
        ];
    }
}
