import { Form } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import UserManagementController from '@/actions/App/Http/Controllers/UserManagementController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type RoleOption = {
    code: string;
    label: string;
};

type SiteOption = {
    id: number;
    name: string;
};

type Props = {
    roles: RoleOption[];
    sites: SiteOption[];
};

/**
 * FR-2: create an account. An EOS account requires a school placement at
 * creation time — the site Select only appears (and is mandatory) when the
 * EOS role is chosen. Select values are mirrored into hidden inputs so the
 * Inertia form submission carries them.
 */
export default function CreateUserDialog({ roles, sites }: Props) {
    const [open, setOpen] = useState(false);
    const [roleCode, setRoleCode] = useState('');
    const [siteId, setSiteId] = useState('');

    const isEos = roleCode === 'EOS';

    useEffect(() => {
        if (!isEos) {
            setSiteId('');
        }
    }, [isEos]);

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (!next) {
                    setRoleCode('');
                    setSiteId('');
                }
            }}
        >
            <DialogTrigger asChild>
                <Button data-test="create-user-button">Create account</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create account</DialogTitle>
                    <DialogDescription>
                        The new account receives a temporary password and must
                        change it at first login.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...UserManagementController.store.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    onSuccess={() => {
                        setOpen(false);
                        setRoleCode('');
                        setSiteId('');
                    }}
                    className="space-y-5"
                >
                    {({ processing, errors }) => (
                        <>
                            {/* Radix Select renders no input; mirror the
                                chosen values so FormData carries them. */}
                            <input
                                type="hidden"
                                name="role_code"
                                value={roleCode}
                            />
                            {isEos && (
                                <input
                                    type="hidden"
                                    name="site_id"
                                    value={siteId}
                                />
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoComplete="off"
                                    placeholder="Full name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autoComplete="off"
                                    placeholder="Email address"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role_code">Role</Label>
                                <Select
                                    value={roleCode}
                                    onValueChange={setRoleCode}
                                    required
                                >
                                    <SelectTrigger id="role_code" className="w-full">
                                        <SelectValue placeholder="Select a role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem
                                                key={role.code}
                                                value={role.code}
                                            >
                                                {role.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.role_code} />
                            </div>

                            {isEos && (
                                <div className="grid gap-2">
                                    <Label htmlFor="site_id">
                                        School placement
                                    </Label>
                                    <Select
                                        value={siteId}
                                        onValueChange={setSiteId}
                                        required
                                    >
                                        <SelectTrigger id="site_id" className="w-full">
                                            <SelectValue placeholder="Select a school" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {sites.map((site) => (
                                                <SelectItem
                                                    key={site.id}
                                                    value={String(site.id)}
                                                >
                                                    {site.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-sm text-muted-foreground">
                                        EOS accounts must be placed at a school.
                                    </p>
                                    <InputError message={errors.site_id} />
                                </div>
                            )}

                            <DialogFooter className="gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Create account
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
