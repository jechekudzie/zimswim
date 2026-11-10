import { BrandLogo } from '@/components/brand-logo';

/**
 * The sidebar identity: the full wordmark, in whichever ink the sidebar is
 * currently painted for.
 */
export default function AppLogo() {
    return (
        <div className="flex min-w-0 items-center">
            <BrandLogo className="h-9 shrink-0 dark:hidden" />
            <BrandLogo
                tone="light"
                className="hidden h-9 shrink-0 dark:block"
            />
            <span className="sr-only">Zimbabwe Swimming</span>
        </div>
    );
}
