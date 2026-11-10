import { Head, Link, router } from '@inertiajs/react';
import { CalendarCheck, Radio, Trophy } from 'lucide-react';
import { Fragment, type ReactNode } from 'react';

import { type HeroFigure } from '@/components/public/page-hero';
import { ProcessBand } from '@/components/public/process-band';
import { Reveal } from '@/components/public/reveal';
import { Section } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import { cn } from '@/lib/utils';
import { calendar } from '@/routes';
import galas from '@/routes/galas';
import results from '@/routes/results';
import type { SeasonSummary } from '@/types';

type CalendarGala = {
    name: string;
    slug: string;
    level: string;
    level_label: string;
    course: string;
    status: string;
    starts_on: string;
    ends_on: string;
    eligible_ages: string | null;
    venue: string | null;
    city: string | null;
    host: string;
    entries_close_at: string | null;
    is_live: boolean;
    has_results: boolean;
};

type LevelFilter = { value: string | null; label: string; count: number };

/**
 * A colour per level, fixed to the level rather than to a row's position, so
 * filtering the calendar never repaints the meets that survive.
 *
 * The colour marks a meet on the season chart and edges its row; the level is
 * always written out beside it, so the colour is a second reading and never
 * the only one. Gold is a fill here and never a text colour - at 1.6:1 on
 * white it cannot carry words.
 */
const levelTints: Record<string, string> = {
    national: 'bg-brand-green',
    provincial: 'bg-brand-deep',
    invitational: 'bg-viz-5',
    club: 'bg-brand-gold',
    international: 'bg-brand-red',
};

const courseLengths: Record<string, string> = {
    LCM: '50m',
    SCM: '25m',
};

/**
 * An ISO date from the server is a calendar day, not an instant. Handing it
 * to `new Date()` reads it as UTC midnight, which shows a reader west of
 * Greenwich the day before, so the parts are placed in local time instead.
 */
function parseDay(iso: string): Date {
    const [year, month, day] = iso.split('-').map(Number);

    return new Date(year, (month ?? 1) - 1, day);
}

function shortMonth(iso: string): string {
    return parseDay(iso).toLocaleDateString('en-GB', { month: 'short' });
}

function mediumDate(iso: string): string {
    return parseDay(iso).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
}

