import { router } from '@inertiajs/react';
import { Check, ChevronsUpDown } from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import admin from '@/routes/admin';
import type { OrganizationSummary } from '@/types';

/**
 * Switches the organisation a user is working in. What they may do follows the
 * roles they hold here and above; what they see is this organisation and
 * everything beneath it.
 */
export function OrganizationSwitcher() {
    const { organization, organizations } = usePermissions();
    const { isMobile } = useSidebar();

    if (!organization) {
        return null;
    }

    const grouped = organizations.reduce<Record<string, OrganizationSummary[]>>(
        (acc, item) => {
            (acc[item.type_label] ??= []).push(item);
            return acc;
        },
        {},
    );

    const switchTo = (id: number) => {
        if (id === organization.id) {
            return;
        }

        router.post(
            admin.organization.switch().url,
            { organization_id: id },
            { preserveScroll: true },
        );
    };

    const onlyOne = organizations.length <= 1;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                {onlyOne ? (
                    <SidebarMenuButton size="lg" className="cursor-default">
                        <span className="truncate text-left text-sm font-semibold">
                            {organization.name}
                        </span>
                    </SidebarMenuButton>
                ) : (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <SidebarMenuButton size="lg">
                                <span className="min-w-0 flex-1 truncate text-left text-sm font-semibold">
                                    {organization.name}
                                </span>
                                <ChevronsUpDown className="ml-auto size-4" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            className="w-72 rounded-lg"
                            align="start"
                            side={isMobile ? 'bottom' : 'right'}
                            sideOffset={4}
                        >
                            {Object.entries(grouped).map(([label, items]) => (
                                <div key={label}>
                                    <DropdownMenuLabel className="text-xs text-muted-foreground">
                                        {label}
                                    </DropdownMenuLabel>
                                    {items.map((item) => (
                                        <DropdownMenuItem
                                            key={item.id}
                                            onSelect={() => switchTo(item.id)}
                                            className="gap-2"
                                        >
                                            <span className="truncate">
                                                {item.name}
                                            </span>
                                            {item.id === organization.id && (
                                                <Check className="ml-auto size-4" />
                                            )}
                                        </DropdownMenuItem>
                                    ))}
                                    <DropdownMenuSeparator className="last:hidden" />
                                </div>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
