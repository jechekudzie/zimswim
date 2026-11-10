import { cn } from '@/lib/utils';

/**
 * The four-band rule used under section headings across the site.
 */
export function FlagRule({ className }: { className?: string }) {
    return (
        <span
            aria-hidden="true"
            className={cn('flag-rule block h-1 w-16 rounded-full', className)}
        />
    );
}
