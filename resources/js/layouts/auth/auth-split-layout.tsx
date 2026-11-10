import { Link } from '@inertiajs/react';
import { CalendarCheck, Timer, Trophy } from 'lucide-react';

import { BrandLogo } from '@/components/brand-logo';
import { FlagRule } from '@/components/flag-rule';
import { photos } from '@/lib/photos';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * The signed-out pages: the form on the left, the sport on the right.
 *
 * The form is given the side a reader starts on and a plain white ground,
 * because the only job here is to get someone through the door. The panel
 * beside it says what is behind the door in the federation's own terms, and
 * it is a panel rather than a background so nothing is ever typed over a
 * photograph.
 */
const capabilities = [
    {
        icon: CalendarCheck,
        title: 'Enter your swimmers',
        body: 'Lodge club entries for any sanctioned gala before the deadline.',
    },
    {
        icon: Timer,
        title: 'Record the racing',
        body: 'Times, places and points as the gala is swum, published when the referee signs off.',
    },
    {
        icon: Trophy,
        title: 'Keep the record straight',
        body: 'Personal bests, national records and the register of officials, in one place.',
    },
];

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-dvh lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div className="flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-16">
                <div className="mx-auto w-full max-w-sm">
                    <Link
                        href={home()}
                        aria-label="Zimbabwe Swimming home"
                        className="inline-block rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:outline-none"
                    >
                        <BrandLogo className="h-12" />
                    </Link>

                    <FlagRule className="mt-7 h-1.5 w-20" />

                    <h1 className="mt-5 text-2xl font-bold tracking-tight text-balance sm:text-3xl">
                        {title}
                    </h1>
                    {description && (
                        <p className="mt-2 text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}

                    <div className="mt-8">{children}</div>

                    <p className="mt-10 text-sm text-muted-foreground">
                        <Link
                            href={home()}
                            className="font-medium hover:text-foreground hover:underline"
                        >
                            Back to the public site
                        </Link>
                    </p>
                </div>
            </div>

            {/*
             * The panel is hidden rather than stacked on a phone: on a narrow
             * screen it would push the form below the fold, and someone
             * signing in on the pool deck wants the form.
             */}
            <aside className="on-dark relative isolate hidden overflow-hidden bg-brand-deeper lg:flex lg:flex-col lg:justify-between">
                <img
                    src={photos.start.src}
                    srcSet={photos.start.srcSet}
                    sizes="50vw"
                    alt=""
                    loading="lazy"
                    decoding="async"
                    style={{ objectPosition: photos.start.position }}
                    className="absolute inset-0 -z-20 size-full object-cover"
                />
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-gradient-to-b from-brand-deeper/80 via-brand-deeper/90 to-brand-deeper"
                />

                <div className="p-12 xl:p-16">
                    <BrandLogo tone="light" className="h-14" />
                </div>

                <div className="p-12 xl:p-16">
                    <h2 className="max-w-md text-3xl font-bold tracking-tight text-balance text-white">
                        One national home for Zimbabwean swimming.
                    </h2>

                    <ul className="mt-10 space-y-7">
                        {capabilities.map((capability) => (
                            <li key={capability.title} className="flex gap-4">
                                <span className="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15">
                                    <capability.icon className="size-5 text-brand-gold" />
                                </span>
                                <div>
                                    <p className="font-semibold text-white">
                                        {capability.title}
                                    </p>
                                    <p className="mt-1 max-w-sm text-sm leading-relaxed text-muted-foreground">
                                        {capability.body}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="border-t border-white/10 px-12 py-7 xl:px-16">
                    <p className="text-sm font-semibold tracking-[0.18em] text-brand-gold uppercase">
                        Same Waters · Brighter Futures
                    </p>
                </div>
            </aside>
        </div>
    );
}
