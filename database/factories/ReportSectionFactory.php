<?php

namespace Database\Factories;

use App\Models\DailyReport;
use App\Models\ReportSection;
use App\Models\ReportSectionTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportSection>
 */
class ReportSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'daily_report_id' => DailyReport::factory(),
            'section_template_id' => ReportSectionTemplate::factory(),
            'payload' => ['uptime_days' => fake()->numberBetween(1, 90)],
            'explanation' => null,
        ];
    }
}
