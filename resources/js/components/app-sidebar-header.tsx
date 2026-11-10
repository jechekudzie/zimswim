import { Breadcrumbs } from '@/components/breadcrumbs';
import { HeaderUser } from '@/components/header-user';
import { NotificationBell } from '@/components/notification-bell';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * The bar across the top of every backend screen.
 *
 * It carries where you are on the left, and on the right the two things that
 * belong to you rather than to the page: what the platform has sent you, and
 * who you are signed in as.
 */
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-2 border-b border-border bg-brand-surface/70 px-4 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-14 md:px-6">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="ml-auto flex shrink-0 items-center gap-1">
                <NotificationBell />
                <HeaderUser />
            </div>
        </header>
    );
}
