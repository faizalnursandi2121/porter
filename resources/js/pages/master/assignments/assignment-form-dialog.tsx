import { useForm } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    store as storeAssignment,
    transfer as transferAssignment,
} from '@/actions/App/Http/Controllers/AssignmentController';

export type AssignmentFormMode =
    | { kind: 'create'; userId: number }
    | { kind: 'transfer'; userId: number; fromSiteId: number }
    | null;

type Props = {
    mode: AssignmentFormMode;
    sites: { id: number; name: string }[];
    /** Current active assignment id when transferring; null when creating. */
    activeAssignmentId: number | null;
    onOpenChange: (open: boolean) => void;
};

const today = () => new Date().toISOString().slice(0, 10);

/**
 * FR-3: create (place) or transfer form. Transfer shows the current site's
 * end date + the new placement start; create only asks for site + start.
 */
export default function AssignmentForm({
    mode,
    sites,
    activeAssignmentId,
    onOpenChange,
}: Props) {
    const isTransfer = mode?.kind === 'transfer';

    const { data, setData, post, processing, errors, clearErrors } = useForm<{
        site_id: string;
        ended_at: string;
        started_at: string;
    }>({
        site_id: '',
        ended_at: today(),
        started_at: today(),
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        clearErrors();

        if (mode === null) {
            return;
        }

        if (isTransfer && activeAssignmentId !== null) {
            post(
                transferAssignment.url(activeAssignmentId),
                {
                    site_id: data.site_id,
                    ended_at: data.ended_at,
                    started_at: data.started_at,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => onOpenChange(false),
                },
            );

            return;
        }

        post(
            storeAssignment.url(),
            {
                user_id: mode.userId,
                site_id: data.site_id,
                started_at: data.started_at,
            },
            {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
            },
        );
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <input type="hidden" name="user_id" value={mode?.userId ?? ''} />
            <input type="hidden" name="ended_at" value={data.ended_at} />
            <input type="hidden" name="started_at" value={data.started_at} />

            <div className="grid gap-2">
                <Label htmlFor="assignment-site">
                    {isTransfer ? 'New school' : 'School'}
                </Label>
                <Select
                    value={data.site_id}
                    onValueChange={(value) => setData('site_id', value)}
                    required
                >
                    <SelectTrigger id="assignment-site" className="w-full">
                        <SelectValue placeholder="Select a school" />
                    </SelectTrigger>
                    <SelectContent>
                        {sites
                            .filter(
                                (site) =>
                                    !isTransfer ||
                                    site.id !== mode?.fromSiteId,
                            )
                            .map((site) => (
                                <SelectItem
                                    key={site.id}
                                    value={String(site.id)}
                                >
                                    {site.name}
                                </SelectItem>
                            ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.site_id} />
            </div>

            {isTransfer && (
                <div className="grid gap-2">
                    <Label htmlFor="assignment-ended-at">Ends current on</Label>
                    <Input
                        id="assignment-ended-at"
                        type="date"
                        value={data.ended_at}
                        onChange={(e) => setData('ended_at', e.target.value)}
                        max={today()}
                        required
                    />
                    <InputError message={errors.ended_at} />
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="assignment-started-at">Starts on</Label>
                <Input
                    id="assignment-started-at"
                    type="date"
                    value={data.started_at}
                    onChange={(e) => setData('started_at', e.target.value)}
                    max={today()}
                    required
                />
                <InputError message={errors.started_at} />
            </div>

            <div className="flex justify-end gap-2 pt-2">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={() => onOpenChange(false)}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    {isTransfer ? 'Transfer' : 'Place at school'}
                </Button>
            </div>
        </form>
    );
}
