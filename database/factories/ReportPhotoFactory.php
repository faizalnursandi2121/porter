<?php

namespace Database\Factories;

use App\Models\ReportPhoto;
use App\Models\ReportSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportPhoto>
 */
class ReportPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_section_id' => ReportSection::factory(),
            'disk' => 'local',
            'path' => 'reports/'.fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(100_000, 5_000_000),
        ];
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'path' => 'reports/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}
