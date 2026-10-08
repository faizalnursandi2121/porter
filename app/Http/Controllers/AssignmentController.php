<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\TransferAssignmentRequest;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    /**
     * FR-3: placement list — every EOS with their active site and placement
     * history. Browsable by Supervisi + Administrator.
     */
    public function index(): Response
    {
        $eosUsers = User::query()
            ->whereHas('role', fn ($role) => $role->where('code', Role::EOS))
            ->with([
                'assignments' => fn ($query) => $query->orderByDesc('started_at'),
                'assignments.site',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $sites = Site::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('master/assignments/index', [
            'eosUsers' => $eosUsers,
            'sites' => $sites,
            'canManagePlacements' => auth()->user()?->isAdministrator()
                || auth()->user()?->isSupervisi(),
        ]);
    }

    /**
     * FR-3: create a placement. The one-active-placement-per-EOS and
     * one-active-EOS-per-site invariants are enforced here and by partial
     * unique indexes (BR: enforcement point is the DB).
     */
    public function store(StoreAssignmentRequest $request): RedirectResponse
    {
        $assignment = DB::transaction(function (): Assignment {
            return Assignment::create([
                'user_id' => $this->input('user_id'),
                'site_id' => $this->input('site_id'),
                'started_at' => $this->input('started_at'),
                'ended_at' => null,
            ]);
        });

        $this->recordAudit('assignment.created', $assignment->id, [
            'user_id' => $assignment->user_id,
            'site_id' => $assignment->site_id,
            'started_at' => $assignment->started_at->toDateString(),
            'ended_at' => null,
        ]);

        return redirect()
            ->route('master.assignments.index')
            ->with('success', __('Placement created.'));
    }

    /**
     * FR-3: transfer an EOS to another school — ends the open placement and
     * opens a new one inside one transaction. History is preserved; the
     * old row keeps its ended_at.
     */
    public function transfer(TransferAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $result = DB::transaction(function () use ($request, $assignment): array {
            $startedAt = $assignment->started_at->toDateString();

            $assignment->update(['ended_at' => $request->input('ended_at')]);

            $replacement = Assignment::create([
                'user_id' => $assignment->user_id,
                'site_id' => $request->input('site_id'),
                'started_at' => $request->input('started_at'),
                'ended_at' => null,
            ]);

            return [$assignment->refresh(), $replacement, $startedAt];
        });

        [, $replacement, $oldStartedAt] = $result;

        $this->recordAudit('assignment.transferred', $assignment->id, [
            'ended_at' => $assignment->ended_at->toDateString(),
            'new_assignment_id' => $replacement->id,
            'new_site_id' => $replacement->site_id,
            'new_started_at' => $replacement->started_at->toDateString(),
        ], [
            'site_id' => $assignment->site_id,
            'started_at' => $oldStartedAt,
            'ended_at' => null,
        ]);

        return redirect()
            ->route('master.assignments.index')
            ->with('success', __('EOS transferred.'));
    }

    /**
     * FR-3: end a placement without a replacement — EOS leaves the school
     * (offboarding). History preserved.
     */
    public function end(Assignment $assignment): RedirectResponse
    {
        $actor = auth()->user();

        if ($actor === null || ! ($actor->isAdministrator() || $actor->isSupervisi())) {
            abort(403);
        }

        if ($assignment->ended_at !== null) {
            return redirect()
                ->route('master.assignments.index')
                ->with('error', __('This placement has already ended.'));
        }

        $before = ['site_id' => $assignment->site_id, 'ended_at' => null];

        $assignment->update(['ended_at' => now()->toDateString()]);

        $this->recordAudit('assignment.ended', $assignment->id, [
            'site_id' => $assignment->site_id,
            'ended_at' => $assignment->ended_at->toDateString(),
        ], $before);

        return redirect()
            ->route('master.assignments.index')
            ->with('success', __('Placement ended.'));
    }

    /**
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>|null  $before
     */
    private function recordAudit(string $action, int $assignmentId, array $after, ?array $before = null): void
    {
        AuditLog::create([
            'actor_id' => auth()->id(),
            'actor_role' => auth()->user()?->role?->code ?? '',
            'action' => $action,
            'object_type' => 'assignment',
            'object_id' => $assignmentId,
            'occurred_at' => now(),
            'occurred_date_local' => now()->toDateString(),
            'before' => $before,
            'after' => $after,
        ]);
    }
}
