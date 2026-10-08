<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Requests\StoreUserRequest;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * FR-2: account management list for Supervisi (read-only) and
     * Administrator — users with their role and, for EOS, the active
     * school placement.
     */
    public function index(): Response
    {
        $users = User::query()
            ->with([
                'role',
                'assignments' => fn ($query) => $query->whereNull('ended_at')->select(['id', 'user_id', 'site_id']),
                'assignments.site' => fn ($query) => $query->select(['id', 'name']),
            ])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $sites = Site::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'sites' => $sites,
            'roles' => collect([
                ['code' => Role::EOS, 'label' => 'EOS'],
                ['code' => Role::SUPERVISI, 'label' => 'Supervisi'],
                ['code' => Role::HR, 'label' => 'HR'],
                ['code' => Role::ADMINISTRATOR, 'label' => 'Administrator'],
            ]),
            // Write actions stay Administrator-only; Supervisi is read-only
            // (PRD matrix) — the page hides create/reset controls accordingly.
            'canManageUsers' => auth()->user()?->isAdministrator() ?? false,
        ]);
    }

    /**
     * FR-2: create an account with a temporary password. The plain temp
     * password is flashed once — it drives the success dialog and is never
     * recoverable afterwards (only the hash is stored).
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated, $request): User {
            $tempPassword = Str::password(12);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $tempPassword,
                'role_id' => Role::where('code', $validated['role_code'])->value('id'),
                // FR-2: NULL keeps 2.3's forced-change gate armed — the user
                // must replace the temporary password at first login.
                'password_changed_at' => null,
            ]);

            if (array_key_exists('site_id', $validated) && $validated['site_id'] !== null) {
                Assignment::create([
                    'user_id' => $user->id,
                    'site_id' => $validated['site_id'],
                    'started_at' => now()->toDateString(),
                ]);
            }

            $this->recordAudit(
                actor: $request->user(),
                action: 'user.created',
                objectId: $user->id,
                after: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_code' => $validated['role_code'],
                    'site_id' => $validated['site_id'] ?? null,
                    'password_changed_at' => null,
                ],
            );

            Inertia::flash('temp_password', $tempPassword);

            return $user;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Account created for :name.', ['name' => $user->name]),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * FR-4a tail: issue a fresh temporary password. The account goes back to
     * the forced-change state (`password_changed_at` NULL) because the admin
     * — not the owner — proved nothing about mailbox ownership.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $tempPassword = Str::password(12);

        app(ResetUserPassword::class, ['marksPasswordChanged' => false])
            ->reset($user, ['password' => $tempPassword, 'password_confirmation' => $tempPassword]);

        $this->recordAudit(
            actor: $request->user(),
            action: 'user.password_reset',
            objectId: $user->id,
            after: [
                'email' => $user->email,
                'password_changed_at' => null,
            ],
        );

        Inertia::flash('temp_password', $tempPassword);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Temporary password issued for :name.', ['name' => $user->name]),
        ]);

        return back();
    }

    /**
     * FR-46: append an audit row for an account-management action.
     */
    private function recordAudit(User $actor, string $action, int $objectId, array $after): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'actor_role' => $actor->role?->code ?? '',
            'action' => $action,
            'object_type' => 'user',
            'object_id' => $objectId,
            'occurred_at' => now(),
            'occurred_date_local' => now()->toDateString(),
            'before' => null,
            'after' => $after,
        ]);
    }
}
