import { Link, usePage } from '@inertiajs/react';

import { FlagRule } from '@/components/flag-rule';
import { home } from '@/routes';

/**
 * The foot of every backend screen.
 *
 * It answers the question a shared login raises on a screen full of other
 * people's swimmers: whose books am I actually looking at, and in which
 * season. The flag rule closes the page the way it opens the public one.
 */
export function AppFooter() {
    const { federation, organization } = usePage().props;

    return (
        <footer className="mt-auto border-t border-border bg-brand-surface/70">
            <FlagRule className="h-0.5 w-full" />
            <div className="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-4 text-xs text-muted-foreground md:px-6">
                <p>
                    {organization ? (
                        <>
                            <span className="font-medium text-foreground">
                                {organization.name}
                            </span>
                            <span> · {organization.type_label}</span>
                        </>
                    ) : (
                        <span className="font-medium text-foreground">
                            {federation.name}
                        </span>
                    )}
                </p>

                <p className="flex items-center gap-4">
                    <Link
                        href={home()}
                        className="transition-colors hover:text-foreground"
                    >
                        Public website
                    </Link>
                    <span>Same Waters · Brighter Futures</span>
                </p>
            </div>
        </footer>
    );
}
