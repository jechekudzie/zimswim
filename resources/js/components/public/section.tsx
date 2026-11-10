import type { PropsWithChildren, ReactNode } from 'react';

import { FlagRule } from '@/components/flag-rule';
import { Reveal } from '@/components/public/reveal';
import { cn } from '@/lib/utils';

/**
 * A band of the page, on one of four grounds.
 *
 * The public site alternates between them so a long page reads as a sequence
 * of distinct subjects rather than one scroll: the light grounds carry the
 * listings and tables people came to read, and a dark band marks the places
 * where the site explains itself — how a time becomes official, how a club
 * affiliates. `ink` is the logo's own near-black, `deep` the water below it.
 *
 * The dark tones set `.on-dark`, which re-points the theme variables at values
 * that survive a dark ground. Anything inside them — a Badge, a Button, a
 * `text-muted-foreground` paragraph — adapts without being told.
 */
export type SectionTone = 'light' | 'surface' | 'deep' | 'ink';

const grounds: Record<SectionTone, string> = {
    light: 'bg-background',
    surface: 'bg-brand-surface/55',
    deep: 'on-dark bg-brand-deep',
    ink: 'on-dark bg-brand-ink',
};

export function Section({
    tone = 'light',
    className,
    innerClassName,
    children,
}: PropsWithChildren<{
    tone?: SectionTone;
    className?: string;
    innerClassName?: string;
}>) {
    const isDark = tone === 'deep' || tone === 'ink';

    return (
        <section className={cn('relative isolate', grounds[tone], className)}>
            {isDark && (
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(85%_65%_at_50%_0%,var(--brand-water),transparent_72%)] opacity-25"
                />
            )}

            <div
                className={cn(
                    'mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20',
                    innerClassName,
                )}
            >
                {children}
            </div>
        </section>
    );
}

/**
 * The heading a band opens with: the flag rule, the subject, and a line of
 * standfirst where the subject needs one.
 */
export function SectionHeading({
    title,
    lead,
    actions,
    className,
}: {
    title: string;
    lead?: string;
    actions?: ReactNode;
    className?: string;
}) {
    return (
        <Reveal
            className={cn(
                'flex flex-wrap items-end justify-between gap-x-8 gap-y-4',
                className,
            )}
        >
            <div className="max-w-2xl">
                <FlagRule className="h-1.5 w-20" />
                <h2 className="mt-4 text-2xl font-bold tracking-tight text-balance sm:text-3xl">
                    {title}
                </h2>
                {lead && (
                    <p className="mt-3 leading-relaxed text-muted-foreground">
                        {lead}
                    </p>
                )}
            </div>
            {actions}
        </Reveal>
    );
}
