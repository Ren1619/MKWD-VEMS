import { Link } from '@inertiajs/react';
import {
    BookOpen,
    CarFront,
    FolderGit2,
    Fuel,
    Gauge,
    LayoutGrid,
    Wrench,
    Route,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { SystemClock } from '@/components/system-clock';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as assignmentsIndex } from '@/routes/assignments';
import { index as driversIndex } from '@/routes/drivers';
import { index as fuelLogsIndex } from '@/routes/fuel-logs';
import { index as maintenanceIndex } from '@/routes/maintenance';
import { index as odometerIndex } from '@/routes/odometer';
import { index as tripsIndex } from '@/routes/trips';
import { index as vehiclesIndex } from '@/routes/vehicles';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Vehicles',
        href: vehiclesIndex(),
        icon: CarFront,
    },
    {
        title: 'Drivers',
        href: driversIndex(),
        icon: Users,
    },
    {
        title: 'Trips / Dispatch',
        href: tripsIndex(),
        icon: Route,
    },
    {
        title: 'Odometer History',
        href: odometerIndex(),
        icon: Gauge,
    },
    {
        title: 'Fuel Logs',
        href: fuelLogsIndex(),
        icon: Fuel,
    },
    {
        title: 'Maintenance',
        href: maintenanceIndex(),
        icon: Wrench,
    },
    {
        title: 'Assignments',
        href: assignmentsIndex(),
        icon: CarFront,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
                <SystemClock />
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
