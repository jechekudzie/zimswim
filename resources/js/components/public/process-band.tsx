import type { ReactNode } from 'react';

import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';

/**
 * The dark band each page closes on: how the thing above it actually works.
 *
 * These are genuine sequences — a club affiliates before its swimmers can be
 * entered, a time is placed before it is released — so they are numbered.
 * The band runs on the logo's own black, which is what separates the part of
 * the page you read from the part that explains it.
 */
export function ProcessBand({
    title,
    lead,
    steps,
    footnote,
    action,
}: {
    title: string;
    lead?: string;
    steps: { title: string; body: string }[];
    footnote?: string;
    action?: ReactNode;
}) {
    return (
        <Section tone="ink">
            <SectionHeading title={title} lead={lead} actions={action} />

            <ol className="mt-12 grid gap-x-10 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                {steps.map((step, index) => (
                    <Reveal
                        as="li"
                        key={step.title}
                        delay={index * 110}
                        className="relative"
                    >
                        <span className="tabular block text-sm font-bold text-brand-gold">
                            {String(index + 1).padStart(2, '0')}
                        </span>
                        <span
                            aria-hidden="true"
                            className="mt-3 block h-px w-full bg-white/20"
                        />
                        <h3 className="mt-4 font-semibold text-white">
                            {step.title}
                        </h3>
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {step.body}
                        </p>
                    </Reveal>
                ))}
            </ol>

            {footnote && (
                <p className="mt-12 max-w-3xl border-t border-white/15 pt-6 text-sm text-muted-foreground">
                    {footnote}
                </p>
            )}
        </Section>
    );
}
