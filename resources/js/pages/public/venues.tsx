import { Head, router } from '@inertiajs/react';
import { Check, Minus } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ProcessBand } from '@/components/public/process-band';
import { Section } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import { venues as venuesRoute } from '@/routes';

type Venue = {
    name: string;
    slug: string;
    city: string | null;
    province: string | null;
    owner_type: string;
    owner_name: string | null;
    pool_length: number | null;
    lanes: number | null;
    starting_blocks: boolean;
    electronic_timing: boolean;
    warmup_pool: boolean;
    grade: string;
    grade_label: string;
    can_host: string;
    condition_notes: string | null;
    notes: string | null;
};

const grades = [
    { value: null, label: 'All venues' },
    { value: 'national', label: 'National' },
    { value: 'provincial', label: 'Provincial' },
    { value: 'club_development', label: 'Club and development' },
];

function Feature({ label, present }: { label: string; present: boolean }) {
    return (
        <span
            className={`flex items-center gap-1.5 text-xs ${present ? 'text-foreground' : 'text-muted-foreground'}`}
        >
            {present ? (
                <Check className="size-3.5 text-brand-green" />
            ) : (
                <Minus className="size-3.5" />
            )}
            {label}
        </span>
    );
}

export default function Venues({
    venues,
    filters,
}: {
    venues: Venue[];
    filters: { grade: string | null };
}) {
    return (
        <PublicLayout
            heroImage={photos.pages.venues}
            figures={[
                { label: 'Registered pools', value: venues.length },
                {
                    label: 'Fifty-metre pools',
                    value: venues.filter((venue) => venue.pool_length === 50)
                        .length,
                },
                {
                    label: 'With electronic timing',
                    value: venues.filter((venue) => venue.electronic_timing)
                        .length,
                },
            ]}
            title="Where Zimbabwe swims"
            description="Every pool that can host a gala, graded by the level of competition it can carry. Venue decisions are made from facts rather than memory."
        >
            <Head title="Venues" />

            <Section>
                <div className="flex flex-wrap gap-2">
                    {grades.map((grade) => {
                        const active = (filters.grade ?? null) === grade.value;

                        return (
                            <Button
                                key={grade.label}
                                size="sm"
                                variant={active ? 'default' : 'outline'}
                                onClick={() =>
                                    router.get(
                                        venuesRoute().url,
                                        grade.value
                                            ? { grade: grade.value }
                                            : {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {grade.label}
                            </Button>
                        );
                    })}
                </div>

                <div className="mt-10 grid gap-6 md:grid-cols-2">
                    {venues.map((venue) => (
                        <article
                            key={venue.slug}
                            className="flex flex-col rounded-xl border border-border bg-card p-6"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h2 className="font-semibold">
                                        {venue.name}
                                    </h2>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {[venue.city, venue.province]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </div>
                                <Badge
                                    variant={
                                        venue.grade === 'national'
                                            ? 'default'
                                            : 'secondary'
                                    }
                                    className="shrink-0"
                                >
                                    {venue.grade_label}
                                </Badge>
                            </div>

                            <p className="mt-4 text-sm text-muted-foreground">
                                Can host: {venue.can_host}
                            </p>

                            <dl className="mt-5 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-border pt-4 text-sm sm:grid-cols-3">
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Pool
                                    </dt>
                                    <dd className="font-medium">
                                        {venue.pool_length
                                            ? `${venue.pool_length}m`
                                            : '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Lanes
                                    </dt>
                                    <dd className="font-medium">
                                        {venue.lanes ?? '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Owner
                                    </dt>
                                    <dd className="font-medium capitalize">
                                        {venue.owner_type}
                                    </dd>
                                </div>
                            </dl>

                            <div className="mt-4 flex flex-wrap gap-x-5 gap-y-2">
                                <Feature
                                    label="Starting blocks"
                                    present={venue.starting_blocks}
                                />
                                <Feature
                                    label="Electronic timing"
                                    present={venue.electronic_timing}
                                />
                                <Feature
                                    label="Warm-up pool"
                                    present={venue.warmup_pool}
                                />
                            </div>

                            {venue.condition_notes && (
                                <p className="mt-4 rounded-md bg-brand-gold/15 px-3 py-2 text-xs text-foreground">
                                    {venue.condition_notes}
                                </p>
                            )}

                            {venue.notes && (
                                <p className="mt-3 text-xs text-muted-foreground">
                                    {venue.notes}
                                </p>
                            )}
                        </article>
                    ))}
                </div>
            </Section>
            <ProcessBand
                title="What the register records about a pool"
                lead="A pool has to be able to carry the gala it is asked to host, and these are the four things that decide it."
                steps={[
                    {
                        title: 'Course length',
                        body: 'Long course is fifty metres, short course twenty-five. Records are held separately on each, so the course is part of the result.',
                    },
                    {
                        title: 'Lanes',
                        body: 'How many lanes the pool has, which sets how many swimmers are in a heat and how a programme is seeded.',
                    },
                    {
                        title: 'Timing',
                        body: 'Whether the pool has electronic timing. A swim put forward as a record needs it.',
                    },
                    {
                        title: 'Grade',
                        body: 'The level of competition the pool is cleared to carry, from a club gala through to a national championship.',
                    },
                ]}
            />
        </PublicLayout>
    );
}
