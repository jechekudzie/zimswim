import type { ReactNode } from 'react';

import { FlagRule } from '@/components/flag-rule';
import { cn } from '@/lib/utils';

/**
 * The heading every admin screen opens with.
 *
 * The flag rule above the title is the same device the public site uses, so
 * someone moving between the two is plainly in one system. An `eyebrow`
 * names the section when the title alone does not — "Galas" over "National
 * Sprint Gala" — which is what stops a long form losing its place.
 */
export function PageHeader({
    eyebrow,
    title,
    description,
    actions,
    className,
}: {
    eyebrow?: string;
    title: string;
    description?: string;
    actions?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex flex-wrap items-end justify-between gap-x-6 gap-y-4 border-b border-border pb-6',
                className,
            )}
        >
            <div className="min-w-0">
                <FlagRule className="h-1.5 w-16" />
                {eyebrow && (
                    <p className="mt-3 text-xs font-semibold tracking-[0.14em] text-primary uppercase">
                        {eyebrow}
                    </p>
                )}
                <h1
                    className={cn(
                        'text-2xl font-bold tracking-tight text-balance sm:text-3xl',
                        eyebrow ? 'mt-1' : 'mt-3',
                    )}
                >
                    {title}
                </h1>
                {description && (
                    <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {actions && (
                <div className="flex flex-wrap items-center gap-2">
                    {actions}
                </div>
            )}
        </div>
    );
}
