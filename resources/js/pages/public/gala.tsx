import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Clock, Mail, ShieldCheck } from 'lucide-react';

import { FlagRule } from '@/components/flag-rule';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PageHero } from '@/components/public/page-hero';
import { ProcessBand } from '@/components/public/process-band';
import { Section } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import { calendar } from '@/routes';

type GalaDetail = {
    name: string;
    level_label: string;
    course_label: string;
    status_label: string;
    is_sanctioned: boolean;
    starts_on: string;
    ends_on: string;
    age_up_date: string;
    entries_close_at: string | null;
    entry_corrections_deadline: string | null;
    entry_fee: string;
    late_entry_fee: string;
    appeal_deposit: string;
    eligible_ages: string | null;
    description: string | null;
    entries_email: string | null;
    qualifying_times_apply: boolean;
    host: string;
    sanctioning_body: string | null;
    venue: {
        name: string;
        slug: string;
        city: string | null;
        pool_length: number | null;
        lanes: number | null;
    } | null;
};

type Session = {
    name: string;
    held_on: string;
    warmup_at: string | null;
    starts_at: string | null;
    events: {
        number: number;
        gender: string;
        age_group: string;
        distance: number;
        stroke: string;
        round: string;
    }[];
};

function shortDate(value: string) {
    return new Date(value).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
}

