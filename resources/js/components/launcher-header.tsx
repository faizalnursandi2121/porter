import { Link, router } from '@inertiajs/react';
import {
    Bell,
    Check,
    Clock,
    FileCheck2,
    LogOut,
    Monitor,
    Moon,
    Search,
    Settings,
    Sun,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import AppLogo from '@/components/app-logo';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/hooks/use-appearance';
import { useInitials } from '@/hooks/use-initials';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import { Switch } from '@/components/ui/switch';
import type { Auth, NavItem } from '@/types';

type Props = {
    auth: Auth;
    // Item yang bisa dicari lewat command palette (menu launcher).
    searchItems: NavItem[];
};

type NotificationItem = {
    id: string;
    title: string;
    description: string;
    time: string;
    unread: boolean;
};

/**
 * Header launcher: logo (kiri), notifikasi + search + avatar inisial (kanan).
 */
export function LauncherHeader({ auth, searchItems }: Props) {
    const user = auth?.user;
    const getInitials = useInitials();
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const [searchOpen, setSearchOpen] = useState(false);


    // Shortcut Ctrl/Cmd+K membuka command palette.
    useEffect(() => {
        const onKeyDown = (e: KeyboardEvent) => {
            if ((e.key === 'k' || e.key === 'K') && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                setSearchOpen((open) => !open);
            }
        };
        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, []);

    const notifications: NotificationItem[] = [
        {
            id: 'demo-1',
            title: 'No notifications yet',
            description:
                'Report reopened, attachment rejected, and export completed notifications will appear here (FR-15).',
            time: '',
            unread: false,
        },
    ];
    const unreadCount = notifications.filter((n) => n.unread).length;

    const runItem = (item: NavItem) => {
        setSearchOpen(false);
        router.get(item.href);
    };

    return (
        <header className="sticky top-0 z-10 border-b bg-background/95 backdrop-blur">
            <div className="mx-auto flex h-14 w-full max-w-6xl items-center justify-between gap-2 px-4">
                <Link href="/dashboard" className="flex items-center gap-2">
                    <AppLogo />
                </Link>

                <div className="flex items-center gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-9 w-9 cursor-pointer"
                        onClick={() => setSearchOpen(true)}
                        aria-label="Search menu"
                        data-test="search-button"
                    >
                        <Search className="!size-5 opacity-80" />
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="relative h-9 w-9 cursor-pointer"
                                aria-label="Notifications"
                                data-test="notification-button"
                            >
                                <Bell className="!size-5 opacity-80" />
                                {unreadCount > 0 && (
                                    <Badge
                                        variant="destructive"
                                        className="absolute -top-0.5 -right-0.5 h-4 min-w-4 rounded-full px-1 text-[10px] leading-none"
                                    >
                                        {unreadCount}
                                    </Badge>
                                )}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-80">
                            <DropdownMenuLabel>Notifications</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            {notifications.map((n) => (
                                <DropdownMenuItem
                                    key={n.id}
                                    className="flex cursor-pointer flex-col items-start gap-1 py-2"
                                >
                                    <span className="text-sm font-medium">
                                        {n.title}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {n.description}
                                    </span>
                                    {n.time && (
                                        <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                            <Clock className="size-3" />
                                            {n.time}
                                        </span>
                                    )}
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>

                    {user && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="h-9 w-9 rounded-full p-0"
                                    aria-label="Menu pengguna"
                                    data-test="launcher-user-button"
                                >
                                    <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                                        <AvatarImage
                                            src={user.avatar}
                                            alt={user.name}
                                        />
                                        <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {getInitials(user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                className="w-56"
                                align="end"
                                side="bottom"
                            >
                                <DropdownMenuLabel className="p-0 font-normal">
                                    <div className="flex items-center justify-center gap-2 px-1 py-1.5 text-left text-sm">
                                        <div className="grid flex-1 leading-tight">
                                            <span className="truncate font-medium">
                                                {user.name}
                                            </span>
                                            <span className="truncate text-xs text-muted-foreground">
                                                {user.email}
                                            </span>
                                        </div>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link
                                        className="block w-full cursor-pointer"
                                        href={edit()}
                                        prefetch
                                    >
                                        <Settings className="mr-2" />
                                        Settings
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <div className="flex items-center justify-between gap-2 px-2 py-1.5">
                                    {resolvedAppearance === 'dark' ? (
                                        <Moon className="size-4" />
                                    ) : (
                                        <Sun className="size-4" />
                                    )}
                                    <span className="flex-1 pl-2 text-sm">Dark mode</span>
                                    <Switch
                                        checked={resolvedAppearance === 'dark'}
                                        onCheckedChange={(checked) =>
                                            updateAppearance(checked ? 'dark' : 'light')
                                        }
                                        aria-label="Toggle dark mode"
                                        data-test="dark-mode-switch"
                                    />
                                </div>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link
                                        className="block w-full cursor-pointer"
                                        href={logout()}
                                        as="button"
                                        data-test="logout-button"
                                    >
                                        <LogOut className="mr-2" />
                                        Log out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </div>

            <CommandDialog
                open={searchOpen}
                onOpenChange={setSearchOpen}
                description="Search menu or page"
            >
                <CommandInput placeholder="Search menu..." />
                <CommandList>
                    <CommandEmpty>No results found.</CommandEmpty>
                    <CommandGroup heading="Menu">
                        {searchItems.map((item) => (
                            <CommandItem
                                key={item.title}
                                value={item.title}
                                onSelect={() => runItem(item)}
                            >
                                {item.icon && (
                                    <item.icon className="mr-2 size-4" />
                                )}
                                {item.title}
                            </CommandItem>
                        ))}
                    </CommandGroup>
                    <CommandSeparator />
                    <CommandGroup heading="More">
                        <CommandItem
                            value="pengaturan"
                            onSelect={() => {
                                setSearchOpen(false);
                                router.get(edit());
                            }}
                        >
                            <FileCheck2 className="mr-2 size-4" />
                            Profile Settings
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </CommandDialog>
        </header>
    );
}
