import { Head, Link, router, usePoll } from '@inertiajs/react';
import {
    ArrowLeft,
    Award,
    Pause,
    Play,
    RefreshCw,
    Star,
    Trophy,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import results from '@/routes/results';

type Row = {
    place: number | null;
    swimmer: string;
    club: string;
    time: string;
    is_personal_best: boolean;
    record_flagged: boolean;
};

type EventResult = {
    number: number;
    title: string;
    age_group: string;
    is_relay: boolean;
    published_at: string | null;
    results: Row[];
};

/**
 * The board does not normally wait for this.
 *
 * The websocket tells the page the moment the referee signs an event off, and
 * it reads the new board then, so in the usual case this timer never fires
 * with anything to fetch. It is the backstop for the phone that cannot hold a
 * socket open on the pool's network: a minute is often enough to catch an
 * event before anyone asks, and cheap enough to leave running all day.
 */
const INTERVAL = 60_000;

export default function LiveResults({
    gala,
    isLive,
    progress,
    latest,
    earlier,
    pointsTable,
    refreshedAt,
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
    isLive: boolean;
    progress: { published: number; total: number; percent: number };
    latest: EventResult | null;
    earlier: EventResult[];
    pointsTable: {
        club: string;
        points: number;
        golds: number;
        silvers: number;
        bronzes: number;
    }[];
    refreshedAt: string;
}) {
    const { start, stop, polling } = usePoll(INTERVAL, {
        only: [
            'latest',
            'earlier',
            'pointsTable',
            'progress',
            'isLive',
            'refreshedAt',
        ],
    });

    const paused = useRef(false);

    // Pausing has to stop the pushed updates as well as the poll, or the
    // button would be a lie: a broadcast would redraw the table under someone
    // who stopped it to read a swimmer's time. Returning false from the live
    // event skips the update that was about to be applied.
    useEffect(
        () => router.on('live', () => (paused.current ? false : undefined)),
        [],
    );

    const toggle = useCallback(() => {
        paused.current = polling;

        if (polling) {
            stop();
        } else {
            start();
        }
    }, [polling, start, stop]);

    const [ago, setAgo] = useState('just now');

    // The clock is driven locally so the page still shows how stale it is when
    // the connection drops and the poll stops coming back.
    useEffect(() => {
        const tick = () => {
            const seconds = Math.max(
                0,
                Math.round((Date.now() - Date.parse(refreshedAt)) / 1000),
            );

            setAgo(
                seconds < 10
                    ? 'just now'
                    : seconds < 60
                      ? `${seconds}s ago`
                      : `${Math.floor(seconds / 60)}m ago`,
            );
        };

        tick();
        const timer = window.setInterval(tick, 5000);

        return () => window.clearInterval(timer);
    }, [refreshedAt]);

    return (
        <PublicLayout>
            <Head title={`${gala.name} live results`} />

            <div className="border-b border-border/60 bg-brand-deep text-white">
                <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="-ml-3 text-white/80 hover:bg-white/10 hover:text-white"
                    >
                        <Link href={results.show(gala.slug)}>
                            <ArrowLeft className="size-4" />
                            Full results
                        </Link>
                    </Button>

                    <div className="mt-4 flex flex-wrap items-center gap-3">
                        {isLive ? (
                            <span className="inline-flex items-center gap-2 rounded-full bg-brand-red px-3 py-1 text-xs font-semibold tracking-wide uppercase">
                                <span className="relative flex size-2">
                                    <span className="absolute inline-flex size-full animate-ping rounded-full bg-white/80" />
                                    <span className="relative inline-flex size-2 rounded-full bg-white" />
                                </span>
                                Live
                            </span>
                        ) : (
                            <Badge
                                variant="outline"
                                className="border-white/30 text-white/80"
                            >
                                Not swimming now
                            </Badge>
                        )}
                        <Badge
                            variant="outline"
                            className="border-white/30 text-white/80"
                        >
                            {gala.level}
                        </Badge>
                    </div>

                    <h1 className="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl">
                        {gala.name}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {gala.course}
                        {gala.venue ? ` · ${gala.venue}` : ''} ·{' '}
                        {progress.published} of {progress.total} events
                        published
                    </p>

                    <div className="mt-5 flex flex-wrap items-center gap-4">
                        <div
                            className="h-1.5 w-full max-w-md overflow-hidden rounded-full bg-white/20"
                            role="progressbar"
                            aria-valuenow={progress.percent}
                            aria-valuemin={0}
                            aria-valuemax={100}
                        >
                            <div
                                className="h-full rounded-full bg-brand-gold transition-[width] duration-700"
                                style={{ width: `${progress.percent}%` }}
                            />
                        </div>

                        <div className="flex items-center gap-2 text-xs text-white/70">
                            <RefreshCw
                                className={`size-3.5 ${polling ? 'animate-spin [animation-duration:3s]' : ''}`}
                            />
                            Updated {ago}
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-7 px-2 text-white/80 hover:bg-white/10 hover:text-white"
                                onClick={toggle}
                            >
                                {polling ? (
                                    <>
                                        <Pause className="size-3.5" />
                                        Pause
                                    </>
                                ) : (
                                    <>
                                        <Play className="size-3.5" />
                                        Resume
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div className="space-y-6 lg:col-span-2">
                    {latest === null ? (
                        <div className="rounded-xl border border-dashed border-border p-12 text-center">
                            <p className="font-medium">
                                No results published yet
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Events appear here as the referee signs each one
                                off. This page updates itself.
                            </p>
                        </div>
                    ) : (
                        <EventPanel event={latest} highlighted />
                    )}

                    {earlier.length > 0 && (
                        <>
                            <h2 className="pt-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                Earlier events
                            </h2>
                            {earlier.map((event) => (
                                <EventPanel key={event.number} event={event} />
                            ))}
                        </>
                    )}
                </div>

                <aside className="space-y-6">
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
                                        <span className="flex min-w-0 items-center gap-2">
                                            <span className="w-4 text-right text-xs text-muted-foreground tabular-nums">
                                                {index + 1}
                                            </span>
                                            <span className="truncate">
                                                {row.club}
                                            </span>
                                        </span>
                                        <span className="font-semibold tabular-nums">
                                            {row.points}
                                        </span>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </section>

                    <p className="rounded-xl border border-border bg-brand-surface/60 p-5 text-xs text-muted-foreground">
                        A star marks a personal best. A medal marks a swim
                        proposed as a record, which stands only once ratified. A
                        protest against a disqualification must reach the
                        referee within 30 minutes of the result being announced.
                    </p>
                </aside>
            </div>
        </PublicLayout>
    );
}

function EventPanel({
    event,
    highlighted = false,
}: {
    event: EventResult;
    highlighted?: boolean;
}) {
    return (
        <section
            className={`overflow-hidden rounded-xl border ${
                highlighted
                    ? 'border-primary/40 shadow-sm ring-1 ring-primary/10'
                    : 'border-border'
            }`}
        >
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-border bg-brand-surface/60 px-5 py-3">
                <div>
                    <h3 className="font-semibold">
                        <span className="font-mono text-sm text-muted-foreground">
                            {event.number}
                        </span>{' '}
                        {event.title}
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        {event.age_group}
                        {event.is_relay ? ' · relay' : ''}
                    </p>
                </div>
                {highlighted && <Badge>Latest</Badge>}
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
    );
}
