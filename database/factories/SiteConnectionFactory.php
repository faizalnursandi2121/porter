<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\SiteConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteConnection>
 */
class SiteConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'kind' => SiteConnection::PRIMARY,
            'provider' => fake()->company(),
            'is_active' => true,
        ];
    }

    public function backup(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => SiteConnection::BACKUP,
        ]);
    }
}
