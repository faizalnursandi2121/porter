<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialSeeder extends Seeder
{
    // FR-2: no self-registration; the first Administrator exists before any login is possible.
    // Password is for local/bootstrap only — the admin rotates it from settings;
    // the seeded account is not treated as holding a temporary password.
    public const INITIAL_ADMIN_EMAIL = 'admin@porter.local';

    public const INITIAL_ADMIN_PASSWORD = 'porter-admin-2026';

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminRole = Role::where('code', Role::ADMINISTRATOR)->firstOrFail();

        User::updateOrCreate(
            ['email' => self::INITIAL_ADMIN_EMAIL],
            [
                'name' => 'PORTER Administrator',
                'password' => Hash::make(self::INITIAL_ADMIN_PASSWORD),
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
                // FR-2: the forced change targets temp-password accounts
                // handed out by admins (epic 2.6); the seeded bootstrap admin
                // owns this install, so it starts unlocked.
                'password_changed_at' => now(),
            ],
        );

        $sites = [
            ['name' => 'Sekolah Rakyat Jakarta', 'timezone' => 'Asia/Jakarta', 'address' => 'Jl. Contoh No. 1, Jakarta', 'latitude' => -6.2088, 'longitude' => 106.8456],
            ['name' => 'Sekolah Rakyat Denpasar', 'timezone' => 'Asia/Makassar', 'address' => 'Jl. Contoh No. 2, Denpasar', 'latitude' => -8.6705, 'longitude' => 115.2126],
            ['name' => 'Sekolah Rakyat Jayapura', 'timezone' => 'Asia/Jayapura', 'address' => 'Jl. Contoh No. 3, Jayapura', 'latitude' => -2.5916, 'longitude' => 140.6690],
        ];

        foreach ($sites as $site) {
            Site::updateOrCreate(['name' => $site['name']], $site);
        }
    }
}
