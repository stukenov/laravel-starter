import { Link } from '@inertiajs/react';
import { History, LayoutGrid, ShieldCheck, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as activityIndex } from '@/routes/dash/activity';
import { index as rolesIndex } from '@/routes/dash/roles';
import { index as usersIndex } from '@/routes/dash/users';
import { home as dashHome } from '@/routes/dash';
import type { NavItem } from '@/types';

// Titles are translation keys — NavMain resolves them. A static array
// cannot call a hook. See AGENTS.md.
const mainNavItems: NavItem[] = [
    {
        title: 'nav.dashboard',
        href: dashHome(),
        icon: LayoutGrid,
    },
    {
        title: 'users.title',
        href: usersIndex(),
        icon: Users,
    },
    {
        title: 'roles.title',
        href: rolesIndex(),
        icon: ShieldCheck,
    },
    {
        title: 'activity.title',
        href: activityIndex(),
        icon: History,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashHome()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
