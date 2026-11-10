import { Link } from '@inertiajs/react';

import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export type NavGroup = {
    /** Left off the first group, which carries the dashboard on its own. */
    label?: string;
    items: NavItem[];
};

/**
 * The backend navigation, grouped by the question each screen answers.
 *
 * A federation administrator does not think in an alphabet of screens: they
 * think about the register, then a competition, then the Academy, then who
 * may do what. Keeping those together means the thing you want next is the
 * thing below the one you are on.
 */
export function NavMain({ groups }: { groups: NavGroup[] }) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <>
            {groups.map((group, index) => (
                <SidebarGroup
                    key={group.label ?? `group-${index}`}
                    className="px-2 py-0"
                >
                    {group.label && (
                        <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
                    )}
                    <SidebarMenu>
                        {group.items.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl(item.href)}
                                    tooltip={{ children: item.title }}
                                >
                                    <Link href={item.href} prefetch>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
