<?php

use App\Models\Role;
use App\Models\Site;
use App\Models\SiteConnection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('roles table seeds the four fixed roles', function () {
    $roles = Role::factory()->count(4)->sequence(
        ['code' => Role::EOS, 'label' => 'EOS'],
        ['code' => Role::SUPERVISI, 'label' => 'Supervisi'],
        ['code' => Role::HR, 'label' => 'HR'],
        ['code' => Role::ADMINISTRATOR, 'label' => 'Administrator'],
    )->create();

    expect($roles->pluck('code')->sort()->values()->all())
        ->toBe([Role::ADMINISTRATOR, Role::EOS, Role::HR, Role::SUPERVISI])
        ->and(Role::where('code', Role::SUPERVISI)->first()->label)->toBe('Supervisi');
});

test('role codes are unique', function () {
    Role::factory()->create(['code' => Role::EOS]);

    expect(fn () => Role::factory()->create(['code' => Role::EOS]))
        ->toThrow(RuntimeException::class);
});

test('user belongs to a role and the legacy role string column is gone', function () {
    $user = User::factory()->withRole(Role::ADMINISTRATOR)->create();

    expect($user->refresh()->role->code)->toBe(Role::ADMINISTRATOR)
        ->and(DB::getSchemaBuilder()->hasColumn('users', 'role'))->toBeFalse()
        ->and(DB::getSchemaBuilder()->hasColumn('users', 'role_id'))->toBeTrue();
});

test('deleting a role is restricted while users reference it', function () {
    $role = Role::factory()->create(['code' => Role::HR]);
    User::factory()->withRole(Role::HR)->create();

    expect(fn () => $role->delete())->toThrow(RuntimeException::class);
});

test('site stores master data with timezone', function () {
    $site = Site::factory()->timezone('Asia/Makassar')->create();

    expect($site->name)->toStartWith('Sekolah Rakyat ')
        ->and($site->latitude)->toBeFloat()
        ->and($site->longitude)->toBeFloat()
        ->and($site->is_active)->toBeTrue()
        ->and($site->timezone)->toBe('Asia/Makassar');
});

test('site has one primary and one backup connection', function () {
    $site = Site::factory()->create();
    $primary = SiteConnection::factory()->create(['site_id' => $site->id]);
    $backup = SiteConnection::factory()->backup()->create(['site_id' => $site->id]);

    expect($site->connections->pluck('kind')->sort()->values()->all())
        ->toBe([SiteConnection::BACKUP, SiteConnection::PRIMARY])
        ->and($site->primaryConnection()->is($primary))->toBeTrue()
        ->and($backup->site->is($site))->toBeTrue();
});

test('site connection kind is unique per site', function () {
    $site = Site::factory()->create();
    SiteConnection::factory()->create(['site_id' => $site->id]);

    expect(fn () => SiteConnection::factory()->create(['site_id' => $site->id]))
        ->toThrow(RuntimeException::class);
});

test('deleting a site cascades to its connections', function () {
    $site = Site::factory()->create();
    SiteConnection::factory()->create(['site_id' => $site->id]);
    SiteConnection::factory()->backup()->create(['site_id' => $site->id]);

    $site->delete();

    expect(SiteConnection::count())->toBe(0);
});
