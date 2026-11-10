import { cn } from '@/lib/utils';

/**
 * The federation's logo.
 *
 * The artwork supplied is a black wordmark on a transparent ground, which
 * disappears on anything dark, so `tone="light"` serves a knockout copy whose
 * ink is white and whose flag colours are left alone. Both files are built
 * from public/logo.png: trimmed, cleaned of the compression artefacts the
 * original carried, and cut to 560px.
 */
export function BrandLogo({
    tone = 'dark',
    className,
}: {
    tone?: 'dark' | 'light';
    className?: string;
}) {
    return (
        <img
            src={tone === 'light' ? '/brand/logo-light.png' : '/brand/logo.png'}
            width={560}
            height={226}
            alt="Zimbabwe Swimming"
            className={cn('w-auto', className)}
        />
    );
}
