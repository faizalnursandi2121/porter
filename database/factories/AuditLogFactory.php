<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->withRole('ADMINISTRATOR'),
            'actor_role' => 'ADMINISTRATOR',
            'action' => 'inventory.status_change',
            'object_type' => 'inventory_item',
            'object_id' => fake()->numberBetween(1, 100),
            'occurred_at' => now(),
            'occurred_date_local' => now()->toDateString(),
            'before' => ['status' => 'dipakai'],
            'after' => ['status' => 'rusak'],
        ];
    }
}
