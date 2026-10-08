<?php

namespace Database\Factories;

use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportTemplate>
 */
class ReportTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Daily Report '.fake()->year(),
            'is_active' => true,
            'created_by' => User::factory()->withRole('ADMINISTRATOR'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
