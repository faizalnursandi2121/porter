import { Head, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import {
    store as storeSite,
    update as updateSite,
} from '@/actions/App/Http/Controllers/SiteMasterController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';

export type SiteFormData = {
    id?: number;
    name: string;
    address: string;
    latitude: string;
    longitude: string;
    timezone: string;
    primary_provider: string;
    backup_provider: string;
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    site: SiteFormData | null;
};

export const TIMEZONE_OPTIONS = [
    { value: 'Asia/Jakarta', label: 'WIB — Asia/Jakarta' },
    { value: 'Asia/Makassar', label: 'WITA — Asia/Makassar' },
    { value: 'Asia/Jayapura', label: 'WIT — Asia/Jayapura' },
];

const emptyForm: SiteFormData = {
    name: '',
    address: '',
    latitude: '',
    longitude: '',
    timezone: '',
    primary_provider: '',
    backup_provider: '',
};

/**
 * FR-34: create/edit form for site master data. One dialog serves both —
 * `site` present means edit. Providers live on site_connections (PRIMARY
 * and optional BACKUP rows) but are edited here as flat fields.
 */
export default function SiteFormDialog({ open, onOpenChange, site }: Props) {
    const isEdit = site !== null;

    const { data, setData, post, put, processing, errors, clearErrors, reset } =
        useForm<SiteFormData>(emptyForm);

    useEffect(() => {
        if (open) {
            clearErrors();
            setData(site ?? emptyForm);
        }
    }, [open, site, setData, clearErrors]);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (isEdit && site?.id !== undefined) {
            put(updateSite.url(site.id), {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        } else {
            post(storeSite.url(), {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        }
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="grid gap-2">
                <Label htmlFor="site-name">School name</Label>
                <Input
                    id="site-name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                    autoComplete="off"
                    placeholder="SD Sekolah Rakyat Majumapan"
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="site-address">Address</Label>
                <Textarea
                    id="site-address"
                    value={data.address}
                    onChange={(e) => setData('address', e.target.value)}
                    required
                    rows={2}
                    placeholder="Street, village, district, city"
                />
                <InputError message={errors.address} />
            </div>

            <div className="grid grid-cols-2 gap-3">
                <div className="grid gap-2">
                    <Label htmlFor="site-latitude">Latitude</Label>
                    <Input
                        id="site-latitude"
                        type="number"
                        step="any"
                        min="-90"
                        max="90"
                        value={data.latitude}
                        onChange={(e) => setData('latitude', e.target.value)}
                        required
                        placeholder="-4.123456"
                    />
                    <InputError message={errors.latitude} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="site-longitude">Longitude</Label>
                    <Input
                        id="site-longitude"
                        type="number"
                        step="any"
                        min="-180"
                        max="180"
                        value={data.longitude}
                        onChange={(e) => setData('longitude', e.target.value)}
                        required
                        placeholder="120.123456"
                    />
                    <InputError message={errors.longitude} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="site-timezone">Timezone</Label>
                <Select
                    value={data.timezone}
                    onValueChange={(value) => setData('timezone', value)}
                    required
                >
                    <SelectTrigger id="site-timezone" className="w-full">
                        <SelectValue placeholder="Select a timezone" />
                    </SelectTrigger>
                    <SelectContent>
                        {TIMEZONE_OPTIONS.map((tz) => (
                            <SelectItem key={tz.value} value={tz.value}>
                                {tz.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.timezone} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="site-primary-provider">
                    Primary internet provider
                </Label>
                <Input
                    id="site-primary-provider"
                    value={data.primary_provider}
                    onChange={(e) =>
                        setData('primary_provider', e.target.value)
                    }
                    required
                    autoComplete="off"
                    placeholder="Telkombright"
                />
                <InputError message={errors.primary_provider} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="site-backup-provider">
                    Backup internet provider
                </Label>
                <Input
                    id="site-backup-provider"
                    value={data.backup_provider}
                    onChange={(e) =>
                        setData('backup_provider', e.target.value)
                    }
                    autoComplete="off"
                    placeholder="Optional"
                />
                <InputError message={errors.backup_provider} />
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
                    {isEdit ? 'Save changes' : 'Create site'}
                </Button>
            </div>
        </form>
    );
}
