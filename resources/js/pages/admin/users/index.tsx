import { Head, Link, router, usePage } from "@inertiajs/react";
import { MoreHorizontal, KeyRound, Search, UserPlus, X } from "lucide-react";
import { useState } from "react";
import ModuleLayout from "@/layouts/module-layout";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { index as usersIndex } from "@/actions/App/Http/Controllers/UserManagementController";
import { resetPassword } from "@/actions/App/Http/Controllers/UserManagementController";
import CreateUserDialog from "@/pages/admin/users/create";
import TempPasswordDialog, {
    useTempPasswordFlash,
} from "@/pages/admin/users/temp-password-dialog";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { cn } from "@/lib/utils";

type RoleOption = {
    code: string;
    label: string;
};

type SiteOption = {
    id: number;
    name: string;
};

type UserRow = {
    id: number;
    name: string;
    email: string;
    created_at: string;
    role?: { code: string; label: string } | null;
    assignments?: { site?: { id: number; name: string } | null }[] | null;
};

type PaginatedUsers = {
    data: UserRow[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    users: PaginatedUsers;
    sites: SiteOption[];
    roles: RoleOption[];
    canManageUsers: boolean;
    filter?: { q?: string | null };
};

const roleBadgeVariant: Record<
    string,
    "default" | "secondary" | "outline" | "destructive"
> = {
    ADMINISTRATOR: "default",
    SUPERVISI: "secondary",
    EOS: "outline",
    HR: "destructive",
};

const dateFormatter = new Intl.DateTimeFormat("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
});

export default function UserManagement() {
    const { props } = usePage<
        Props & { flash?: { temp_password?: string | null } }
    >();
    const { users, sites, roles, canManageUsers, filter } = props;

    // One-shot temp password from create/reset; the server only flashes it.
    const [tempPassword, clearTempPassword] = useTempPasswordFlash(
        props.flash?.temp_password,
    );

    // Server-side search: debounced term drives ?q= via Inertia partial reload.
    const [searchTerm, setSearchTerm] = useState(filter?.q ?? "");
    const debouncedTerm = useDebouncedValue(searchTerm, 300);

    if ((debouncedTerm || "") !== (filter?.q || "")) {
        router.get(
            usersIndex.url(),
            debouncedTerm ? { q: debouncedTerm } : {},
            {
                preserveState: true,
                preserveScroll: true,
                only: ["users", "filter"],
            },
        );
    }

    return (
        <ModuleLayout
            title="Users"
            description="Manage user accounts, roles, and site placements"
        >
            <Head title="Users" />

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
                            placeholder="Search name, email, role, site…"
                            aria-label="Search users"
                            className="pl-9"
                        />
                        {searchTerm && (
                            <button
                                type="button"
                                onClick={() => setSearchTerm("")}
                                aria-label="Clear search"
                                className="absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            >
                                <X className="size-4" />
                            </button>
                        )}
                    </div>

                    {canManageUsers && (
                        <CreateUserDialog roles={roles} sites={sites} />
                    )}
                </div>

                {users.data.length === 0 ? (
                    searchTerm ? (
                        <div className="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed py-16 text-center">
                            <Search className="size-8 text-muted-foreground" />
                            <p className="font-medium">
                                No results for “{searchTerm}”
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Check the spelling or try a different term.
                            </p>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setSearchTerm("")}
                            >
                                Clear search
                            </Button>
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed py-16 text-center">
                            <UserPlus className="size-8 text-muted-foreground" />
                            <p className="font-medium">No users yet</p>
                            <p className="text-sm text-muted-foreground">
                                Accounts for every PORTER role live here.
                            </p>
                            {canManageUsers && (
                                <CreateUserDialog roles={roles} sites={sites} />
                            )}
                        </div>
                    )
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Site</TableHead>
                                    <TableHead>Created</TableHead>
                                    {canManageUsers && (
                                        <TableHead className="w-12">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => {
                                    const activeSite =
                                        user.assignments?.find(
                                            (assignment) => assignment.site,
                                        )?.site ?? null;

                                    return (
                                        <TableRow key={user.id}>
                                            <TableCell className="font-medium">
                                                {user.name}
                                            </TableCell>
                                            <TableCell>{user.email}</TableCell>
                                            <TableCell>
                                                {user.role ? (
                                                    <Badge
                                                        variant={
                                                            roleBadgeVariant[
                                                                user.role.code
                                                            ] ?? "outline"
                                                        }
                                                    >
                                                        {user.role.label}
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {activeSite?.name ?? "—"}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                                {dateFormatter.format(
                                                    new Date(user.created_at),
                                                )}
                                            </TableCell>
                                            {canManageUsers && (
                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger
                                                            asChild
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon-sm"
                                                            >
                                                                <MoreHorizontal className="size-4" />
                                                                <span className="sr-only">
                                                                    Open actions
                                                                    for{" "}
                                                                    {user.name}
                                                                </span>
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <ResetPasswordMenuItem
                                                                userId={user.id}
                                                                userName={
                                                                    user.name
                                                                }
                                                            />
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {users.data.length > 0 && (
                    <nav className="flex items-center justify-between">
                        <p className="text-sm tabular-nums text-muted-foreground">
                            {users.last_page > 1
                                ? `${users.from}–${users.to} of ${users.total} users`
                                : `${users.total} user${users.total === 1 ? "" : "s"}`}
                        </p>
                        {users.last_page > 1 && (
                            <div className="flex items-center gap-1">
                                {users.links.map((link, index) => {
                                    const label =
                                        link.label
                                            .replace("&laquo;", "‹")
                                            .replace("&raquo;", "›")
                                            .trim() ?? String(index);

                                    if (link.url === null) {
                                        return (
                                            <span
                                                key={`${label}-${index}`}
                                                className="px-2 text-sm text-muted-foreground"
                                            >
                                                {label}
                                            </span>
                                        );
                                    }

                                    return (
                                        <Link
                                            key={`${label}-${index}`}
                                            href={link.url}
                                            className={cn(
                                                "rounded-md px-2 py-1 text-sm hover:bg-accent",
                                                link.active &&
                                                    "bg-primary text-primary-foreground hover:bg-primary/90",
                                            )}
                                        >
                                            {label}
                                        </Link>
                                    );
                                })}
                            </div>
                        )}
                    </nav>
                )}
            </div>

            <TempPasswordDialog
                tempPassword={tempPassword}
                onClose={clearTempPassword}
            />
        </ModuleLayout>
    );
}

function ResetPasswordMenuItem({
    userId,
    userName,
}: {
    userId: number;
    userName: string;
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);

    return (
        <>
            <DropdownMenuItem
                onSelect={(event) => {
                    event.preventDefault();
                    setConfirmOpen(true);
                }}
            >
                <KeyRound className="size-4" />
                Reset password
            </DropdownMenuItem>

            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Reset password for {userName}?
                        </DialogTitle>
                        <DialogDescription>
                            A new temporary password is issued and shown once
                            after this. Share it with {userName} — the account
                            must change it at next login.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setConfirmOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => {
                                setConfirmOpen(false);
                                router.post(resetPassword.url(userId), {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            Reset password
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
