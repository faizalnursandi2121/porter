import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import { Breadcrumbs } from '@/components/breadcrumbs';
import Heading from '@/components/heading';
import { LauncherHeader } from '@/components/launcher-header';
import { dashboard } from '@/routes';
import type { Auth, BreadcrumbItem } from '@/types';

type Props = PropsWithChildren<{
    title: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
}>;

/**
 * Module layout (ui-design.md §1.1/§1.3): full-page launcher header +
 * breadcrumb (Dashboard is the return path) + page heading + content.
 * No sidebar, no separate back button — the breadcrumb carries navigation.
 */
export default function ModuleLayout({
    title,
    description,
    breadcrumbs = [],
    children,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <div className="flex min-h-screen w-full flex-col bg-muted/30">
            <LauncherHeader auth={auth} searchItems={[]} />
            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        ...breadcrumbs,
                        { title, href: '' },
                    ]}
                />

                <div className="mt-4">
                    <Heading title={title} description={description} />
                </div>

                <div className="mt-8">{children}</div>
            </main>
        </div>
    );
}
