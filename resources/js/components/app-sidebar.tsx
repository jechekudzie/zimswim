import {
    Building2,
    CalendarDays,
    ClipboardCheck,
    Globe,
    GraduationCap,
    LayoutGrid,
    Newspaper,
    ShieldCheck,
    Trophy,
    Users,
    Waves,
} from 'lucide-react';

import { Link } from '@inertiajs/react';

import { BrandLogo } from '@/components/brand-logo';
import { NavFooter } from '@/components/nav-footer';
import AppLogoIcon from '@/components/app-logo-icon';
import { NavMain } from '@/components/nav-main';
import { OrganizationSwitcher } from '@/components/organization-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { home } from '@/routes';
import { dashboard } from '@/routes';
import academy from '@/routes/academy';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { can } = usePermissions();

    const items: (NavItem & { permission?: string })[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        {
            title: 'Swimmers',
            href: admin.members.index(),
            icon: Waves,
            permission: 'members.view',
        },
        {
            title: 'Officials',
            href: admin.officials.index(),
            icon: ClipboardCheck,
            permission: 'officials.view',
        },
        {
            title: 'Galas',
            href: admin.galas.index(),
            icon: CalendarDays,
            permission: 'galas.view',
        },
        {
            title: 'Academy',
            href: academy.index(),
            icon: GraduationCap,
        },
        {
            title: 'Venues',
            href: admin.venues.index(),
            icon: Building2,
            permission: 'venues.view',
        },
        {
            title: 'Records',
            href: admin.records.index(),
            icon: Trophy,
            permission: 'records.view',
        },
        {
            title: 'Organisations',
            href: admin.organizations.index(),
            icon: Globe,
            permission: 'organizations.view',
        },
        {
            title: 'Users',
            href: admin.users.index(),
            icon: Users,
            permission: 'users.view',
        },
        {
            title: 'Roles',
            href: admin.roles.index(),
            icon: ShieldCheck,
            permission: 'roles.manage',
        },
        {
            title: 'Course admin',
            href: admin.academy.index(),
            icon: GraduationCap,
            permission: 'officials.manage',
        },
    ];

    const visible = items.filter(
        (item) => item.permission === undefined || can(item.permission),
    );

    const footerItems: NavItem[] = [
        { title: 'Public website', href: home(), icon: Newspaper },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader className="gap-0 p-0">
                {/*
                 * The logo is a dark wordmark, and the sidebar is deep water,
                 * so it is given its own white band rather than a knockout
                 * copy: the mark keeps its flag colours, and the band reads
                 * as the masthead the rest of the column hangs from.
                 */}
                <Link
                    href={dashboard()}
                    className="flex h-16 items-center justify-center border-b border-sidebar-border bg-white px-4 group-data-[collapsible=icon]:h-12 group-data-[collapsible=icon]:px-2"
                >
                    <BrandLogo className="h-9 group-data-[collapsible=icon]:hidden" />
                    <AppLogoIcon className="hidden size-7 group-data-[collapsible=icon]:block" />
                    <span className="sr-only">Zimbabwe Swimming</span>
                </Link>

                <div className="p-2">
                    <OrganizationSwitcher />
                </div>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visible} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerItems} className="mt-auto" />
            </SidebarFooter>
        </Sidebar>
    );
}
