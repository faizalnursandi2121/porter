import { usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';
import { useContext } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarContext,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';

/**
 * User menu. Varian `standalone` dipakai di luar SidebarProvider
 * (header launcher dashboard full-page); varian default untuk sidebar.
 */
export function NavUser({ standalone = false }: { standalone?: boolean }) {
    const { auth } = usePage().props;
    const isMobile = useIsMobile();
    // Baca context sidebar secara opsional: null di luar SidebarProvider
    // (varian standalone) tanpa melempar error seperti useSidebar().
    const sidebarContext = useContext(SidebarContext);

    if (!auth.user) {
        return null;
    }

    if (standalone) {
        return (
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <button
                        type="button"
                        className="flex h-10 items-center gap-2 rounded-md px-2 hover:bg-accent data-[state=open]:bg-accent"
                        data-test="launcher-user-button"
                    >
                        <UserInfo user={auth.user} />
                        <ChevronsUpDown className="ml-1 size-4" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    align="end"
                    side="bottom"
                >
                    <UserMenuContent user={auth.user} />
                </DropdownMenuContent>
            </DropdownMenu>
        );
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent"
                            data-test="sidebar-menu-button"
                        >
                            <UserInfo user={auth.user} />
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side={
                            isMobile
                                ? 'bottom'
                                : sidebarContext?.state === 'collapsed'
                                  ? 'left'
                                  : 'bottom'
                        }
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
