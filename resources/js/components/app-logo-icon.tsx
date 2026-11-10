import { cn } from '@/lib/utils';

/**
 * The federation's mark on its own: the swimmer over the flag waves, lifted
 * out of the full logo for the places too tight to carry the wordmark.
 *
 * Two files rather than one tinted SVG, because the mark is four colours and
 * only its ink changes between a light surface and a dark one.
 */
export default function AppLogoIcon({ className }: { className?: string }) {
    return (
        <>
            <img
                src="/brand/mark.png"
                width={256}
                height={256}
                alt=""
                className={cn('object-contain dark:hidden', className)}
            />
            <img
                src="/brand/mark-light.png"
                width={256}
                height={256}
                alt=""
                className={cn('hidden object-contain dark:block', className)}
            />
        </>
    );
}
