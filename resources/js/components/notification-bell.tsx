import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    CalendarCheck,
    CalendarDays,
    ClipboardCheck,
    ListOrdered,
    Timer,
    Trophy,
    UserCheck,
    Waves,
    type LucideIcon,
} from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import notifications from '@/routes/notifications';
import type { AppNotification, NotificationKind } from '@/types';

/**
 * The icon a notice wears, chosen by what it is about rather than by how
 * urgent it is: a reader scanning the list is looking for the gala or the
 * record, not for a severity.
 */
const kindIcons: Record<NotificationKind, LucideIcon> = {
    accreditation: ClipboardCheck,
    entries: CalendarCheck,
    gala: CalendarDays,
    heats: ListOrdered,
    notice: Bell,
    record: Trophy,
    registration: Waves,
    results: Timer,
};

function Notice({ notice }: { notice: AppNotification }) {
    const Icon = kindIcons[notice.kind] ?? Bell;

    return (
        <Link
            href={notifications.read(notice.id)}
            as="button"
            className={cn(
                'flex w-full gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/70 focus-visible:bg-muted/70 focus-visible:outline-none',
                !notice.read && 'bg-primary/[0.04]',
            )}
        >
            <Icon
                className={cn(
                    'mt-0.5 size-4 shrink-0',
                    notice.read ? 'text-muted-foreground' : 'text-primary',
                )}
            />
            <span className="min-w-0 flex-1">
                <span
                    className={cn(
                        'block text-sm leading-snug text-balance',
                        notice.read ? 'text-foreground' : 'font-semibold',
                    )}
                >
                    {notice.title}
                </span>
                {notice.message && (
                    <span className="mt-0.5 block text-xs leading-relaxed text-muted-foreground">
                        {notice.message}
                    </span>
                )}
                {notice.sent_at && (
                    <span className="mt-1 block text-xs text-muted-foreground/80">
                        {notice.sent_at}
                    </span>
                )}
            </span>
            {!notice.read && (
                <span
                    aria-label="Unread"
                    className="mt-1.5 size-2 shrink-0 rounded-full bg-primary"
                />
            )}
        </Link>
    );
}

/**
 * Everything the platform has sent this person, in the header beside their
 * name.
 *
 * The same notices that go out by email and text are written to the database
 * as they are sent, so the bell is not a second system with its own wording
 * — it is the same notice, still here for somebody who deleted the mail.
 */
export function NotificationBell() {
    const feed = usePage().props.notifications;

    if (!feed) {
        return null;
    }

    const { unread, items } = feed;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className="relative inline-flex size-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none data-[state=open]:bg-muted data-[state=open]:text-foreground"
                aria-label={
                    unread === 0
                        ? 'Notifications'
                        : `Notifications, ${unread} unread`
                }
            >
                <Bell className="size-[1.15rem]" />
                {unread > 0 && (
                    <span className="absolute -top-0.5 -right-0.5 flex min-w-4.5 items-center justify-center rounded-full bg-brand-red px-1 text-[0.625rem] leading-4 font-bold text-white tabular-nums">
                        {unread > 99 ? '99+' : unread}
                    </span>
                )}
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                sideOffset={8}
                className="w-[22rem] overflow-hidden p-0"
            >
                <div className="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
                    <p className="text-sm font-semibold">Notifications</p>
                    {unread > 0 && (
                        <Link
                            href={notifications.readAll()}
                            as="button"
                            preserveScroll
                            className="text-xs font-medium text-primary hover:underline"
                        >
                            Mark all read
                        </Link>
                    )}
                </div>

                {items.length === 0 ? (
                    <div className="px-4 py-10 text-center">
                        <UserCheck className="mx-auto size-6 text-muted-foreground/60" />
                        <p className="mt-3 text-sm font-medium">
                            Nothing waiting
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Sanctions, entry deadlines, heat sheets and ratified
                            records land here.
                        </p>
                    </div>
                ) : (
                    <div className="max-h-[26rem] divide-y divide-border overflow-y-auto">
                        {items.map((notice) => (
                            <Notice key={notice.id} notice={notice} />
                        ))}
                    </div>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
