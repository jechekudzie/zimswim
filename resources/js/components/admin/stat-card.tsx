import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * The figures a board opens on, set as one scoreboard rather than a row of
 * floating boxes: divided cells read as a single statement about the
 * organisation, and the rules between them cost nothing in height.
 *
 * Each cell links to the register it counts, because a number on a dashboard
 * is a question half-asked.
 */
export function FigureStrip({ children }: { children: ReactNode }) {
    return (
        <div className="grid grid-cols-2 divide-x divide-y divide-border overflow-hidden rounded-xl border border-border bg-card sm:grid-cols-3 xl:grid-cols-6 xl:divide-y-0">
            {children}
        </div>
    );
}

export function Figure({
    label,
    value,
    icon: Icon,
    hint,
    href,
    tone = 'plain',
}: {
    label: string;
    value: number | string;
    icon: LucideIcon;
    hint?: string;
    href?: string;
    /** `alert` marks a figure that is asking to be worked through. */
    tone?: 'plain' | 'alert';
}) {
    const body = (
        <>
            <div className="flex items-center justify-between gap-2">
                <p className="truncate text-sm text-muted-foreground">
                    {label}
                </p>
                <Icon
                    className={cn(
                        'size-4 shrink-0',
                        tone === 'alert' ? 'text-brand-red' : 'text-primary',
                    )}
                />
            </div>
            <p
                className={cn(
                    'mt-2 text-3xl leading-none font-bold tracking-tight tabular-nums',
                    tone === 'alert' && 'text-brand-red',
                )}
            >
                {value}
            </p>
            <p className="mt-1.5 text-xs text-muted-foreground">
                {hint ?? ' '}
            </p>
        </>
    );

    if (href === undefined) {
        return <div className="p-4 sm:p-5">{body}</div>;
    }

    return (
        <Link
            href={href}
            className="block p-4 transition-colors hover:bg-muted/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:-outline-offset-2 focus-visible:outline-none sm:p-5"
        >
            {body}
        </Link>
    );
}
