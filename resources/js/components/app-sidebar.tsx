import {
    BookOpen,
    Building2,
    CalendarDays,
    ClipboardCheck,
    ClipboardList,
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

import { BrandWordmark } from '@/components/brand-wordmark';
import { NavFooter } from '@/components/nav-footer';
import AppLogoIcon from '@/components/app-logo-icon';
import { NavMain, type NavGroup } from '@/components/nav-main';
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

type GuardedItem = NavItem & { permission?: string };

export function AppSidebar() {
    const { can } = usePermissions();

    const groups: { label?: string; items: GuardedItem[] }[] = [
        {
            items: [
                { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            ],
        },
        {
            label: 'Register',
            items: [
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
                    title: 'Applications',
                    href: admin.officialApplications.index(),
                    icon: ClipboardList,
                    permission: 'officials.view',
                },
                {
                    title: 'Organisations',
                    href: admin.organizations.index(),
                    icon: Globe,
                    permission: 'organizations.view',
                },
            ],
        },
        {
            label: 'Competition',
            items: [
                {
                    title: 'Galas',
                    href: admin.galas.index(),
                    icon: CalendarDays,
                    permission: 'galas.view',
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
            ],
        },
        {
            label: 'Academy',
            items: [
                {
                    title: 'My courses',
                    href: academy.index(),
                    icon: GraduationCap,
                },
                {
                    title: 'Course admin',
                    href: admin.academy.index(),
                    icon: BookOpen,
                    permission: 'officials.manage',
                },
            ],
        },
        {
            label: 'Access',
            items: [
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
            ],
        },
    ];

    /*
     * A group whose every screen is closed to this person is dropped
     * entirely, so a club secretary is not shown an "Access" heading with
     * nothing under it.
     */
    const visible: NavGroup[] = groups
        .map((group) => ({
            label: group.label,
            items: group.items.filter(
                (item) => item.permission === undefined || can(item.permission),
            ),
        }))
        .filter((group) => group.items.length > 0);

    const footerItems: NavItem[] = [
        { title: 'Public website', href: home(), icon: Newspaper },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader className="gap-0 p-0">
                {/*
                 * The wordmark is inline SVG, so its ink takes the sidebar's
                 * own foreground and the flag colours are left alone. No
                 * white panel is needed to make a dark logo legible on deep
                 * water.
                 */}
                <Link
                    href={dashboard()}
                    className="flex h-18 items-center border-b border-sidebar-border/60 px-4 text-sidebar-foreground transition-opacity group-data-[collapsible=icon]:h-12 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-2 hover:opacity-90"
                >
                    <BrandWordmark className="h-13 group-data-[collapsible=icon]:hidden" />
                    <AppLogoIcon className="hidden size-7 group-data-[collapsible=icon]:block" />
                    <span className="sr-only">Zimbabwe Swimming</span>
                </Link>

                <div className="p-2">
                    <OrganizationSwitcher />
                </div>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={visible} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerItems} className="mt-auto" />
            </SidebarFooter>
        </Sidebar>
    );
}
