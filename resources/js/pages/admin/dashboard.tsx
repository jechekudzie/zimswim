import { Head, Link } from '@inertiajs/react';
import {
    Building2,
    CalendarDays,
    ClipboardCheck,
    Globe,
    Trophy,
    Waves,
} from 'lucide-react';
import type { ReactNode } from 'react';

import {
    BarRows,
    ChartCard,
    Columns,
    Donut,
    ReadinessGrid,
    SERIES,
    StatusStrip,
    type StatusSlice,
} from '@/components/admin/charts';
import { PageHeader } from '@/components/admin/page-header';
import { Figure, FigureStrip } from '@/components/admin/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type { SeasonSummary } from '@/types';

type DashboardProps = {
    season: SeasonSummary | null;
    organizationName: string | null;
    currentMonth: string;
    summary: {
        swimmers: number;
        pendingSwimmers: number;
        clubs: number;
        provinces: number;
        officials: number;
        upcomingGalas: number;
    };
    swimmersByAgeGroup: { age_group: string; count: number }[];
    swimmersByGender: { label: string; value: number }[];
    membersByStatus: StatusSlice[];
    galasByMonth: { label: string; value: number }[];
    officialsByCategory: { label: string; value: number; required: boolean }[];
    clubsByProvince: { label: string; value: number }[];
    awaitingSanction: {
        name: string;
        slug: string;
        host: string;
        starts_on: string;
        level: string;
    }[];
    awaitingRatification: {
        id: number;
        event: string;
        age_group: string;
        holder: string;
        time: string;
        set_on: string | null;
    }[];
    upcoming: {
        name: string;
        slug: string;
        starts_on: string;
        status: string;
        level: string;
        venue: string | null;
    }[];
};