function longDate(iso: string): string {
    return parseDay(iso).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function monthKey(iso: string): string {
    return parseDay(iso).toLocaleDateString('en-GB', {
        month: 'long',
        year: 'numeric',
    });
}

/** Whole days from one calendar day to another; negative once it has passed. */
function daysBetween(fromIso: string, toIso: string): number {
    const day = 24 * 60 * 60 * 1000;

    return Math.round(
        (parseDay(toIso).getTime() - parseDay(fromIso).getTime()) / day,
    );
}

/** The dates a meet runs over, written the way a programme writes them. */
function dateRange(gala: CalendarGala): string {
    const start = parseDay(gala.starts_on);
    const end = parseDay(gala.ends_on);

    if (gala.starts_on === gala.ends_on) {
        return mediumDate(gala.starts_on);
    }

    if (start.getMonth() === end.getMonth()) {
        return `${start.getDate()}–${end.getDate()} ${shortMonth(gala.ends_on)}`;
    }

    return `${mediumDate(gala.starts_on)} – ${mediumDate(gala.ends_on)}`;
}

/**
 * The season on one line.
 *
 * Twelve months of fixtures will not fit as readable blocks, and a list
 * cannot show the shape of a season - the quiet winter, the championship run
 * from January. So the meets are placed on a single axis by date: position
 * carries when, width carries how long, colour carries the level, and the red
 * rule carries today. Nothing here is the only way to reach a meet; the table
 * below holds the same fixtures in full.
 */
function SeasonChart({
    list,
    start,
    end,
    today,
    levels,
}: {
    list: CalendarGala[];
    start: string;
    end: string;
    today: string;
    levels: LevelFilter[];
}) {
    const span = daysBetween(start, end);

    if (span <= 0) {
        return null;
    }

    const at = (iso: string) =>
        Math.min(100, Math.max(0, (daysBetween(start, iso) / span) * 100));

    const months: { iso: string; label: string; isYear: boolean }[] = [];
    const cursor = parseDay(start);
    cursor.setDate(1);

    while (cursor <= parseDay(end)) {
        const iso = `${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}-01`;

        if (daysBetween(start, iso) >= 0) {
            months.push({
                iso,
                label: cursor.toLocaleDateString('en-GB', { month: 'short' }),
                isYear: cursor.getMonth() === 0,
            });
        }

        cursor.setMonth(cursor.getMonth() + 1);
    }

    const inSeason =
        daysBetween(start, today) >= 0 && daysBetween(today, end) >= 0;

    return (
        <figure className="mt-10 hidden md:block">
            <div className="overflow-x-auto pb-2">
                <div className="relative h-28 min-w-[46rem]">
                    {months.map((month) => (
                        <div
                            key={month.iso}
                            className="absolute top-0 bottom-6"
                            style={{ left: `${at(month.iso)}%` }}
                        >
                            <span className="absolute top-0 left-1.5 text-xs font-medium whitespace-nowrap text-muted-foreground">
                                {month.label}
                                {month.isYear && (
                                    <span className="ml-1 text-muted-foreground/70">
                                        {parseDay(month.iso).getFullYear()}
                                    </span>
                                )}
                            </span>
                            <span className="absolute top-5 bottom-0 w-px bg-border" />
                        </div>
                    ))}

                    <div className="absolute inset-x-0 top-[3.2rem] h-px bg-border" />

                    {list.map((gala) => {
                        const left = at(gala.starts_on);
                        const width = at(gala.ends_on) - left;
                        const hasEnded = daysBetween(today, gala.ends_on) < 0;

                        return (
                            <Link
                                key={gala.slug}
                                href={galas.show(gala.slug)}
                                title={`${gala.name} - ${dateRange(gala)}`}
                                aria-label={`${gala.name}, ${gala.level_label}, ${dateRange(gala)}`}
                                style={{
                                    left: `${left}%`,
                                    width: `${width}%`,
                                    minWidth: '0.5rem',
                                }}
                                className={cn(
                                    'absolute top-[2.35rem] h-7 rounded-sm ring-2 ring-brand-surface transition-transform hover:scale-y-110 motion-reduce:hover:scale-y-100',
                                    levelTints[gala.level] ??
                                        levelTints.provincial,
                                    hasEnded && 'opacity-45',
                                )}
                            />
                        );
                    })}

                    {inSeason && (
                        <div
                            className="absolute top-5 bottom-0"
                            style={{ left: `${at(today)}%` }}
                        >
                            <span className="absolute inset-y-0 w-0.5 bg-brand-red" />
                            <span className="absolute bottom-0 left-1.5 text-xs font-semibold whitespace-nowrap text-brand-red">
                                Today
                            </span>
                        </div>
                    )}
                </div>
            </div>

            <figcaption className="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-muted-foreground">
                {levels.map((level) => (
                    <span
                        key={level.label}
                        className="inline-flex items-center gap-2"
                    >
                        <span
                            aria-hidden="true"
                            className={cn(
                                'size-2.5 rounded-xs',
                                level.value === null
                                    ? 'bg-muted-foreground'
                                    : (levelTints[level.value] ??
                                          'bg-muted-foreground'),
                            )}
                        />
                        {level.label}
                    </span>
                ))}
                <span>Faded meets have been swum.</span>
            </figcaption>
        </figure>
    );
}

/**
 * The right-hand cell of a row, carrying whatever the reader has to act on: a
 * meet being swum now, a closing date they are running out of, or the results
 * of one already gone.
 */
function standingFor(gala: CalendarGala, today: string): ReactNode {
    const hasEnded = daysBetween(today, gala.ends_on) < 0;

    if (gala.is_live) {
        return (
            <Link
                href={results.live(gala.slug)}
                className="inline-flex items-center gap-2 rounded-full bg-brand-red px-3 py-1 text-xs font-semibold whitespace-nowrap text-white"
            >
                <Radio className="size-3.5 animate-pulse motion-reduce:animate-none" />
                Live now
            </Link>
        );
    }

    if (hasEnded) {
        return gala.has_results ? (
            <Link
                href={results.show(gala.slug)}
                className="inline-flex items-center gap-1.5 text-sm font-semibold whitespace-nowrap text-primary underline-offset-4 hover:underline"
            >
                <Trophy className="size-4" />
                Results
            </Link>
        ) : (
            <span className="text-sm text-muted-foreground">Swum</span>
        );
    }

    if (gala.entries_close_at === null) {
        return null;
    }

    const daysLeft = daysBetween(today, gala.entries_close_at);

    if (daysLeft < 0) {
        return <span className="text-sm text-muted-foreground">Closed</span>;
    }

    // A deadline months out is a date; a deadline inside the next few weeks is
    // a countdown, because that is the form a club can act on. Inside a
    // fortnight it also takes the gold ground - the one thing in a row that
    // asks for attention.
    if (daysLeft > 21) {
        return (
            <span className="text-sm whitespace-nowrap text-muted-foreground">
                Closes {mediumDate(gala.entries_close_at)}
            </span>
        );
    }

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-sm font-medium whitespace-nowrap',
                daysLeft <= 14
                    ? 'bg-brand-gold/30 text-brand-charcoal'
                    : 'text-muted-foreground',
            )}
        >
            <CalendarCheck className="size-4 shrink-0" />
            {daysLeft === 0
                ? 'Closes today'
                : daysLeft === 1
                  ? 'Closes tomorrow'
                  : `${daysLeft} days left`}
        </span>
    );
}

