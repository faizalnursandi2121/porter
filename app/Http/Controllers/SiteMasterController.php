<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\AuditLog;
use App\Models\Site;
use App\Models\SiteConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SiteMasterController extends Controller
{
    /**
     * FR-34: site master data list. Every role may browse (FR-1); write
     * actions are Administrator-only per the UI.
     */
    public function index(): Response
    {
        $sites = Site::query()
            ->with('connections')
            ->withCount([
                'assignments as active_eos_count' => fn ($query) => $query->whereNull('ended_at'),
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('master/sites/index', [
            'sites' => $sites,
            'canManageSites' => auth()->user()?->isAdministrator() ?? false,
        ]);
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $site = DB::transaction(function () use ($request): Site {
            $site = Site::create([
                'name' => $request->input('name'),
                'address' => $request->input('address'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'timezone' => $request->input('timezone'),
                'is_active' => true,
            ]);

            $site->connections()->create([
                'kind' => SiteConnection::PRIMARY,
                'provider' => $request->input('primary_provider'),
                'is_active' => true,
            ]);

            if (filled($request->input('backup_provider'))) {
                $site->connections()->create([
                    'kind' => SiteConnection::BACKUP,
                    'provider' => $request->input('backup_provider'),
                    'is_active' => true,
                ]);
            }

            return $site;
        });

        $this->recordAudit('site.created', $site->id, [
            'name' => $site->name,
            'timezone' => $site->timezone,
            'primary_provider' => $request->input('primary_provider'),
            'backup_provider' => $request->input('backup_provider'),
        ]);

        return redirect()
            ->route('master.sites.index')
            ->with('success', __('Site created.'));
    }

    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $before = [
            'name' => $site->name,
            'address' => $site->address,
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
            'timezone' => $site->timezone,
            'primary_provider' => $site->primaryConnection()?->provider,
            'backup_provider' => $site->connections()->where('kind', SiteConnection::BACKUP)->value('provider'),
        ];

        $site = DB::transaction(function () use ($request, $site): Site {
            $site->update([
                'name' => $request->input('name'),
                'address' => $request->input('address'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'timezone' => $request->input('timezone'),
            ]);

            $site->primaryConnection()?->update([
                'provider' => $request->input('primary_provider'),
            ]);

            $backup = $site->connections()->where('kind', SiteConnection::BACKUP)->first();

            if (filled($request->input('backup_provider'))) {
                $backup
                    ? $backup->update(['provider' => $request->input('backup_provider')])
                    : $site->connections()->create([
                        'kind' => SiteConnection::BACKUP,
                        'provider' => $request->input('backup_provider'),
                        'is_active' => true,
                    ]);
            } elseif ($backup) {
                $backup->delete();
            }

            return $site;
        });

        $after = [
            'name' => $site->name,
            'address' => $site->address,
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
            'timezone' => $site->timezone,
            'primary_provider' => $request->input('primary_provider'),
            'backup_provider' => $request->input('backup_provider'),
        ];

        $this->recordAudit('site.updated', $site->id, $after, $before);

        return redirect()
            ->route('master.sites.index')
            ->with('success', __('Site updated.'));
    }

    /**
     * FR-36: deactivation, never deletion — historical data references the
     * site. Reactivation is the same endpoint with the flag flipped.
     */
    public function toggleActive(Site $site): RedirectResponse
    {
        $actor = auth()->user();

        if ($actor === null || ! $actor->isAdministrator()) {
            abort(403);
        }

        $site->update(['is_active' => ! $site->is_active]);

        $this->recordAudit(
            $site->is_active ? 'site.activated' : 'site.deactivated',
            $site->id,
            ['is_active' => $site->is_active],
            ['is_active' => ! $site->is_active],
        );

        return redirect()
            ->route('master.sites.index')
            ->with('success', $site->is_active ? __('Site activated.') : __('Site deactivated.'));
    }

    /**
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>|null  $before
     */
    private function recordAudit(string $action, int $siteId, array $after, ?array $before = null): void
    {
        AuditLog::create([
            'actor_id' => auth()->id(),
            'actor_role' => auth()->user()?->role?->code ?? '',
            'action' => $action,
            'object_type' => 'site',
            'object_id' => $siteId,
            'occurred_at' => now(),
            'occurred_date_local' => now()->toDateString(),
            'before' => $before,
            'after' => $after,
        ]);
    }
}
