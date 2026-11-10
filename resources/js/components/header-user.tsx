import { usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';

/**
 * Who you are signed in as, in the header rather than in the foot of the
 * sidebar.
 *
 * Signing out and reaching your own settings are things you do from anywhere
 * in the backend, so they belong in the bar that is on every screen, next to
 * the bell, instead of at the bottom of a column that collapses to icons.
 */
export function HeaderUser() {
    const { auth, organization } = usePage().props;
    const getInitials = useInitials();

    if (!auth.user) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger className="flex items-center gap-2.5 rounded-lg py-1 pr-2 pl-1 transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none data-[state=open]:bg-muted">
                <Avatar className="size-8 overflow-hidden rounded-full">
                    <AvatarImage src={auth.user.avatar} alt="" />
                    <AvatarFallback className="bg-brand-deep text-xs font-semibold text-white">
                        {getInitials(auth.user.name)}
                    </AvatarFallback>
                </Avatar>
                <span className="hidden text-left leading-tight sm:grid">
                    <span className="truncate text-sm font-medium">
                        {auth.user.name}
                    </span>
                    {organization && (
                        <span className="truncate text-xs text-muted-foreground">
                            {organization.name}
                        </span>
                    )}
                </span>
                <ChevronDown className="size-4 text-muted-foreground" />
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" sideOffset={8} className="w-60">
                <UserMenuContent user={auth.user} />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
