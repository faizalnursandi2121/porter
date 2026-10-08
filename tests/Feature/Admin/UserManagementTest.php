<?php

namespace Tests\Feature\Admin;

use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\InitialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // FR-1: server-side role matrix on the account management routes.
    public function test_role_matrix_gates_the_account_management_routes(): void
    {
        $this->seed(InitialSeeder::class);

        $users = [
            Role::ADMINISTRATOR => User::factory()->withRole(Role::ADMINISTRATOR)->create(),
            Role::SUPERVISI => User::factory()->withRole(Role::SUPERVISI)->create(),
            Role::EOS => User::factory()->withRole(Role::EOS)->create(),
            Role::HR => User::factory()->withRole(Role::HR)->create(),
        ];

        $target = User::factory()->create();

        foreach ($users as $code => $actor) {
            $canRead = in_array($code, [Role::SUPERVISI, Role::ADMINISTRATOR], true);
            $canWrite = $code === Role::ADMINISTRATOR;

            $read = $this->actingAs($actor)->get('/admin/users');
            $canRead
                ? $read->assertOk()
                : $read->assertForbidden();

            $create = $this->actingAs($actor)->post('/admin/users', [
                'name' => 'New User',
                'email' => "new-{$code}@porter.test",
                'role_code' => Role::HR,
            ]);
            $canWrite
                ? $create->assertRedirect()
                : $create->assertForbidden();

            $reset = $this->actingAs($actor)->post("/admin/users/{$target->id}/reset-password");
            $canWrite
                ? $reset->assertRedirect()
                : $reset->assertForbidden();
        }
    }

    // FR-1: guests never see a role-gated area.
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/users')->assertRedirect(route('login'));
    }

    // FR-2: creating an EOS account without a school placement is rejected.
    public function test_creating_eos_without_site_fails_validation(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Placedless EOS',
                'email' => 'eos.unplaced@porter.test',
                'role_code' => Role::EOS,
            ])
            ->assertSessionHasErrors('site_id');

        $this->assertDatabaseMissing(User::class, [
            'email' => 'eos.unplaced@porter.test',
        ]);
        $this->assertDatabaseCount(AuditLog::class, 0);
    }

    // FR-2: an EOS account is created with a temp password, forced-change
    // state, one-time flash, and an audit row.
    public function test_creating_eos_with_site_creates_temporary_account_and_audits(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();
        $site = Site::factory()->create();

        $response = $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Field EOS',
                'email' => 'field.eos@porter.test',
                'role_code' => Role::EOS,
                'site_id' => $site->id,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $tempPassword = $this->extractFlashedTempPassword($response);

        $user = User::where('email', 'field.eos@porter.test')->firstOrFail();

        $this->assertNotSame('', $tempPassword, 'temp password must be flashed once');
        $this->assertTrue(Hash::check($tempPassword, $user->password));
        $this->assertNull($user->password_changed_at, 'temp password must keep the forced-change gate armed');
        $this->assertTrue($user->isEos());

        // FR-3: placement at creation — one active assignment at the school.
        $this->assertDatabaseHas(Assignment::class, [
            'user_id' => $user->id,
            'site_id' => $site->id,
            'ended_at' => null,
        ]);

        // FR-46: audit row for account creation.
        $this->assertDatabaseHas(AuditLog::class, [
            'actor_id' => $admin->id,
            'actor_role' => Role::ADMINISTRATOR,
            'action' => 'user.created',
            'object_type' => 'user',
            'object_id' => $user->id,
        ]);
    }

    // FR-2: non-EOS accounts need no placement.
    public function test_creating_non_eos_accounts_without_site_succeeds(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();

        foreach ([Role::SUPERVISI, Role::HR, Role::ADMINISTRATOR] as $index => $code) {
            $this->actingAs($admin)
                ->post('/admin/users', [
                    'name' => "Staff {$index}",
                    'email' => "staff.{$index}@porter.test",
                    'role_code' => $code,
                ])
                ->assertSessionHasNoErrors();

            $user = User::where('email', "staff.{$index}@porter.test")->firstOrFail();
            $this->assertTrue($user->hasRole($code));
            $this->assertNull($user->password_changed_at);
            $this->assertDatabaseMissing(Assignment::class, ['user_id' => $user->id]);
        }
    }

    // FR-4a tail: an admin reset installs a fresh temp password and re-arms
    // the forced-change gate.
    public function test_reset_password_issues_new_temp_password_and_rearms_gate(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();
        $user = User::factory()->withRole(Role::EOS)->create([
            'password' => Hash::make('old-password'),
            'password_changed_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/reset-password")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $tempPassword = $this->extractFlashedTempPassword($response);

        $user->refresh();
        $this->assertNull($user->password_changed_at, 'forced-change gate must be re-armed');
        $this->assertNotSame('old-password', $user->password);
        $this->assertTrue(Hash::check($tempPassword, $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));

        $this->assertDatabaseHas(AuditLog::class, [
            'actor_id' => $admin->id,
            'actor_role' => Role::ADMINISTRATOR,
            'action' => 'user.password_reset',
            'object_type' => 'user',
            'object_id' => $user->id,
        ]);
    }

    // FR-2: emails are unique across accounts.
    public function test_duplicate_email_fails_validation(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();
        $existing = User::factory()->create(['email' => 'taken@porter.test']);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Copycat',
                'email' => 'taken@porter.test',
                'role_code' => Role::HR,
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'taken@porter.test')->count());
    }

    // ui-design §2.2: the list ships role badges, the EOS site column, and
    // pagination props for the table.
    public function test_index_lists_users_with_role_site_and_pagination(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create([
            'name' => 'Ada Admin',
        ]);
        $site = Site::factory()->create(['name' => 'Sekolah Rakyat Test']);
        $eos = User::factory()->withRole(Role::EOS)->create(['name' => 'Eo S']);
        Assignment::factory()->create(['user_id' => $eos->id, 'site_id' => $site->id]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->where('canManageUsers', true)
                ->has('users.data', 3)
                ->has('users.links')
                ->has('sites', 4)
                ->has('roles', 4)
                ->etc());
    }

    // PRD matrix: Supervisi reads the list but the page hides write controls.
    public function test_supervisi_sees_read_only_list(): void
    {
        $this->seed(InitialSeeder::class);
        $supervisi = User::factory()->withRole(Role::SUPERVISI)->create();

        $this->actingAs($supervisi)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->where('canManageUsers', false));
    }

    /**
     * Pull the one-shot temp password out of the Inertia flash payload.
     */
    private function extractFlashedTempPassword($response): string
    {
        $flash = $response->getSession()->get('inertia.flash_data');

        $tempPassword = $flash['temp_password'] ?? null;

        return is_string($tempPassword) ? $tempPassword : '';
    }
}
