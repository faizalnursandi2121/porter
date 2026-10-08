import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import {
    BarChart3,
    ClipboardCheck,
    FolderGit2,
    MapPin,
    Package,
    UserCog,
    Users,
} from 'lucide-react';

import AppLogo from '@/components/app-logo';
import { LauncherHeader } from '@/components/launcher-header';
import type { Auth, NavItem } from '@/types';

type ModuleTile = {
    title: string;
    href: string;
    icon: typeof MapPin;
    roles: string[];
};

// Menu launcher sesuai route baseline ui-spec.md §3; gating role (PRD §4).
const moduleTiles: ModuleTile[] = [
    {
        title: 'Attendance',
        href: '/eos/attendance',
        icon: MapPin,
        roles: ['EOS'],
    },
    {
        title: 'Daily Report',
        href: '/eos/daily-report/current',
        icon: ClipboardCheck,
        roles: ['EOS'],
    },
    {
        title: 'Inventory',
        href: '/eos/inventory/assets',
        icon: Package,
        roles: ['EOS'],
    },
    {
        title: 'Attendance',
        href: '/supervisi/attendance',
        icon: MapPin,
        roles: ['SUPERVISI'],
    },
    {
        title: 'Daily Reports',
        href: '/supervisi/daily-reports',
        icon: ClipboardCheck,
        roles: ['SUPERVISI'],
    },
    {
        title: 'Inventory',
        href: '/supervisi/inventory/assets',
        icon: Package,
        roles: ['SUPERVISI'],
    },
    {
        title: 'Master Data',
        href: '/supervisi/master/sites',
        icon: FolderGit2,
        roles: ['SUPERVISI', 'ADMINISTRATOR'],
    },
    {
        title: 'Assignments',
        href: '/supervisi/master/assignments',
        icon: Users,
        roles: ['SUPERVISI', 'ADMINISTRATOR'],
    },
    {
        title: 'Analytics',
        href: '/manager/analytics/overview',
        icon: BarChart3,
        roles: ['SUPERVISI', 'ADMINISTRATOR'],
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: UserCog,
        roles: ['ADMINISTRATOR'],
    },
];

function greetingForHour(hour: number): string {
    if (hour >= 5 && hour < 12) return 'Good Morning';
    if (hour >= 12 && hour < 17) return 'Good Afternoon';
    if (hour >= 17 && hour < 20) return 'Good Evening';
    return 'Good Night';
}

export default function Dashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth?.user;

    // Read the hour once per render — the greeting does not need to be reactive.
    const greeting = useMemo(() => greetingForHour(new Date().getHours()), []);
    const today = useMemo(
        () =>
            new Intl.DateTimeFormat('en-GB', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            }).format(new Date()),
        [],
    );

    const tiles = useMemo(
        () => moduleTiles.filter((tile) => tile.roles.includes(auth?.role ?? '')),
        [auth?.role],
    );

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex min-h-screen w-full flex-col bg-muted/30">
                <LauncherHeader
                    auth={auth}
                    modules={tiles.map((tile) => ({
                        title: tile.title,
                        href: tile.href,
                        icon: tile.icon,
                    }))}
                    searchItems={tiles.map((tile) => ({
                        title: tile.title,
                        href: tile.href,
                        icon: tile.icon,
                    }))}
                />
                <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-10">
                    <div className="flex flex-col gap-1 pb-8">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {greeting}
                        </h1>
                        {user?.name && (
                            <p className="text-3xl font-semibold tracking-tight">
                                {user.name}
                            </p>
                        )}
                        <p className="text-sm text-muted-foreground">{today}</p>
                    </div>

                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {tiles.map((tile) => (
                            <Link
                                key={tile.title}
                                href={tile.href}
                                className="group flex flex-col items-start gap-4 rounded-xl border bg-card p-5 transition-colors hover:border-foreground/20 hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <span className="flex size-12 items-center justify-center rounded-lg bg-primary text-primary-foreground transition-transform group-hover:scale-105">
                                    <tile.icon className="size-6" />
                                </span>
                                <span className="text-sm font-medium">
                                    {tile.title}
                                </span>
                            </Link>
                        ))}
                    </div>
                </main>
            </div>
        </>
    );
}
