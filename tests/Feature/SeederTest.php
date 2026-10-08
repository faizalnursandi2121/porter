<?php

use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('role seeder creates the four fixed roles idempotently', function () {
    $this->seed(RoleSeeder::class);

    expect(Role::count())->toBe(4)
        ->and(Role::where('code', Role::SUPERVISI)->first()->label)->toBe('Supervisi')
        ->and(Role::where('code', Role::ADMINISTRATOR)->first()->label)->toBe('Administrator');

    $this->seed(RoleSeeder::class);

    expect(Role::count())->toBe(4);
});

test('initial seeder provisions admin account and sample sites across timezones', function () {
    $this->seed(InitialSeeder::class);

    $admin = User::where('email', Database\Seeders\InitialSeeder::INITIAL_ADMIN_EMAIL)->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role->code)->toBe(Role::ADMINISTRATOR)
        ->and(Site::count())->toBe(3)
        ->and(Site::pluck('timezone')->unique())->toHaveCount(3)
        ->and(Site::where('timezone', 'Asia/Jayapura')->exists())->toBeTrue();
});

test('initial seeder is idempotent', function () {
    $this->seed(InitialSeeder::class);
    $this->seed(InitialSeeder::class);

    expect(User::where('email', Database\Seeders\InitialSeeder::INITIAL_ADMIN_EMAIL)->count())->toBe(1)
        ->and(Site::count())->toBe(3)
        ->and(Role::count())->toBe(4);
});

// Bootstrap path a fresh environment actually runs (FR-2: admin must exist before any login).
test('database seeder provisions roles, admin, and sites', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Role::count())->toBe(4)
        ->and(User::where('email', Database\Seeders\InitialSeeder::INITIAL_ADMIN_EMAIL)->first()->role->code)
        ->toBe(Role::ADMINISTRATOR)
        ->and(Site::count())->toBe(3);
});