function FixtureRow({
    gala,
    today,
    isNext,
}: {
    gala: CalendarGala;
    today: string;
    isNext: boolean;
}) {
    const hasEnded = daysBetween(today, gala.ends_on) < 0;
    const tint = levelTints[gala.level] ?? levelTints.provincial;

    return (
        <tr
            className={cn(
                'border-b border-border/70 align-top transition-colors',
                isNext ? 'bg-brand-gold/10' : 'hover:bg-brand-surface/50',
            )}
        >
            <td className="relative hidden py-4 pr-4 pl-5 md:table-cell">
                <span
                    aria-hidden="true"
                    className={cn(
                        'absolute inset-y-0 left-0 w-1',
                        tint,
                        hasEnded && 'opacity-45',
                    )}
                />
                <span
                    className={cn(
                        'text-sm font-semibold whitespace-nowrap tabular-nums',
                        hasEnded && 'text-muted-foreground',
                    )}
                >
                    {dateRange(gala)}
                </span>
            </td>

            <td className="relative py-4 pr-3 pl-5 md:pr-4 md:pl-0">
                <span
                    aria-hidden="true"
                    className={cn(
                        'absolute inset-y-0 left-0 w-1 md:hidden',
                        tint,
                        hasEnded && 'opacity-45',
                    )}
                />
                <p
                    className={cn(
                        'text-sm font-semibold tabular-nums md:hidden',
                        hasEnded && 'text-muted-foreground',
                    )}
                >
                    {dateRange(gala)}
                </p>
                <Link
                    href={galas.show(gala.slug)}
                    className={cn(
                        'font-semibold text-balance hover:text-primary',
                        hasEnded && 'font-medium text-muted-foreground',
                    )}
                >
                    {gala.name}
                </Link>
                {isNext && (
                    <span className="ml-2 rounded-full bg-brand-gold/40 px-2 py-0.5 align-middle text-xs font-semibold whitespace-nowrap text-brand-charcoal">
                        Next up
                    </span>
                )}
                <p className="mt-0.5 text-sm text-muted-foreground">
                    {gala.host}
                    {gala.eligible_ages ? ` · ${gala.eligible_ages}` : ''}
                </p>
                <p className="mt-1 text-sm text-muted-foreground md:hidden">
                    {gala.level_label}
                    {gala.venue ? ` · ${gala.venue}` : ''}
                </p>
            </td>

            <td className="hidden py-4 pr-4 md:table-cell">
                <span className="inline-flex items-center gap-2 text-sm whitespace-nowrap">
                    <span
                        aria-hidden="true"
                        className={cn('size-2 rounded-full', tint)}
                    />
                    {gala.level_label}
                </span>
            </td>

            <td className="hidden py-4 pr-4 text-sm whitespace-nowrap lg:table-cell">
                {gala.course}
                <span className="ml-1 text-muted-foreground">
                    {courseLengths[gala.course] ?? ''}
                </span>
            </td>

            <td className="hidden py-4 pr-4 text-sm text-muted-foreground md:table-cell">
                {gala.venue ?? 'To be confirmed'}
                {gala.city && (
                    <span className="block text-muted-foreground/80">
                        {gala.city}
                    </span>
                )}
            </td>

            <td className="py-4 pr-4 pl-2 text-right md:pr-5 md:pl-4">
                {standingFor(gala, today)}
            </td>
        </tr>
    );
}