/** A date the way a board reads one, from the plain date the server sends. */
function readableDate(iso: string): string {
    const [year, month, day] = iso.split('-').map(Number);

    return new Date(year, (month ?? 1) - 1, day).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

/**
 * A named stretch of the dashboard.
 *
 * The rule carries the eye across to the next band, which is what stops a
 * long page of cards reading as one undifferentiated wall.
 */
const bandColumns = {
    1: '',
    2: 'lg:grid-cols-2',
    3: 'lg:grid-cols-3',
} as const;

function Band({
    title,
    columns = 3,
    children,
}: {
    title: string;
    columns?: 1 | 2 | 3;
    children: ReactNode;
}) {
    return (
        <section>
            <div className="flex items-center gap-4">
                <h2 className="text-base font-semibold tracking-tight">
                    {title}
                </h2>
                <span aria-hidden="true" className="h-px flex-1 bg-border" />
            </div>
            <div
                className={cn(
                    'mt-4 grid items-start gap-5',
                    bandColumns[columns],
                )}
            >
                {children}
            </div>
        </section>
    );
}

export default function Dashboard({
    season,
    organizationName,
    currentMonth,
    summary,
    swimmersByAgeGroup,
    swimmersByGender,
    membersByStatus,
    galasByMonth,
    officialsByCategory,
    clubsByProvince,
    awaitingSanction,
    awaitingRatification,
    upcoming,
}: DashboardProps) {
    useFlashMessages();

    const ageBands = swimmersByAgeGroup.map((row) => ({
        label: row.age_group,
        value: row.count,
    }));

    // A province with no clubs is worth one line at the foot of the chart,
    // not a labelled row with an empty bar beside it.
    const affiliated = clubsByProvince.filter((row) => row.value > 0);
    const unaffiliated = clubsByProvince.length - affiliated.length;

    const queues = [
        awaitingSanction.length > 0,
        awaitingRatification.length > 0,
        summary.pendingSwimmers > 0,
    ].filter(Boolean).length;

    return (
        <AppLayout breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-8 p-4 sm:p-6">
                <PageHeader
                    title={organizationName ?? 'Dashboard'}
                    description={
                        season
                            ? `${season.name}. Swimmers are counted at the age they reach on the age-up date.`
                            : 'No season is currently open.'
                    }
                    actions={
                        season?.age_up_date ? (
                            <div className="rounded-lg border border-border bg-card px-4 py-2.5 text-right">
                                <p className="text-xs text-muted-foreground">
                                    Age-up date
                                </p>
                                <p className="text-sm font-semibold">
                                    {readableDate(season.age_up_date)}
                                </p>
                            </div>
                        ) : undefined
                    }
                />

                <FigureStrip>
                    <Figure
                        label="Swimmers"
                        value={summary.swimmers}
                        icon={Waves}
                        href={admin.members.index().url}
                        hint={
                            summary.pendingSwimmers > 0
                                ? `${summary.pendingSwimmers} awaiting approval`
                                : 'All registrations active'
                        }
                    />
                    <Figure
                        label="Clubs"
                        value={summary.clubs}
                        icon={Building2}
                        href={admin.organizations.index().url}
                        hint="Affiliated this season"
                    />
                    <Figure
                        label="Provinces"
                        value={summary.provinces}
                        icon={Globe}
                        href={admin.organizations.index().url}
                        hint={
                            unaffiliated > 0
                                ? `${unaffiliated} with no clubs yet`
                                : 'All carrying clubs'
                        }
                    />
                    <Figure
                        label="Officials"
                        value={summary.officials}
                        icon={ClipboardCheck}
                        href={admin.officials.index().url}
                        hint="Currently accredited"
                    />
                    <Figure
                        label="Upcoming galas"
                        value={summary.upcomingGalas}
                        icon={CalendarDays}
                        href={admin.galas.index().url}
                        hint="Still to be swum"
                    />
                    <Figure
                        label="Records to ratify"
                        value={awaitingRatification.length}
                        icon={Trophy}
                        href={admin.records.index().url}
                        tone={
                            awaitingRatification.length > 0 ? 'alert' : 'plain'
                        }
                        hint={
                            awaitingRatification.length > 0
                                ? 'Waiting on the board'
                                : 'Nothing outstanding'
                        }
                    />
                </FigureStrip>

                {queues > 0 ? (
                    <Band
                        title="Needs attention"
                        columns={Math.min(queues, 3) as 1 | 2 | 3}
                    >
                        {awaitingSanction.length > 0 && (
                            <section className="@container rounded-xl border border-brand-gold/50 bg-brand-gold/5 p-5 sm:p-6">
                                <h3 className="font-semibold">
                                    Galas awaiting your sanction
                                </h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    A gala cannot take entries until the board
                                    sanctions it.
                                </p>
                                <ul className="mt-5 grid gap-x-8 gap-y-3 @2xl:grid-cols-2">
                                    {awaitingSanction.map((gala) => (
                                        <li
                                            key={gala.slug}
                                            className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border/70 bg-card px-3 py-2.5"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    {gala.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {gala.host} ·{' '}
                                                    {readableDate(
                                                        gala.starts_on,
                                                    )}
                                                </p>
                                            </div>
                                            <Button asChild size="sm">
                                                <Link
                                                    href={admin.galas.show(
                                                        gala.slug,
                                                    )}
                                                >
                                                    Review
                                                </Link>
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}

                        {awaitingRatification.length > 0 && (
                            <section className="@container rounded-xl border border-border bg-card p-5 sm:p-6">
                                <h3 className="font-semibold">
                                    Records awaiting ratification
                                </h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    A time is not a national record until the
                                    board ratifies it.
                                </p>
                                <ul className="mt-5 grid gap-x-8 gap-y-3 @2xl:grid-cols-2">
                                    {awaitingRatification.map((record) => (
                                        <li
                                            key={record.id}
                                            className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border/70 px-3 py-2.5"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    {record.event} ·{' '}
                                                    {record.age_group}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {record.holder} ·{' '}
                                                    {record.time}
                                                </p>
                                            </div>
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link
                                                    href={admin.records.index()}
                                                >
                                                    Review
                                                </Link>
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}

                        {summary.pendingSwimmers > 0 && (
                            <section className="rounded-xl border border-border bg-card p-5 sm:p-6">
                                <h3 className="font-semibold">
                                    Registrations awaiting approval
                                </h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    A pending swimmer cannot be entered into a
                                    gala.
                                </p>
                                <p className="mt-5 text-3xl font-bold tabular-nums">
                                    {summary.pendingSwimmers}
                                </p>
                                <Button
                                    asChild
                                    size="sm"
                                    variant="outline"
                                    className="mt-4"
                                >
                                    <Link href={admin.members.index()}>
                                        Open the register
                                    </Link>
                                </Button>
                            </section>
                        )}
                    </Band>
                ) : (
                    <p className="rounded-xl border border-dashed border-border px-5 py-4 text-sm text-muted-foreground">
                        Nothing is waiting on you: no galas to sanction, no
                        records to ratify, no registrations to approve.
                    </p>
                )}

                <Band title="The register" columns={1}>
                    <section className="@container rounded-xl border border-border bg-card p-5 sm:p-6">
                        <h3 className="font-semibold">
                            Every swimmer on the books
                        </h3>
                        <p className="mt-1 text-sm text-muted-foreground">
                            By the age band they swim in, the split between
                            girls and boys, and the standing of each
                            registration.
                        </p>

                        <div className="mt-6 grid gap-8 @4xl:grid-cols-[1.6fr_1fr] @4xl:gap-10">
                            <div>
                                <p className="text-sm font-medium">
                                    By age group
                                </p>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Ages taken at the age-up date, which is the
                                    only grouping the handbook recognises.
                                </p>
                                <div className="mt-5">
                                    <BarRows
                                        rows={ageBands}
                                        colour={SERIES[0]}
                                        showShare
                                        empty="No registered swimmers yet."
                                    />
                                </div>
                            </div>

                            {/*
                             * Its own container, so the ring and its key
                             * stack to the column's width rather than the
                             * whole card's.
                             */}
                            <div className="@container @4xl:border-l @4xl:border-border @4xl:pl-10">
                                <p className="text-sm font-medium">
                                    Girls and boys
                                </p>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Active registrations only.
                                </p>
                                <div className="mt-5">
                                    <Donut
                                        slices={swimmersByGender}
                                        centreLabel="Swimmers"
                                        empty="No registered swimmers yet."
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="mt-8 border-t border-border pt-6">
                            <p className="text-sm font-medium">
                                By the standing of each registration
                            </p>
                            <div className="mt-4">
                                <StatusStrip
                                    slices={membersByStatus}
                                    empty="No swimmers registered yet."
                                />
                            </div>
                        </div>
                    </section>
                </Band>

                <Band title="The season">
                    <ChartCard
                        title="Galas across the season"
                        note={
                            season
                                ? `Every gala on the calendar between ${readableDate(season.starts_on ?? '')} and ${readableDate(season.ends_on ?? '')}.`
                                : 'No season is currently open.'
                        }
                        className="lg:col-span-2"
                    >
                        <Columns
                            points={galasByMonth}
                            colour={SERIES[1]}
                            highlight={currentMonth}
                            empty="No galas on the calendar for this season yet."
                        />
                    </ChartCard>

                    <section className="rounded-xl border border-border bg-card p-5 sm:p-6">
                        <h3 className="font-semibold">Next on the calendar</h3>
                        {upcoming.length === 0 ? (
                            <p className="mt-5 text-sm text-muted-foreground">
                                Nothing scheduled.
                            </p>
                        ) : (
                            <ul className="mt-5 divide-y divide-border">
                                {upcoming.map((gala) => (
                                    <li
                                        key={gala.slug}
                                        className="py-3 first:pt-0 last:pb-0"
                                    >
                                        <Link
                                            href={admin.galas.show(gala.slug)}
                                            className="group flex items-start justify-between gap-3"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium group-hover:text-primary">
                                                    {gala.name}
                                                </p>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    {readableDate(
                                                        gala.starts_on,
                                                    )}{' '}
                                                    ·{' '}
                                                    {gala.venue ??
                                                        'Venue to book'}
                                                </p>
                                            </div>
                                            <Badge
                                                variant="secondary"
                                                className="shrink-0 text-xs"
                                            >
                                                {gala.status}
                                            </Badge>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </Band>

                <Band
                    title="Clubs and officials"
                    columns={clubsByProvince.length > 0 ? 2 : 1}
                >
                    {clubsByProvince.length > 0 && (
                        <ChartCard
                            title="Clubs by province"
                            note="Affiliated clubs under each provincial body."
                        >
                            <BarRows
                                rows={affiliated}
                                colour={SERIES[4]}
                                empty="No clubs are affiliated to a province yet."
                            />
                            {unaffiliated > 0 && (
                                <p className="mt-5 border-t border-border pt-4 text-sm text-muted-foreground">
                                    {unaffiliated} of {clubsByProvince.length}{' '}
                                    provinces carry no affiliated club yet.
                                </p>
                            )}
                        </ChartCard>
                    )}

                    <ChartCard
                        title="Officials on the books"
                        note="A gala needs a referee and a starter before it can be run."
                    >
                        <ReadinessGrid roles={officialsByCategory} />
                    </ChartCard>
                </Band>
            </div>
        </AppLayout>
    );
}
