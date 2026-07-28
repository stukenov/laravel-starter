import { Link } from '@inertiajs/react';
import {
    Activity,
    History,
    LayoutGrid,
    ShieldCheck,
    Users,
} from 'lucide-react';
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
import { useCan } from '@/hooks/use-can';
import { home as dashHome } from '@/routes/dash';
import { index as activityIndex } from '@/routes/dash/activity';
import { index as healthIndex } from '@/routes/dash/health';
import { index as rolesIndex } from '@/routes/dash/roles';
import { index as usersIndex } from '@/routes/dash/users';
import type { NavItem } from '@/types';

// Titles are translation keys — NavMain resolves them. A static array
// cannot call a hook. See AGENTS.md.
//
// `ability` is the permission the section needs. A section with none is
// open to anyone who is signed in.
const mainNavItems: (NavItem & { ability?: string })[] = [
    {
        title: 'nav.dashboard',
        href: dashHome(),
        icon: LayoutGrid,
    },
    {
        title: 'users.title',
        href: usersIndex(),
        icon: Users,
        ability: 'users.view',
    },
    {
        title: 'roles.title',
        href: rolesIndex(),
        icon: ShieldCheck,
        ability: 'roles.view',
    },
    {
        title: 'activity.title',
        href: activityIndex(),
        icon: History,
        ability: 'activity.view',
    },
    {
        title: 'health.title',
        href: healthIndex(),
        icon: Activity,
        ability: 'health.view',
    },
];

export function AppSidebar() {
    const can = useCan();

    // Offering a section that answers 403 is the interface lying about
    // what it can do. The policies still decide; this only stops the
    // pointless click.
    const visible = mainNavItems.filter(
        (item) => item.ability === undefined || can(item.ability),
    );

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
                <NavMain items={visible} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
