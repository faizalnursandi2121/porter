<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => Role::EOS,
            'label' => 'EOS',
        ];
    }

    public function code(string $code, string $label): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
            'label' => $label,
        ]);
    }
}
