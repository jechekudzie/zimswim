import { useEffect, useRef, useState, type ReactNode } from 'react';

import { FlagRule } from '@/components/flag-rule';
import type { Photo } from '@/lib/photos';
import { cn } from '@/lib/utils';

export type HeroFigure = {
    label: string;
    value: string | number;
};

/**
 * Does this browser want motion held back?
 */
function usesReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

/**
 * Run a figure up to its value once, on arrival.
 *
 * Only numbers are counted. A date or a name is set straight away, because
 * animating it would be decoration rather than a reading of the figure.
 */
function useCountUp(value: string | number): string | number {
    const [shown, setShown] = useState<string | number>(
        typeof value === 'number' ? 0 : value,
    );
    const frame = useRef(0);

    useEffect(() => {
        if (typeof value !== 'number' || usesReducedMotion() || value === 0) {
            setShown(value);

            return;
        }

        const started = performance.now();
        const duration = 900;

        const step = (now: number) => {
            const progress = Math.min(1, (now - started) / duration);
            // Ease out, so the figure arrives rather than slamming to a stop.
            const eased = 1 - Math.pow(1 - progress, 3);

            setShown(Math.round(value * eased));

            if (progress < 1) {
                frame.current = requestAnimationFrame(step);
            }
        };

        frame.current = requestAnimationFrame(step);

        return () => cancelAnimationFrame(frame.current);
    }, [value]);

    return shown;
}

function Figure({ figure, delay }: { figure: HeroFigure; delay: number }) {
    const value = useCountUp(figure.value);

    return (
        <div
            className="animate-lane-rise"
            style={{ animationDelay: `${delay}ms` }}
        >
            <dd className="tabular text-2xl font-bold text-brand-gold sm:text-3xl">
                {value}
            </dd>
            <dt className="mt-1 text-sm text-white/75">{figure.label}</dt>
        </div>
    );
}

/**
 * The header every public page opens on.
 *
 * The photograph runs the full width and the full height of the band, under
 * the navigation as well, so the page begins with a pool rather than with a
 * bar. Two scrims sit over it: a diagonal wash that holds the text, and a
 * fade into the page ground at the bottom so the band does not end on a
 * hard line. The lane hairlines are the brand device, laid over the water
 * at a strength you notice only once.
 *
 * Figures replace a decorative label: someone landing on the calendar reads
 * how many galas are sanctioned this season before reading any prose.
 */
export function PageHero({
    title,
    description,
    figures = [],
    image,
    actions,
    breadcrumb,
    size = 'page',
    children,
}: {
    title: string;
    description?: string;
    figures?: HeroFigure[];
    image?: Photo;
    actions?: ReactNode;
    breadcrumb?: ReactNode;
    size?: 'page' | 'tall';
    children?: ReactNode;
}) {
    return (
        <section
            className={cn(
                'on-dark lane-field relative isolate flex items-end overflow-hidden text-white',
                size === 'tall'
                    ? 'min-h-[86vh]'
                    : 'min-h-[30rem] lg:min-h-[38rem]',
            )}
        >
            {image && (
                <img
                    src={image.src}
                    srcSet={image.srcSet}
                    sizes="100vw"
                    alt={image.alt}
                    fetchPriority="high"
                    decoding="async"
                    style={{ objectPosition: image.position }}
                    className="animate-hero-drift absolute inset-0 -z-20 size-full object-cover"
                />
            )}

            {/*
             * Two washes rather than one. The vertical one weights the foot of
             * the band so the figures hold, the horizontal one weights the
             * left so the heading does; crossing them keeps the right-hand
             * side of the photograph a photograph instead of a black field.
             */}
            <div
                aria-hidden="true"
                className="absolute inset-0 -z-10 bg-gradient-to-t from-brand-deeper/92 via-brand-deeper/35 to-brand-deeper/15"
            />
            <div
                aria-hidden="true"
                className="absolute inset-0 -z-10 bg-gradient-to-r from-brand-deeper/70 via-brand-deeper/15 to-transparent"
            />
            <div className="relative mx-auto w-full max-w-7xl px-4 pt-28 pb-12 sm:px-6 lg:px-8 lg:pt-36 lg:pb-16">
                <div className="max-w-3xl">
                    {breadcrumb && (
                        <div className="animate-lane-rise mb-5">
                            {breadcrumb}
                        </div>
                    )}

                    <h1
                        className={cn(
                            'animate-lane-rise font-bold tracking-tight text-balance drop-shadow-sm',
                            size === 'tall'
                                ? 'text-4xl sm:text-6xl lg:text-7xl'
                                : 'text-4xl sm:text-5xl lg:text-6xl',
                        )}
                        style={{ animationDelay: '60ms' }}
                    >
                        {title}
                    </h1>

                    <FlagRule className="animate-flag-draw mt-6 h-1.5 w-24 origin-left" />

                    {description && (
                        <p
                            className="animate-lane-rise mt-5 max-w-prose text-base leading-relaxed text-white/85 sm:text-lg"
                            style={{ animationDelay: '140ms' }}
                        >
                            {description}
                        </p>
                    )}

                    {actions && (
                        <div
                            className="animate-lane-rise mt-8 flex flex-wrap gap-3"
                            style={{ animationDelay: '200ms' }}
                        >
                            {actions}
                        </div>
                    )}
                </div>

                {figures.length > 0 && (
                    <dl className="mt-10 grid max-w-3xl grid-cols-2 gap-x-8 gap-y-6 border-t border-white/20 pt-7 sm:grid-cols-3">
                        {figures.map((figure, index) => (
                            <Figure
                                key={figure.label}
                                figure={figure}
                                delay={260 + index * 70}
                            />
                        ))}
                    </dl>
                )}

                {children}
            </div>

            <span
                aria-hidden="true"
                className="flag-rule absolute inset-x-0 bottom-0 h-1"
            />
        </section>
    );
}
