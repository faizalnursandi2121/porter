import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Users } from 'lucide-react';
import { useState } from 'react';
import ModuleLayout from '@/layouts/module-layout';
import { RowActions } from '@/components/row-actions';
import {
    store as storeAssignment,
    transfer as transferAssignment,
    end as endAssignment,
} from '@/actions/App/Http/Controllers/AssignmentController';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AssignmentForm, {
    type AssignmentFormMode,
} from '@/pages/master/assignments/assignment-form-dialog';

type AssignmentData = {
    id: number;
    site_id: number;
    started_at: string;
    ended_at: string | null;
    site?: { id: number; name: string } | null;
};

type EosUser = {
    id: number;
    name: string;
    email: string;
    assignments: AssignmentData[];
};

type SiteOption = {
    id: number;
    name: string;
};

type Props = {
    eosUsers: EosUser[];
    sites: SiteOption[];
    canManagePlacements: boolean;
};

const dateFormatter = new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

export default function AssignmentIndex() {
    const { props } = usePage<
        Props & { flash?: { success?: string | null; error?: string | null } }
    >();
    const { eosUsers, sites, canManagePlacements } = props;

    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState<AssignmentFormMode>(null);

    const openCreate = (userId: number, siteId?: number) => {
        setFormMode(
            siteId !== undefined
                ? { kind: 'transfer', userId, fromSiteId: siteId }
                : { kind: 'create', userId },
        );
        setFormOpen(true);
    };

    const openTransfer = (userId: number, fromSiteId: number) => {
        setFormMode({ kind: 'transfer', userId, fromSiteId });
        setFormOpen(true);
    };

    const endPlacement = (assignmentId: number) => {
        router.post(endAssignment.url(assignmentId), {}, { preserveScroll: true });
    };

    return (
        <ModuleLayout
            title="Assignments"
            description="Place EOS at schools — one active placement per EOS and per school"
        >
            <Head title="Assignments" />

            <div className="space-y-6">
                {eosUsers.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed py-16 text-center">
                        <Users className="size-8 text-muted-foreground" />
                        <p className="font-medium">No EOS accounts yet</p>
                        <p className="text-sm text-muted-foreground">
                            Create EOS accounts from the Users page first.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>EOS</TableHead>
                                    <TableHead>Active placement</TableHead>
                                    <TableHead>Since</TableHead>
                                    <TableHead>History</TableHead>
                                    {canManagePlacements && (
                                        <TableHead className="w-40">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {eosUsers.map((eos) => {
                                    const active = eos.assignments.find(
                                        (a) => a.ended_at === null,
                                    );
                                    const pastCount =
                                        eos.assignments.length -
                                        (active !== undefined ? 1 : 0);

                                    return (
                                        <TableRow key={eos.id}>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {eos.name}
                                                </div>
                                                <div className="text-sm text-muted-foreground">
                                                    {eos.email}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {active ? (
                                                    <Badge>
                                                        {active.site?.name ??
                                                            '—'}
                                                    </Badge>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">
                                                        Unplaced
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-muted-foreground tabular-nums">
                                                {active
                                                    ? dateFormatter.format(
                                                          new Date(
                                                              active.started_at,
                                                          ),
                                                      )
                                                    : '—'}
                                            </TableCell>
                                            <TableCell className="tabular-nums text-muted-foreground">
                                                {pastCount > 0
                                                    ? `${pastCount} previous`
                                                    : '—'}
                                            </TableCell>
                                            {canManagePlacements && (
                                                <TableCell>
                                                    <RowActions
                                                        label={`Open actions for ${eos.name}`}
                                                        actions={[
                                                            ...(active
                                                                ? [
                                                                      {
                                                                          label: 'Transfer',
                                                                          onSelect: () =>
                                                                              openTransfer(
                                                                                  eos.id,
                                                                                  active.site_id,
                                                                              ),
                                                                      },
                                                                      {
                                                                          label: 'End placement',
                                                                          destructive: true,
                                                                          onSelect: () =>
                                                                              endPlacement(
                                                                                  active.id,
                                                                              ),
                                                                      },
                                                                  ]
                                                                : [
                                                                      {
                                                                          label: 'Place at school',
                                                                          icon: (
                                                                              <Plus className="size-4" />
                                                                          ),
                                                                          onSelect: () =>
                                                                              openCreate(
                                                                                  eos.id,
                                                                              ),
                                                                      },
                                                                  ]),
                                                        ]}
                                                    />
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <p className="text-sm tabular-nums text-muted-foreground">
                    {eosUsers.length} EOS account
                    {eosUsers.length === 1 ? '' : 's'}
                </p>
            </div>

            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {formMode?.kind === 'transfer'
                                ? 'Transfer EOS'
                                : 'Place EOS'}
                        </DialogTitle>
                        <DialogDescription>
                            {formMode?.kind === 'transfer'
                                ? 'The current placement ends and a new one opens — history is preserved.'
                                : 'The EOS gets an active placement at one school.'}
                        </DialogDescription>
                    </DialogHeader>
                    <AssignmentForm
                        mode={formMode}
                        sites={sites}
                        activeAssignmentId={
                            formMode?.kind === 'transfer'
                                ? (eosUsers
                                      .find(
                                          (eos) =>
                                              eos.id === formMode.userId,
                                      )
                                      ?.assignments.find(
                                          (a) => a.ended_at === null,
                                      )?.id ?? null)
                                : null
                        }
                        onOpenChange={setFormOpen}
                    />
                </DialogContent>
            </Dialog>
        </ModuleLayout>
    );
}
