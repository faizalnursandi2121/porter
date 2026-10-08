<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\ReportTemplate;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReport>
 */
class DailyReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->withRole('EOS'),
            'site_id' => Site::factory(),
            'template_id' => ReportTemplate::factory(),
            'attendance_id' => null,
            'work_date_local' => now()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'status' => DailyReport::DRAFT,
            'report_number' => null,
            'revision_count' => 0,
            'submitted_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DailyReport::SUBMITTED,
            'report_number' => 'CMX.WR.'.now()->format('Ym').'.'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'submitted_at' => now(),
        ]);
    }

    public function needsRevision(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DailyReport::NEEDS_REVISION,
            'reopen_reason' => 'Temperature reading unclear, please retake photos.',
        ]);
    }

    public function forAttendance(Attendance $attendance): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $attendance->user_id,
            'site_id' => $attendance->site_id,
            'work_date_local' => $attendance->work_date_local,
            'attendance_id' => $attendance->id,
        ]);
    }

    public function timezone(string $tz): static
    {
        return $this->state(fn (array $attributes) => [
            'timezone' => $tz,
        ]);
    }
}
