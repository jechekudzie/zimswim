import { Form, Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Check, Pencil, X } from 'lucide-react';

import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';

type GalaDetail = {
    id: number;
    slug: string;
    name: string;
    level_label: string;
    course_label: string;
    status: string;
    status_label: string;
    is_sanctioned: boolean;
    starts_on: string;
    ends_on: string;
    age_up_date: string;
    entries_close_at: string | null;
    entry_fee: string;
    eligible_ages: string | null;
    description: string | null;
    host: string;
    venue: string | null;
    venue_grade: string | null;
    has_suitable_venue: boolean;
    required_venue_grade: string;
    sanctioned_by: string | null;
    sanctioned_at: string | null;
    is_published: boolean;
};

export default function GalaShow({
    gala,
    clashes,
    sessions,
    can,
}: {
    gala: GalaDetail;
    clashes: {
        name: string;
        slug: string;
        starts_on: string;
        ends_on: string;
    }[];
    sessions: {
        name: string;
        held_on: string;
        warmup_at: string | null;
        starts_at: string | null;
        events: {
            number: number;
            title: string;
            age_group: string;
            round: string;
        }[];
    }[];
    can: { update: boolean; sanction: boolean };
}) {
    useFlashMessages();

    const facts = [
        { label: 'Level', value: gala.level_label },
        { label: 'Course', value: gala.course_label },
        { label: 'Host', value: gala.host },
        { label: 'Dates', value: `${gala.starts_on} to ${gala.ends_on}` },
        { label: 'Age taken at', value: gala.age_up_date },
        { label: 'Eligible ages', value: gala.eligible_ages ?? '—' },
        { label: 'Entry fee', value: `USD ${gala.entry_fee}` },
        { label: 'Entries close', value: gala.entries_close_at ?? '—' },
    ];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Galas', href: admin.galas.index().url },
                { title: gala.name, href: admin.galas.show(gala.slug).url },
            ]}
        >
            <Head title={gala.name} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.galas.index()}>
                        <ArrowLeft className="size-4" />
                        Back to galas
                    </Link>
                </Button>

                <PageHeader
                    title={gala.name}
                    description={`${gala.status_label}${gala.sanctioned_by ? ` · sanctioned by ${gala.sanctioned_by} on ${gala.sanctioned_at}` : ''}`}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link
                                    href={admin.galas.entries.index(gala.slug)}
                                >
                                    Entries
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link
                                    href={admin.galas.relays.index(gala.slug)}
                                >
                                    Relays
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={admin.galas.heatSheet(gala.slug)}>
                                    Heat sheet
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link
                                    href={admin.galas.finals.index(gala.slug)}
                                >
                                    Finals
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link
                                    href={admin.galas.results.index(gala.slug)}
                                >
                                    Results
                                </Link>
                            </Button>
                            {can.update && (
                                <Button asChild>
                                    <Link href={admin.galas.edit(gala.slug)}>
                                        <Pencil className="size-4" />
                                        Edit
                                    </Link>
                                </Button>
                            )}
                        </div>
                    }
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">At a glance</h2>
                            <dl className="mt-5 grid gap-4 sm:grid-cols-2">
                                {facts.map((fact) => (
                                    <div key={fact.label}>
                                        <dt className="text-xs text-muted-foreground">
                                            {fact.label}
                                        </dt>
                                        <dd className="mt-0.5 text-sm font-medium">
                                            {fact.value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>

                            {gala.description && (
                                <p className="mt-6 border-t border-border pt-5 text-sm whitespace-pre-line text-muted-foreground">
                                    {gala.description}
                                </p>
                            )}
                        </section>

                        {sessions.length > 0 && (
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">Programme</h2>
                                <div className="mt-5 space-y-5">
                                    {sessions.map((session) => (
                                        <div key={session.name}>
                                            <p className="text-sm font-medium">
                                                {session.name} ·{' '}
                                                {session.held_on}
                                                {session.starts_at
                                                    ? ` · start ${session.starts_at}`
                                                    : ''}
                                            </p>
                                            <ul className="mt-2 grid gap-1 sm:grid-cols-2">
                                                {session.events.map((event) => (
                                                    <li
                                                        key={event.number}
                                                        className="text-sm text-muted-foreground"
                                                    >
                                                        <span className="font-mono text-xs">
                                                            {event.number}
                                                        </span>{' '}
                                                        {event.title}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}
                    </div>

                    <aside className="space-y-6">
                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">Venue check</h2>
                            {gala.venue === null ? (
                                <p className="mt-4 flex items-start gap-2 text-sm text-brand-red">
                                    <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                    No venue booked. A gala cannot be sanctioned
                                    without one.
                                </p>
                            ) : (
                                <>
                                    <p className="mt-4 text-sm font-medium">
                                        {gala.venue}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Graded {gala.venue_grade}; this gala
                                        needs {gala.required_venue_grade}.
                                    </p>
                                    <p
                                        className={`mt-3 flex items-start gap-2 text-sm ${gala.has_suitable_venue ? 'text-brand-green' : 'text-brand-red'}`}
                                    >
                                        {gala.has_suitable_venue ? (
                                            <Check className="mt-0.5 size-4 shrink-0" />
                                        ) : (
                                            <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                        )}
                                        {gala.has_suitable_venue
                                            ? 'The venue is graded for this level.'
                                            : 'The venue is not graded for this level.'}
                                    </p>
                                </>
                            )}
                        </section>

                        {clashes.length > 0 && (
                            <section className="rounded-xl border border-brand-gold/50 bg-brand-gold/10 p-6">
                                <h2 className="flex items-center gap-2 font-semibold">
                                    <AlertTriangle className="size-4" />
                                    Calendar clash
                                </h2>
                                <ul className="mt-3 space-y-2 text-sm">
                                    {clashes.map((clash) => (
                                        <li key={clash.slug}>
                                            <Link
                                                href={admin.galas.show(
                                                    clash.slug,
                                                )}
                                                className="font-medium hover:underline"
                                            >
                                                {clash.name}
                                            </Link>
                                            <span className="block text-xs text-muted-foreground">
                                                {clash.starts_on} –{' '}
                                                {clash.ends_on}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}

                        {can.sanction && !gala.is_sanctioned && (
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">
                                    Board decision
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Sanctioning puts this gala on the public
                                    calendar.
                                </p>

                                <Form
                                    {...admin.galas.sanction.form(gala.slug)}
                                    className="mt-5 space-y-4"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="notes">
                                                    Notes
                                                </Label>
                                                <textarea
                                                    id="notes"
                                                    name="notes"
                                                    rows={3}
                                                    className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                                    placeholder="Any conditions or changes requested"
                                                />
                                            </div>
                                            <InputError
                                                message={errors.decision}
                                            />
                                            <div className="flex gap-2">
                                                <Button
                                                    type="submit"
                                                    name="decision"
                                                    value="sanction"
                                                    disabled={processing}
                                                    className="flex-1"
                                                >
                                                    <Check className="size-4" />
                                                    Sanction
                                                </Button>
                                                <Button
                                                    type="submit"
                                                    name="decision"
                                                    value="reject"
                                                    variant="outline"
                                                    disabled={processing}
                                                >
                                                    <X className="size-4" />
                                                    Request changes
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </section>
                        )}

                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">Visibility</h2>
                            <Badge
                                variant={
                                    gala.is_published ? 'default' : 'outline'
                                }
                                className="mt-3"
                            >
                                {gala.is_published
                                    ? 'On the public calendar'
                                    : 'Not published'}
                            </Badge>
                        </section>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}
