import { Head, Link } from '@inertiajs/react';
import { CalendarDays, MapPin, Timer } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { ProcessBand } from '@/components/public/process-band';
import { Section } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import results from '@/routes/results';

export default function Results({
    galas,
}: {
    galas: {
        name: string;
        slug: string;
        level: string;
        starts_on: string;
        venue: string | null;
        events_published: number;
        is_live: boolean;
    }[];
}) {
    return (
        <PublicLayout
            heroImage={photos.pages.results}
            figures={[
                { label: 'Galas with results', value: galas.length },
                {
                    label: 'Events published',
                    value: galas.reduce(
                        (total, gala) => total + gala.events_published,
                        0,
                    ),
                },
                {
                    label: 'Swimming now',
                    value: galas.filter((gala) => gala.is_live).length,
                },
            ]}
            title="Results and times"
            description="Results publish as each event is confirmed. Personal bests update automatically, and a time that beats a standing record is flagged for ratification."
        >
            <Head title="Results" />

            <Section>
                {galas.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border p-16 text-center">
                        <Timer className="mx-auto size-8 text-muted-foreground" />
                        <p className="mt-4 font-medium">
                            No results published yet
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Results appear here as soon as the first gala of the
                            season is swum.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-border">
                        {galas.map((gala, index) => (
                            <Link
                                key={gala.slug}
                                href={results.show(gala.slug)}
                                className={`flex flex-wrap items-center justify-between gap-4 bg-card p-5 transition-colors hover:bg-accent/40 ${index > 0 ? 'border-t border-border' : ''}`}
                            >
                                <div>
                                    <span className="flex flex-wrap items-center gap-2">
                                        <Badge variant="secondary">
                                            {gala.level}
                                        </Badge>
                                        {gala.is_live && (
                                            <span className="inline-flex items-center gap-1.5 rounded-full bg-brand-red px-2.5 py-0.5 text-xs font-semibold tracking-wide text-white uppercase">
                                                <span className="relative flex size-1.5">
                                                    <span className="absolute inline-flex size-full animate-ping rounded-full bg-white/80" />
                                                    <span className="relative inline-flex size-1.5 rounded-full bg-white" />
                                                </span>
                                                Live
                                            </span>
                                        )}
                                    </span>
                                    <h2 className="mt-2 font-semibold">
                                        {gala.name}
                                    </h2>
                                    <div className="mt-1.5 flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted-foreground">
                                        <span className="flex items-center gap-1.5">
                                            <CalendarDays className="size-3.5" />
                                            {gala.starts_on}
                                        </span>
                                        {gala.venue && (
                                            <span className="flex items-center gap-1.5">
                                                <MapPin className="size-3.5" />
                                                {gala.venue}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <span className="text-sm text-muted-foreground">
                                    {gala.events_published} events published
                                </span>
                            </Link>
                        ))}
                    </div>
                )}
            </Section>
            <ProcessBand
                title="How a time becomes official"
                lead="Nothing on this page is a stopwatch reading. Every time has been through the same four steps."
                steps={[
                    {
                        title: 'Recorded at the pool',
                        body: 'Timekeepers enter each swim against its entry: a time, or a disqualification with the rule code behind it.',
                    },
                    {
                        title: 'Placed',
                        body: 'Once an event\u2019s times are in, places and points are worked out together. A dead heat shares a place and the next one is skipped.',
                    },
                    {
                        title: 'Released by the referee',
                        body: 'Nothing reaches this site until the referee signs the event off. The club points table counts released events only.',
                    },
                    {
                        title: 'Sent for ratification',
                        body: 'A swim that beats a standing record is flagged automatically, and the record changes only once the authorised body ratifies it.',
                    },
                ]}
                footnote="A protest against a disqualification must reach the referee within thirty minutes of the result being announced."
            />
        </PublicLayout>
    );
}
