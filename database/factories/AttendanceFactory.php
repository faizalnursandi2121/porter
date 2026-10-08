<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->withRole('EOS'),
            'site_id' => Site::factory(),
            'work_date_local' => now()->toDateString(),
            'checked_in_at' => now(),
            'check_in_latitude' => fake()->latitude(),
            'check_in_longitude' => fake()->longitude(),
            'check_in_accuracy_m' => fake()->randomFloat(2, 3, 25),
            'check_in_selfie_path' => 'attendances/'.fake()->uuid().'.jpg',
            'checked_out_at' => null,
            'status' => Attendance::CHECKED_IN,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_out_at' => now(),
            'check_out_latitude' => fake()->latitude(),
            'check_out_longitude' => fake()->longitude(),
            'check_out_accuracy_m' => fake()->randomFloat(2, 3, 25),
            'check_out_selfie_path' => 'attendances/'.fake()->uuid().'.jpg',
            'status' => Attendance::COMPLETED,
        ]);
    }
}
