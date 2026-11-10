import type { ReactNode } from 'react';

import type { Photo } from '@/lib/photos';
import { cn } from '@/lib/utils';

/**
 * A photograph in a panel, with an optional wash over it.
 *
 * This replaced a component that fetched its pictures from a placeholder
 * service, which meant every image on the site was a third-party request
 * that showed a grey rectangle on a bad connection. These are the
 * federation's own photographs, served from this application.
 */
export function PhotoPanel({
    photo,
    className,
    imageClassName,
    sizes = '(min-width: 1024px) 50vw, 100vw',
    overlay = 'none',
    priority = false,
    children,
}: {
    photo: Photo;
    className?: string;
    imageClassName?: string;
    sizes?: string;
    overlay?: 'none' | 'soft' | 'deep' | 'bottom';
    priority?: boolean;
    children?: ReactNode;
}) {
    const washes = {
        none: null,
        soft: 'bg-brand-ink/25',
        deep: 'bg-gradient-to-br from-brand-ink/90 via-brand-deep/70 to-brand-water/30',
        bottom: 'bg-gradient-to-t from-brand-ink via-brand-ink/55 to-transparent',
    } as const;

    return (
        <div
            className={cn(
                'relative isolate overflow-hidden bg-brand-deep',
                className,
            )}
        >
            <img
                src={photo.src}
                srcSet={photo.srcSet}
                sizes={sizes}
                alt={photo.alt}
                loading={priority ? 'eager' : 'lazy'}
                decoding="async"
                style={{ objectPosition: photo.position }}
                className={cn(
                    'absolute inset-0 size-full object-cover',
                    imageClassName,
                )}
            />
            {washes[overlay] && (
                <div
                    aria-hidden="true"
                    className={cn('absolute inset-0', washes[overlay])}
                />
            )}
            {children && <div className="relative">{children}</div>}
        </div>
    );
}
