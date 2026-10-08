<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->withRole('EOS'),
            'site_id' => Site::factory(),
            'started_at' => now()->subDays(30)->toDateString(),
            'ended_at' => null,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now()->subDay()->toDateString(),
        ]);
    }
}
