import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { PropsWithChildren } from 'react';

import { Breadcrumbs } from '@/components/breadcrumbs';
import Heading from '@/components/heading';
import { LauncherHeader } from '@/components/launcher-header';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { Auth, BreadcrumbItem } from '@/types';

type Props = PropsWithChildren<{
    title: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
}>;

/**
 * Module layout (ui-design.md §1.1/§1.3): full-page launcher header +
 * Back to Dashboard + breadcrumb + page content. No sidebar — the dashboard
 * launcher is the navigation hub; inside a module, move around via the
 * launcher header.
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
                <Button
                    variant="ghost"
                    size="sm"
                    asChild
                    className="mb-4 -ml-2 text-muted-foreground"
                >
                    <Link href={dashboard()} prefetch>
                        <ArrowLeft className="size-4" />
                        Back to Dashboard
                    </Link>
                </Button>

                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        ...breadcrumbs,
                        { title, href: '' },
                    ]}
                />

                <Heading title={title} description={description} />

                <div className="mt-8">{children}</div>
            </main>
        </div>
    );
}