export default function Calendar({
    season,
    galas: list,
    levels,
    filters,
    today,
}: {
    season: SeasonSummary | null;
    galas: CalendarGala[];
    levels: LevelFilter[];
    filters: { level: string | null };
    today: string;
}) {
    const upcoming = list.filter(
        (gala) => daysBetween(today, gala.ends_on) >= 0,
    );
    const nextSlug = upcoming[0]?.slug ?? null;
    const total = levels.reduce((sum, level) => sum + level.count, 0);
    const pills: LevelFilter[] = [
        { value: null, label: 'All meets', count: total },
        ...levels,
    ];

    const seasonStart = season?.starts_on ?? list[0]?.starts_on ?? null;
    const seasonEnd = season?.ends_on ?? list.at(-1)?.ends_on ?? null;

    const figures: HeroFigure[] = [
        { label: 'Season', value: season?.name ?? '—' },
        { label: 'Meets on the calendar', value: list.length },
    ];

    if (upcoming[0]) {
        figures.push({
            label: 'Next meet',
            value: mediumDate(upcoming[0].starts_on),
        });
    }

    const goToLevel = (level: string | null) => {
        router.get(calendar().url, level ? { level } : {}, {
            preserveScroll: true,
        });
    };

    let lastMonth = '';

    return (
        <PublicLayout
            heroImage={photos.pages.calendar}
            figures={figures}
            title="Season calendar"
            description="Provincial and national championships. Only meets appearing on the official calendars are recognised by Zimbabwe Swimming, and the calendar is subject to change."
        >
            <Head title="Calendar" />

            <Section tone="surface" innerClassName="max-w-6xl">
                <div className="flex flex-wrap items-center justify-between gap-x-8 gap-y-4">
                    <div className="flex flex-wrap gap-2">
                        {pills.map((level) => {
                            const active =
                                (filters.level ?? null) === level.value;

                            return (
                                <button
                                    key={level.label}
                                    type="button"
                                    aria-pressed={active}
                                    onClick={() => goToLevel(level.value)}
                                    className={cn(
                                        'inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium transition-colors',
                                        active
                                            ? 'border-brand-deep bg-brand-deep text-white'
                                            : 'border-border bg-card text-foreground hover:border-brand-deep/40',
                                    )}
                                >
                                    {level.label}
                                    <span
                                        className={cn(
                                            'tabular-nums',
                                            active
                                                ? 'text-white/70'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {level.count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>

                    {season?.age_up_date && (
                        <p className="max-w-xs text-sm text-muted-foreground">
                            Ages are taken at {longDate(season.age_up_date)},
                            the season&rsquo;s age-up date.
                        </p>
                    )}
                </div>

                {list.length === 0 ? (
                    <p className="mt-10 rounded-xl border border-dashed border-border bg-card p-12 text-center text-muted-foreground">
                        Nothing on the calendar at that level yet.
                    </p>
                ) : (
                    <>
                        {seasonStart && seasonEnd && (
                            <SeasonChart
                                list={list}
                                start={seasonStart}
                                end={seasonEnd}
                                today={today}
                                levels={levels}
                            />
                        )}

                        <Reveal className="mt-10 overflow-hidden rounded-xl border border-border bg-card shadow-xs">
                            <table className="w-full border-collapse text-left">
                                <caption className="sr-only">
                                    Every sanctioned meet on the{' '}
                                    {season?.name ?? 'current'} calendar, in
                                    date order.
                                </caption>
                                <thead>
                                    <tr className="border-b border-border text-xs font-semibold text-muted-foreground">
                                        <th
                                            scope="col"
                                            className="hidden py-3 pr-4 pl-5 md:table-cell"
                                        >
                                            Date
                                        </th>
                                        <th
                                            scope="col"
                                            className="py-3 pr-3 pl-5 md:pr-4 md:pl-0"
                                        >
                                            Meet
                                        </th>
                                        <th
                                            scope="col"
                                            className="hidden py-3 pr-4 md:table-cell"
                                        >
                                            Level
                                        </th>
                                        <th
                                            scope="col"
                                            className="hidden py-3 pr-4 lg:table-cell"
                                        >
                                            Course
                                        </th>
                                        <th
                                            scope="col"
                                            className="hidden py-3 pr-4 md:table-cell"
                                        >
                                            Venue
                                        </th>
                                        <th
                                            scope="col"
                                            className="py-3 pr-4 pl-2 text-right md:pr-5 md:pl-4"
                                        >
                                            Entries
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {list.map((gala) => {
                                        const month = monthKey(gala.starts_on);
                                        const opensMonth = month !== lastMonth;
                                        lastMonth = month;

                                        return (
                                            <Fragment key={gala.slug}>
                                                {opensMonth && (
                                                    <tr className="bg-brand-surface/70">
                                                        <th
                                                            scope="colgroup"
                                                            colSpan={6}
                                                            className="border-y border-border/70 py-2 pr-4 pl-5 text-left text-sm font-semibold"
                                                        >
                                                            {month}
                                                        </th>
                                                    </tr>
                                                )}
                                                <FixtureRow
                                                    gala={gala}
                                                    today={today}
                                                    isNext={
                                                        gala.slug === nextSlug
                                                    }
                                                />
                                            </Fragment>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </Reveal>
                    </>
                )}
            </Section>

            <ProcessBand
                title="Entering a gala"
                lead="Entries are lodged by the club, not by the swimmer or the family."
                steps={[
                    {
                        title: 'Read the programme',
                        body: 'Each gala publishes its events, the age groups they are open to, and whether qualifying times apply.',
                    },
                    {
                        title: 'Check your standing',
                        body: 'The club and its province must both be fully affiliated when entries are lodged. A lapsed province stops every club beneath it.',
                    },
                    {
                        title: 'Lodge by the deadline',
                        body: 'Entries close before the gala. Anything after that is a late entry and needs the organiser to accept it.',
                    },
                    {
                        title: 'Heats and lanes',
                        body: 'The organiser seeds heats and lanes from the entry times and publishes the programme before the first race.',
                    },
                ]}
                footnote="A swimmer must also fall in the right age group at the gala’s age-up date, and a swimmer under eighteen needs a parent or guardian’s consent on file."
            />
        </PublicLayout>
    );
}
