import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Award, Radio, Star, Trophy } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PageHero } from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import results from '@/routes/results';

function dateRange(starts: string, ends: string) {
    const format = (value: string, withYear: boolean) =>
        new Date(value).toLocaleDateString('en-GB', {
            day: 'numeric',
            month: 'short',
            ...(withYear ? { year: 'numeric' } : {}),
        });

    return starts === ends
        ? format(starts, true)
        : `${format(starts, false)} – ${format(ends, true)}`;
}

export default function GalaResults({
    gala,
    events,
    pointsTable,
}: {
    gala: {
        name: string;
        slug: string;
        level: string;
        course: string;
        starts_on: string;
        ends_on: string;
        venue: string | null;
    };
    events: {
        number: number;
        title: string;
        age_group: string;
        results: {
            place: number | null;
            swimmer: string;
            club: string;
            time: string;
            is_personal_best: boolean;
            record_flagged: boolean;
        }[];
    }[];
    pointsTable: {
        club: string;
        points: number;
        golds: number;
        silvers: number;
        bronzes: number;
    }[];
}) {
    return (
        <PublicLayout overHero>
            <Head title={`${gala.name} results`} />

            <PageHero
                image={photos.freestyle}
                title={gala.name}
                description={`${dateRange(gala.starts_on, gala.ends_on)}, ${
                    gala.course
                }${gala.venue ? `, at ${gala.venue}` : ''}.`}
                breadcrumb={
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="-ml-3 text-white/80 hover:bg-white/10 hover:text-white"
                    >
                        <Link href={results.index()}>
                            <ArrowLeft className="size-4" />
                            All results
                        </Link>
                    </Button>
                }
                figures={[
                    { label: 'Events published', value: events.length },
                    {
                        label: 'Swims recorded',
                        value: events.reduce(
                            (total, event) => total + event.results.length,
                            0,
                        ),
                    },
                    { label: 'Clubs scoring', value: pointsTable.length },
                ]}
                actions={
                    <>
                        <Button asChild variant="secondary">
                            <Link href={results.live(gala.slug)}>
                                <Radio className="size-4" />
                                Live board
                            </Link>
                        </Button>
                        <Badge className="self-center bg-white/15 text-white hover:bg-white/20">
                            {gala.level}
                        </Badge>
                    </>
                }
            />

            <div className="mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div className="space-y-6 lg:col-span-2">
                    {events.length === 0 ? (
                        <p className="rounded-xl border border-dashed border-border p-12 text-center text-muted-foreground">
                            No events have been published for this gala yet.
                        </p>
                    ) : (
                        events.map((event) => (
                            <section
                                key={event.number}
                                className="overflow-hidden rounded-xl border border-border"
                            >
                                <div className="border-b border-border bg-brand-surface/60 px-5 py-3">
                                    <h2 className="font-semibold">
                                        <span className="font-mono text-sm text-muted-foreground">
                                            {event.number}
                                        </span>{' '}
                                        {event.title}
                                    </h2>
                                    <p className="text-xs text-muted-foreground">
                                        {event.age_group}
                                    </p>
                                </div>
                                <table className="w-full text-sm">
                                    <tbody>
                                        {event.results.map((row, index) => (
                                            <tr
                                                key={`${row.swimmer}-${index}`}
                                                className="border-b border-border/60 bg-card last:border-0"
                                            >
                                                <td className="w-12 px-5 py-2.5 font-semibold tabular-nums">
                                                    {row.place ?? '—'}
                                                </td>
                                                <td className="px-5 py-2.5 font-medium">
                                                    {row.swimmer}
                                                </td>
                                                <td className="px-5 py-2.5 text-muted-foreground">
                                                    {row.club}
                                                </td>
                                                <td className="px-5 py-2.5 text-right font-mono font-semibold tabular-nums">
                                                    <span className="inline-flex items-center gap-1.5">
                                                        {row.is_personal_best && (
                                                            <Star
                                                                className="size-3.5 text-brand-gold"
                                                                aria-label="Personal best"
                                                            />
                                                        )}
                                                        {row.record_flagged && (
                                                            <Award
                                                                className="size-3.5 text-brand-gold"
                                                                aria-label="Record pending ratification"
                                                            />
                                                        )}
                                                        {row.time}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </section>
                        ))
                    )}
                </div>

                <aside>
                    <section className="rounded-xl border border-border bg-card p-6">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <Trophy className="size-4 text-primary" />
                            Club points
                        </h2>
                        {pointsTable.length === 0 ? (
                            <p className="mt-4 text-sm text-muted-foreground">
                                No scored swims yet.
                            </p>
                        ) : (
                            <ol className="mt-4 space-y-3">
                                {pointsTable.map((row, index) => (
                                    <li
                                        key={row.club}
                                        className="flex items-center justify-between gap-3 text-sm"
                                    >
                                        <span className="flex items-center gap-2">
                                            <span className="w-4 text-right text-xs text-muted-foreground tabular-nums">
                                                {index + 1}
                                            </span>
                                            {row.club}
                                        </span>
                                        <span className="font-semibold tabular-nums">
                                            {row.points}
                                        </span>
                                    </li>
                                ))}
                            </ol>
                        )}
                        <p className="mt-5 border-t border-border pt-4 text-xs text-muted-foreground">
                            A star marks a personal best. A medal marks a swim
                            proposed as a record, which stands only once
                            ratified.
                        </p>
                    </section>
                </aside>
            </div>
        </PublicLayout>
    );
}
