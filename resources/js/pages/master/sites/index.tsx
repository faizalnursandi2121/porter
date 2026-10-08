import { Head, router, usePage } from '@inertiajs/react';
import { MapPin, Plus, Search, Power } from 'lucide-react';
import { useMemo, useState } from 'react';
import ModuleLayout from '@/layouts/module-layout';
import { RowActions } from '@/components/row-actions';
import { index as sitesIndex } from '@/actions/App/Http/Controllers/SiteMasterController';
import { toggleActive as toggleActiveAction } from '@/actions/App/Http/Controllers/SiteMasterController';
import SiteFormDialog, {
    type SiteFormData,
    TIMEZONE_OPTIONS,
} from '@/pages/master/sites/site-form-dialog';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type SiteRow = {
    id: number;
    name: string;
    address: string | null;
    latitude: number;
    longitude: number;
    timezone: string;
    is_active: boolean;
    active_eos_count: number;
    connections?: { kind: string; provider: string }[] | null;
};

type Props = {
    sites: SiteRow[];
    canManageSites: boolean;
    filter?: { q?: string | null };
};

const TIMEZONE_LABELS: Record<string, string> = Object.fromEntries(
    TIMEZONE_OPTIONS.map((tz) => [tz.value, tz.label]),
);

export default function SiteMaster() {
    const { props } = usePage<
        Props & { flash?: { success?: string | null } }
    >();
    const { sites, canManageSites, filter } = props;

    const [searchTerm, setSearchTerm] = useState(filter?.q ?? '');
    const debouncedTerm = useDebouncedValue(searchTerm, 300);
    const [formOpen, setFormOpen] = useState(false);
    const [editingSite, setEditingSite] = useState<SiteFormData | null>(null);

    const visibleSites = useMemo(() => {
        const term = debouncedTerm.trim().toLowerCase();

        if (!term) {
            return sites;
        }

        return sites.filter((site) =>
            [
                site.name,
                site.address ?? '',
                site.timezone,
                site.connections?.map((c) => c.provider).join(' ') ?? '',
            ]
                .join(' ')
                .toLowerCase()
                .includes(term),
        );
    }, [sites, debouncedTerm]);

    const openCreate = () => {
        setEditingSite(null);
        setFormOpen(true);
    };

    const openEdit = (site: SiteRow) => {
        const connection = (provider: string) =>
            site.connections?.find((c) => c.kind === provider)?.provider ?? '';

        setEditingSite({
            id: site.id,
            name: site.name,
            address: site.address ?? '',
            latitude: String(site.latitude),
            longitude: String(site.longitude),
            timezone: site.timezone,
            primary_provider: connection('PRIMARY'),
            backup_provider: connection('BACKUP'),
        });
        setFormOpen(true);
    };

    const toggleActive = (site: SiteRow) => {
        router.post(
            toggleActiveAction.url(site.id),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <ModuleLayout
            title="Master Data"
            description="Manage schools, coordinates, timezones, and internet providers"
        >
            <Head title="Master Data" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="relative w-full max-w-sm">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            value={searchTerm}
                            onChange={(event) =>
                                setSearchTerm(event.target.value)
                            }
                            placeholder="Search name, address, provider…"
                            aria-label="Search sites"
                            className="pl-9"
                        />
                        {searchTerm && (
                            <button
                                type="button"
                                onClick={() => setSearchTerm('')}
                                aria-label="Clear search"
                                className="absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            >
                                ×
                            </button>
                        )}
                    </div>

                    {canManageSites && (
                        <Button onClick={openCreate}>
                            <Plus className="size-4" />
                            Add site
                        </Button>
                    )}
                </div>

                {visibleSites.length === 0 ? (
                    debouncedTerm ? (
                        <div className="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed py-16 text-center">
                            <Search className="size-8 text-muted-foreground" />
                            <p className="font-medium">
                                No results for “{debouncedTerm}”
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Check the spelling or try a different term.
                            </p>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setSearchTerm('')}
                            >
                                Clear search
                            </Button>
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed py-16 text-center">
                            <MapPin className="size-8 text-muted-foreground" />
                            <p className="font-medium">No sites yet</p>
                            <p className="text-sm text-muted-foreground">
                                Schools live here once added.
                            </p>
                            {canManageSites && (
                                <Button size="sm" onClick={openCreate}>
                                    <Plus className="size-4" />
                                    Add site
                                </Button>
                            )}
                        </div>
                    )
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>School</TableHead>
                                    <TableHead>Timezone</TableHead>
                                    <TableHead>Providers</TableHead>
                                    <TableHead className="text-right">
                                        Active EOS
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                    {canManageSites && (
                                        <TableHead className="w-24">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {visibleSites.map((site) => (
                                    <TableRow
                                        key={site.id}
                                        className={
                                            site.is_active
                                                ? undefined
                                                : 'opacity-60'
                                        }
                                    >
                                        <TableCell>
                                            <div className="font-medium">
                                                {site.name}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                {site.address}
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {TIMEZONE_LABELS[site.timezone] ??
                                                site.timezone}
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm">
                                                {
                                                    site.connections?.find(
                                                        (c) =>
                                                            c.kind ===
                                                            'PRIMARY',
                                                    )?.provider
                                                }
                                            </div>
                                            {site.connections?.some(
                                                (c) => c.kind === 'BACKUP',
                                            ) && (
                                                <div className="text-sm text-muted-foreground">
                                                    backup:{' '}
                                                    {
                                                        site.connections.find(
                                                            (c) =>
                                                                c.kind ===
                                                                'BACKUP',
                                                        )?.provider
                                                    }
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {site.active_eos_count}
                                        </TableCell>
                                        <TableCell>
                                            {site.is_active ? (
                                                <Badge>Active</Badge>
                                            ) : (
                                                <Badge variant="secondary">
                                                    Inactive
                                                </Badge>
                                            )}
                                        </TableCell>
                                        {canManageSites && (
                                            <TableCell>
                                                <RowActions
                                                    label={`Open actions for ${site.name}`}
                                                    actions={[
                                                        {
                                                            label: 'Edit',
                                                            onSelect: () =>
                                                                openEdit(site),
                                                        },
                                                        {
                                                            label: site.is_active
                                                                ? 'Deactivate'
                                                                : 'Activate',
                                                            icon: (
                                                                <Power className="size-4" />
                                                            ),
                                                            destructive:
                                                                site.is_active,
                                                            onSelect: () =>
                                                                toggleActive(
                                                                    site,
                                                                ),
                                                        },
                                                    ]}
                                                />
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <p className="text-sm tabular-nums text-muted-foreground">
                    {visibleSites.length} site
                    {visibleSites.length === 1 ? '' : 's'}
                </p>
            </div>

            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {editingSite ? 'Edit site' : 'Add site'}
                        </DialogTitle>
                        <DialogDescription>
                            {editingSite
                                ? 'Changes apply immediately and are recorded in the audit log.'
                                : 'New sites start active and appear in placement options.'}
                        </DialogDescription>
                    </DialogHeader>
                    <SiteFormDialog
                        open={formOpen}
                        onOpenChange={setFormOpen}
                        site={editingSite}
                    />
                </DialogContent>
            </Dialog>
        </ModuleLayout>
    );
}
