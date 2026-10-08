import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import { Breadcrumbs } from '@/components/breadcrumbs';
import Heading from '@/components/heading';
import { LauncherHeader } from '@/components/launcher-header';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { Auth, BreadcrumbItem, NavItem } from '@/types';

const subNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
];

/**
 * Settings layout (ui-design.md §1.1/§1.3): full-page launcher header +
 * breadcrumb (Dashboard is the return path) + section heading + sub-nav.
 * No sidebar, no separate back button — the breadcrumb carries navigation.
 */
export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { auth } = usePage<{ auth: Auth }>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Settings', href: '' },
    ];

    return (
        <div className="flex min-h-screen w-full flex-col bg-muted/30">
            <LauncherHeader auth={auth} searchItems={[]} />
            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
                <Breadcrumbs breadcrumbs={breadcrumbs} />

                <div className="mt-4">
                    <Heading
                        title="Settings"
                        description="Manage your profile and account settings"
                    />
                </div>

                <nav
                    className="mt-6 flex gap-1 overflow-x-auto border-b pb-px"
                    aria-label="Settings"
                >
                    {subNavItems.map((item, index) => (
                        <Link
                            key={`${toUrl(item.href)}-${index}`}
                            href={item.href}
                            className={cn(
                                'relative whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium transition-colors hover:text-foreground',
                                isCurrentOrParentUrl(item.href)
                                    ? 'text-foreground'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {item.title}
                            {isCurrentOrParentUrl(item.href) && (
                                <span className="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-foreground" />
                            )}
                        </Link>
                    ))}
                </nav>

                <div className="mt-8 flex justify-center md:justify-start">
                    <div className="w-full md:max-w-2xl">
                        <section className="space-y-12">{children}</section>
                    </div>
                </div>
            </main>
        </div>
    );
}
