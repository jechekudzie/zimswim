import { Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

export function Pagination<T>({ meta }: { meta: Paginated<T> }) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <nav className="mt-6 flex flex-wrap items-center justify-between gap-4">
            <p className="text-sm text-muted-foreground">
                Showing {meta.from ?? 0} to {meta.to ?? 0} of {meta.total}
            </p>
            <div className="flex flex-wrap gap-1">
                {meta.links.map((link) =>
                    link.url ? (
                        <Link
                            key={link.label}
                            href={link.url}
                            preserveScroll
                            className={cn(
                                'rounded-md border border-border px-3 py-1.5 text-sm transition-colors hover:bg-accent',
                                link.active &&
                                    'border-primary bg-primary text-primary-foreground hover:bg-primary',
                            )}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={link.label}
                            className="rounded-md border border-border/50 px-3 py-1.5 text-sm text-muted-foreground"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
            </div>
        </nav>
    );
}