function longDate(value: string) {
    return new Date(value).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export default function GalaPage({
    gala,
    sessions,
}: {
    gala: GalaDetail;
    sessions: Session[];
}) {
    const dateRange =
        gala.starts_on === gala.ends_on
            ? longDate(gala.starts_on)
            : `${shortDate(gala.starts_on)} – ${longDate(gala.ends_on)}`;

    const facts = [
        { label: 'Dates', value: dateRange },
        { label: 'Course', value: gala.course_label },
        { label: 'Eligible ages', value: gala.eligible_ages ?? 'See rules' },
        { label: 'Age taken at', value: longDate(gala.age_up_date) },
        { label: 'Entry fee', value: `USD ${gala.entry_fee}` },
        {
            label: 'Entries close',
            value: gala.entries_close_at
                ? longDate(gala.entries_close_at)
                : 'To be advised',
        },
        {
            label: 'Qualifying times',
            value: gala.qualifying_times_apply ? 'Apply' : 'Do not apply',
        },
        { label: 'Appeal deposit', value: `USD ${gala.appeal_deposit}` },
    ];

    return (
        <PublicLayout overHero>
            <Head title={gala.name} />

            <PageHero
                image={photos.marquees}
                title={gala.name}
                description={`Hosted by ${gala.host}${
                    gala.venue ? ` at ${gala.venue.name}` : ''
                }${gala.venue?.city ? `, ${gala.venue.city}` : ''}.`}
                breadcrumb={
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="-ml-3 text-white/80 hover:bg-white/10 hover:text-white"
                    >
                        <Link href={calendar()}>
                            <ArrowLeft className="size-4" />
                            Back to calendar
                        </Link>
                    </Button>
                }
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge className="bg-white/15 text-white hover:bg-white/20">
                            {gala.level_label}
                        </Badge>
                        <Badge
                            variant="outline"
                            className={
                                gala.is_sanctioned
                                    ? 'gap-1 border-transparent bg-brand-green text-white'
                                    : 'border-white/30 text-white/80'
                            }
                        >
                            {gala.is_sanctioned && (
                                <ShieldCheck className="size-3" />
                            )}
                            {gala.status_label}
                        </Badge>
                    </div>
                }
                figures={[
                    { label: 'Dates', value: dateRange },
                    {
                        label: 'Entries close',
                        value: gala.entries_close_at
                            ? longDate(gala.entries_close_at)
                            : 'To be advised',
                    },
                    {
                        label: 'Events in the programme',
                        value: sessions.reduce(
                            (total, session) => total + session.events.length,
                            0,
                        ),
                    },
                ]}
            />

            <Section innerClassName="grid gap-10 py-14 lg:grid-cols-3 lg:py-16">
                <div className="space-y-10 lg:col-span-2">
                    {gala.description && (
                        <section>
                            <FlagRule />
                            <h2 className="mt-4 text-xl font-bold">
                                Key points
                            </h2>
                            <p className="mt-3 leading-relaxed whitespace-pre-line text-muted-foreground">
                                {gala.description}
                            </p>
                        </section>
                    )}

                    <section>
                        <FlagRule />
                        <h2 className="mt-4 text-xl font-bold">
                            Programme of events
                        </h2>

                        {sessions.length === 0 && (
                            <div className="mt-5 rounded-xl border border-dashed border-border p-10 text-center">
                                <Clock className="mx-auto size-7 text-muted-foreground" />
                                <p className="mt-4 font-medium">
                                    The programme is not published yet
                                </p>
                                <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                                    Sessions, events and age groups appear here
                                    once the organiser confirms them, usually
                                    with the entry pack.
                                    {gala.entries_email
                                        ? ' Until then, the gala secretary can tell you what is planned.'
                                        : ''}
                                </p>
                                {gala.entries_email && (
                                    <a
                                        href={`mailto:${gala.entries_email}`}
                                        className="mt-4 inline-block text-sm font-semibold text-primary hover:underline"
                                    >
                                        {gala.entries_email}
                                    </a>
                                )}
                            </div>
                        )}

                        {sessions.length > 0 && (
                            <div className="mt-5 space-y-6">
                                {sessions.map((session) => (
                                    <div
                                        key={session.name}
                                        className="overflow-hidden rounded-xl border border-border"
                                    >
                                        <div className="flex flex-wrap items-center justify-between gap-2 bg-brand-surface px-5 py-3">
                                            <h3 className="font-semibold">
                                                {session.name}
                                            </h3>
                                            <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                                <Clock className="size-3.5" />
                                                {session.warmup_at
                                                    ? `Warm-up ${session.warmup_at}`
                                                    : ''}
                                                {session.starts_at
                                                    ? ` · Start ${session.starts_at}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <table className="w-full text-sm">
                                            <thead className="border-b border-border text-left text-xs tracking-wide text-muted-foreground uppercase">
                                                <tr>
                                                    <th className="px-5 py-2.5 font-medium">
                                                        Event
                                                    </th>
                                                    <th className="px-5 py-2.5 font-medium">
                                                        Age group
                                                    </th>
                                                    <th className="px-5 py-2.5 font-medium">
                                                        Race
                                                    </th>
                                                    <th className="px-5 py-2.5 font-medium">
                                                        Round
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {session.events.map((event) => (
                                                    <tr
                                                        key={event.number}
                                                        className="border-b border-border/60 last:border-0"
                                                    >
                                                        <td className="px-5 py-2.5 font-medium tabular-nums">
                                                            {event.number}
                                                        </td>
                                                        <td className="px-5 py-2.5 text-muted-foreground">
                                                            {event.gender}{' '}
                                                            {event.age_group}
                                                        </td>
                                                        <td className="px-5 py-2.5">
                                                            {event.distance}m{' '}
                                                            {event.stroke}
                                                        </td>
                                                        <td className="px-5 py-2.5 text-muted-foreground capitalize">
                                                            {event.round}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>

                <aside className="space-y-6">
                    <div className="rounded-xl border border-border bg-card p-6">
                        <h2 className="text-sm font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                            At a glance
                        </h2>
                        <dl className="mt-5 space-y-3.5">
                            {facts.map((fact) => (
                                <div
                                    key={fact.label}
                                    className="flex justify-between gap-4 text-sm"
                                >
                                    <dt className="text-muted-foreground">
                                        {fact.label}
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {fact.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>

                    {gala.venue && (
                        <div className="rounded-xl border border-border bg-card p-6">
                            <h2 className="text-sm font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Venue
                            </h2>
                            <p className="mt-4 font-semibold">
                                {gala.venue.name}
                            </p>
                            {gala.venue.city && (
                                <p className="text-sm text-muted-foreground">
                                    {gala.venue.city}
                                </p>
                            )}
                            <p className="mt-3 text-sm text-muted-foreground">
                                {gala.venue.pool_length
                                    ? `${gala.venue.pool_length}m pool`
                                    : 'Pool length to be confirmed'}
                                {gala.venue.lanes
                                    ? ` · ${gala.venue.lanes} lanes`
                                    : ''}
                            </p>
                        </div>
                    )}

                    {gala.entries_email && (
                        <div className="rounded-xl border border-border bg-card p-6">
                            <h2 className="text-sm font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                Entries
                            </h2>
                            <p className="mt-4 text-sm text-muted-foreground">
                                Entries are submitted through your club or
                                province.
                            </p>
                            <a
                                href={`mailto:${gala.entries_email}`}
                                className="mt-3 flex items-center gap-2 text-sm font-medium text-primary hover:underline"
                            >
                                <Mail className="size-4" />
                                {gala.entries_email}
                            </a>
                            {gala.entry_corrections_deadline && (
                                <p className="mt-4 text-xs text-muted-foreground">
                                    Corrections deadline{' '}
                                    {gala.entry_corrections_deadline}. Late
                                    entries are charged USD{' '}
                                    {gala.late_entry_fee} per swimmer.
                                </p>
                            )}
                        </div>
                    )}

                    {gala.sanctioning_body && (
                        <p className="text-xs text-muted-foreground">
                            Sanctioned by {gala.sanctioning_body}.
                        </p>
                    )}
                </aside>
            </Section>

            <ProcessBand
                title="Entering this gala"
                lead="Entries are lodged by the club, not by the swimmer or the family."
                steps={[
                    {
                        title: 'Read the programme',
                        body: 'Check the events, the age groups they are open to, and whether qualifying times apply.',
                    },
                    {
                        title: 'Check your standing',
                        body: 'The club and its province must both be fully affiliated when entries are lodged.',
                    },
                    {
                        title: 'Lodge by the deadline',
                        body: 'Anything after entries close is a late entry and needs the organiser to accept it.',
                    },
                    {
                        title: 'Heats and lanes',
                        body: 'The organiser seeds heats and lanes from the entry times and publishes them before the first race.',
                    },
                ]}
                footnote="A swimmer must fall in the right age group at the age-up date shown above, and a swimmer under eighteen needs a parent or guardian's consent on file."
            />
        </PublicLayout>
    );
}
