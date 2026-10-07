<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['code' => Role::EOS, 'label' => 'EOS'],
            ['code' => Role::SUPERVISOR, 'label' => 'Supervisi'],
            ['code' => Role::HR, 'label' => 'HR'],
            ['code' => Role::ADMINISTRATOR, 'label' => 'Administrator'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['code' => $role['code']], ['label' => $role['label']]);
        }
    }
}
