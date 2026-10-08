<?php

namespace Tests\Feature\Master;

use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\InitialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacementTest extends TestCase
{
    use RefreshDatabase;

    // FR-34/FR-1: every role may browse site master data, but only the
    // Administrator may create, update, or deactivate.
    public function test_site_master_role_matrix(): void
    {
        $this->seed(InitialSeeder::class);

        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();
        $supervisi = User::factory()->withRole(Role::SUPERVISI)->create();
        $eos = User::factory()->withRole(Role::EOS)->create();
        $hr = User::factory()->withRole(Role::HR)->create();

        foreach ([$admin, $supervisi, $eos, $hr] as $actor) {
            $this->actingAs($actor)->get('/supervisi/master/sites')->assertOk();
        }

        $payload = [
            'name' => 'SD New Site',
            'address' => 'Jl. Pendidikan 1',
            'latitude' => -4.123,
            'longitude' => 120.456,
            'timezone' => 'Asia/Jakarta',
            'primary_provider' => 'Telkom',
        ];

        $target = Site::factory()->create();

        foreach ([$supervisi, $eos, $hr] as $actor) {
            $this->actingAs($actor)->post('/supervisi/master/sites', $payload)->assertForbidden();
            $this->actingAs($actor)->put("/supervisi/master/sites/{$target->id}", $payload)->assertForbidden();
            $this->actingAs($actor)->post("/supervisi/master/sites/{$target->id}/toggle-active")->assertForbidden();
        }

        $this->actingAs($admin)->post('/supervisi/master/sites', $payload)->assertRedirect();
    }

    // FR-34: site creation persists core fields plus both connections.
    public function test_administrator_creates_site_with_primary_and_backup_providers(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();

        $this->actingAs($admin)->post('/supervisi/master/sites', [
            'name' => 'SD Sekolah Rakyat A',
            'address' => 'Jl. Pendidikan 10',
            'latitude' => -4.1,
            'longitude' => 120.2,
            'timezone' => 'Asia/Makassar',
            'primary_provider' => 'Telkom',
            'backup_provider' => 'Indosat',
        ])->assertRedirect();

        $site = Site::where('name', 'SD Sekolah Rakyat A')->firstOrFail();

        $this->assertTrue($site->is_active);
        $this->assertSame('Telkom', $site->primaryConnection()?->provider);
        $this->assertSame(
            'Indosat',
            $site->connections()->where('kind', 'BACKUP')->value('provider'),
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'site.created',
            'object_type' => 'site',
            'object_id' => $site->id,
        ]);
    }

    // FR-36: deactivation never deletes — the site row stays and history
    // referencing it is untouched.
    public function test_administrator_deactivates_site_without_deleting_data(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();
        $site = Site::factory()->create();

        $this->actingAs($admin)->post("/supervisi/master/sites/{$site->id}/toggle-active")->assertRedirect();

        $site->refresh();
        $this->assertFalse($site->is_active);
        $this->assertDatabaseHas('sites', ['id' => $site->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'site.deactivated',
            'object_type' => 'site',
            'object_id' => $site->id,
        ]);

        // Re-open: the same endpoint flips it back on.
        $this->actingAs($admin)->post("/supervisi/master/sites/{$site->id}/toggle-active")->assertRedirect();
        $this->assertTrue($site->refresh()->is_active);
    }

    // FR-3: placement rules enforced server-side — one active placement per
    // EOS, one active EOS per site, target must be an active site.
    public function test_placement_uniqueness_rules(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();
        $eos = User::factory()->withRole(Role::EOS)->create();
        $otherEos = User::factory()->withRole(Role::EOS)->create();

        $place = fn (User $user, Site $site) => $this->actingAs($admin)->post('/supervisi/master/assignments', [
            'user_id' => $user->id,
            'site_id' => $site->id,
            'started_at' => '2026-10-01',
        ]);

        $place($eos, $siteA)->assertRedirect();
        $assignment = Assignment::where('user_id', $eos->id)->whereNull('ended_at')->sole();

        // Same EOS twice: rejected.
        $place($eos, $siteB)->assertSessionHasErrors(['user_id']);

        // Same site for a second EOS: rejected.
        $place($otherEos, $siteA)->assertSessionHasErrors(['site_id']);

        // Inactive site: rejected.
        $siteC = Site::factory()->inactive()->create();
        $place($otherEos, $siteC)->assertSessionHasErrors(['site_id']);

        // Non-EOS account: rejected.
        $hr = User::factory()->withRole(Role::HR)->create();
        $place($hr, $siteB)->assertSessionHasErrors(['user_id']);

        $this->assertSame(1, Assignment::whereNull('ended_at')->count());
        $this->assertSame($siteA->id, $assignment->site_id);
    }

    // FR-3: transfer ends the open placement and opens a new one — history
    // keeps both rows with their dates.
    public function test_transfer_preserves_history_and_enforces_order(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();
        $siteA = Site::factory()->create();
        $siteB = Site::factory()->create();
        $eos = User::factory()->withRole(Role::EOS)->create();

        Assignment::create([
            'user_id' => $eos->id,
            'site_id' => $siteA->id,
            'started_at' => '2026-01-10',
        ]);

        $assignment = Assignment::where('user_id', $eos->id)->whereNull('ended_at')->sole();

        // started_at before ended_at: rejected.
        $this->actingAs($admin)->post("/supervisi/master/assignments/{$assignment->id}/transfer", [
            'site_id' => $siteB->id,
            'ended_at' => '2026-06-30',
            'started_at' => '2026-01-01',
        ])->assertSessionHasErrors(['started_at']);

        // Valid transfer.
        $this->actingAs($admin)->post("/supervisi/master/assignments/{$assignment->id}/transfer", [
            'site_id' => $siteB->id,
            'ended_at' => '2026-06-30',
            'started_at' => '2026-07-01',
        ])->assertRedirect();

        $this->assertSame('2026-06-30', $assignment->refresh()->ended_at->toDateString());

        $newAssignment = Assignment::where('user_id', $eos->id)->whereNull('ended_at')->sole();
        $this->assertSame($siteB->id, $newAssignment->site_id);
        $this->assertSame('2026-07-01', $newAssignment->started_at->toDateString());

        $this->assertSame(2, Assignment::where('user_id', $eos->id)->count());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'assignment.transferred',
            'object_type' => 'assignment',
            'object_id' => $assignment->id,
        ]);
    }

    // FR-3: ending a placement keeps the row (offboarding, not deletion)
    // and frees the EOS for a future placement.
    public function test_end_placement_keeps_history_and_audits(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();
        $site = Site::factory()->create();
        $eos = User::factory()->withRole(Role::EOS)->create();

        $assignment = Assignment::create([
            'user_id' => $eos->id,
            'site_id' => $site->id,
            'started_at' => '2026-03-01',
        ]);

        $this->actingAs($admin)->post("/supervisi/master/assignments/{$assignment->id}/end")->assertRedirect();

        $this->assertNotNull($assignment->refresh()->ended_at);
        $this->assertSame(1, Assignment::count());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'assignment.ended',
            'object_type' => 'assignment',
            'object_id' => $assignment->id,
        ]);
    }

    // FR-3/FR-24 parity: audit rows carry before/after values.
    public function test_assignment_audit_records_before_and_after(): void
    {
        $this->seed(InitialSeeder::class);
        $admin = User::factory()->withRole(Role::ADMINISTRATOR)->create();
        $site = Site::factory()->create();
        $eos = User::factory()->withRole(Role::EOS)->create();

        $this->actingAs($admin)->post('/supervisi/master/assignments', [
            'user_id' => $eos->id,
            'site_id' => $site->id,
            'started_at' => '2026-05-05',
        ])->assertRedirect();

        $audit = AuditLog::where('action', 'assignment.created')->sole();

        $this->assertSame($eos->id, $audit->after['user_id']);
        $this->assertSame($site->id, $audit->after['site_id']);
        $this->assertSame('2026-05-05', $audit->after['started_at']);
        $this->assertSame($admin->id, $audit->actor_id);
    }
}
