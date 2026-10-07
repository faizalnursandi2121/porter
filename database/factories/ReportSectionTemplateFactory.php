<?php

namespace Database\Factories;

use App\Models\ReportSectionTemplate;
use App\Models\ReportTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportSectionTemplate>
 */
class ReportSectionTemplateFactory extends Factory
{
    private static int $sequence = 0;

    public function definition(): array
    {
        return [
            'template_id' => ReportTemplate::factory(),
            'name' => fake()->unique()->randomElement([
                'Info Umum', 'Router & Firewall', 'Access Point',
                'Infrastruktur & Lingkungan', 'Konektivitas Dual-Link',
            ]),
            'order' => ++self::$sequence,
            'is_active' => true,
        ];
    }
}
