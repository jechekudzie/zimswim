import { Form, Head, router } from '@inertiajs/react';
import { Check, Trophy, X } from 'lucide-react';

import { SelectField, TextField } from '@/components/admin/form-field';
import { ExportButton } from '@/components/admin/export-button';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import { records as exportRecords } from '@/routes/admin/exports';
import type { Option } from '@/types';

type RecordRow = {
    id: number;
    scope: string;
    course: string;
    course_label: string;
    gender: string;
    age_group: string;
    event: string;
    time: string;
    holder: string;
    club: string | null;
    venue: string | null;
    gala: string | null;
    set_on: string | null;
    status: string;
    status_label: string;
};

type GalaGroup = {
    key: string;
    gala: string | null;
    held_on: string | null;
    venue: string | null;
    records: RecordRow[];
};

type EventGroup = {
    key: string;
    course: string;
    gender: string;
    age_group: string;
    records: RecordRow[];
};

const genderLabels: Record<string, string> = {
    female: 'Girls and women',
    male: 'Boys and men',
    mixed: 'Mixed',
};

/**
 * One group's worth of records.
 *
 * `showGroup` adds the course, gender and age group to each row: inside a
 * gala they differ line by line, while under an event heading they are
 * already in the heading and repeating them is noise.
 */
function RecordTable({
    records,
    canRatify,
    showGroup = false,
}: {
    records: RecordRow[];
    canRatify: boolean;
    showGroup?: boolean;
}) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[640px] text-sm">
                <thead className="border-b border-border/60 text-left text-xs tracking-wide text-muted-foreground uppercase">
                    <tr>
                        <th className="px-4 py-2.5 font-medium">Event</th>
                        <th className="px-4 py-2.5 font-medium">Time</th>
                        <th className="px-4 py-2.5 font-medium">Holder</th>
                        {!showGroup && (
                            <th className="px-4 py-2.5 font-medium">Set at</th>
                        )}
                        <th className="px-4 py-2.5" />
                    </tr>
                </thead>
                <tbody>
                    {records.map((record) => (
                        <tr
                            key={record.id}
                            className="border-b border-border/40 last:border-0"
                        >
                            <td className="px-4 py-3">
                                <p className="font-medium">{record.event}</p>
                                {showGroup && (
                                    <p className="text-xs text-muted-foreground">
                                        {record.age_group} ·{' '}
                                        {record.course_label} · {record.gender}
                                    </p>
                                )}
                            </td>
                            <td className="px-4 py-3 font-mono font-semibold tabular-nums">
                                {record.time}
                            </td>
                            <td className="px-4 py-3">
                                <p>{record.holder}</p>
                                {record.club && (
                                    <p className="text-xs text-muted-foreground">
                                        {record.club}
                                    </p>
                                )}
                            </td>
                            {!showGroup && (
                                <td className="px-4 py-3 text-xs text-muted-foreground">
                                    {record.gala ?? record.venue ?? '—'}
                                    {record.set_on && (
                                        <span className="block tabular-nums">
                                            {record.set_on}
                                        </span>
                                    )}
                                </td>
                            )}
                            <td className="px-4 py-3">
                                <div className="flex items-center justify-end gap-2">
                                    {record.status !== 'ratified' && (
                                        <Badge
                                            variant="outline"
                                            className={
                                                record.status === 'pending'
                                                    ? 'border-brand-gold/60'
                                                    : 'text-muted-foreground'
                                            }
                                        >
                                            {record.status_label}
                                        </Badge>
                                    )}

                                    {canRatify &&
                                        record.status === 'pending' && (
                                            <>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    aria-label={`Ratify ${record.event} for ${record.holder}`}
                                                    onClick={() =>
                                                        router.post(
                                                            admin.records.ratify(
                                                                record.id,
                                                            ).url,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <Check className="size-4 text-brand-green" />
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    aria-label={`Reject ${record.event} for ${record.holder}`}
                                                    onClick={() =>
                                                        router.post(
                                                            admin.records.reject(
                                                                record.id,
                                                            ).url,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <X className="size-4 text-brand-red" />
                                                </Button>
                                            </>
                                        )}
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default function RecordsIndex({
    pending,
    standing,
    total,
    filters,
    statuses,
    courses,
    strokes,
    can,
}: {
    pending: GalaGroup[];
    standing: EventGroup[];
    total: number;
    filters: {
        status: string | null;
        course: string | null;
        gender: string | null;
    };
    statuses: Option[];
    courses: Option[];
    strokes: Option[];
    can: { ratify: boolean };
}) {
    useFlashMessages();

    function apply(next: Record<string, string | null>) {
        router.get(
            admin.records.index().url,
            { ...filters, ...next },
            { preserveScroll: true },
        );
    }

    const waiting = pending.reduce(
        (count, group) => count + group.records.length,
        0,
    );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Records', href: admin.records.index().url },
            ]}
        >
            <Head title="Records" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Records"
                    description="A record changes only after the authorised body ratifies it. Ratifying a record supersedes the one it beats."
                    actions={
                        <ExportButton
                            url={exportRecords().url}
                            params={{
                                status: filters.status,
                                course: filters.course,
                            }}
                        />
                    }
                />

                <div className="flex flex-wrap gap-4">
                    <div className="flex flex-wrap gap-1.5">
                        <Button
                            size="sm"
                            variant={filters.status ? 'outline' : 'default'}
                            onClick={() => apply({ status: null })}
                        >
                            All
                        </Button>
                        {statuses.map((status) => (
                            <Button
                                key={status.value}
                                size="sm"
                                variant={
                                    filters.status === status.value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => apply({ status: status.value })}
                            >
                                {status.label}
                            </Button>
                        ))}
                    </div>
                    <div className="flex flex-wrap gap-1.5">
                        {courses.map((course) => (
                            <Button
                                key={course.value}
                                size="sm"
                                variant={
                                    filters.course === course.value
                                        ? 'secondary'
                                        : 'ghost'
                                }
                                onClick={() =>
                                    apply({
                                        course:
                                            filters.course === course.value
                                                ? null
                                                : course.value,
                                    })
                                }
                            >
                                {course.label}
                            </Button>
                        ))}
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-3">
                    <div className="flex flex-col gap-8 xl:col-span-2">
                        {total === 0 ? (
                            <div className="rounded-xl border border-dashed border-border p-14 text-center">
                                <Trophy className="mx-auto size-8 text-muted-foreground" />
                                <p className="mt-4 font-medium">
                                    No records yet
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Load the historic lists or let gala results
                                    propose new records.
                                </p>
                            </div>
                        ) : (
                            <>
                                {/*
                                 * Proposals first, in the batches they
                                 * arrived in. A gala produces a handful of
                                 * record swims in one afternoon and the
                                 * board works through them together.
                                 */}
                                {pending.length > 0 && (
                                    <section>
                                        <h2 className="flex flex-wrap items-baseline gap-x-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                            Awaiting ratification
                                            <span className="text-xs font-medium normal-case">
                                                {waiting}{' '}
                                                {waiting === 1
                                                    ? 'time'
                                                    : 'times'}{' '}
                                                across {pending.length}{' '}
                                                {pending.length === 1
                                                    ? 'meet'
                                                    : 'meets'}
                                            </span>
                                        </h2>

                                        <div className="mt-4 flex flex-col gap-5">
                                            {pending.map((group) => (
                                                <article
                                                    key={group.key}
                                                    className="overflow-hidden rounded-xl border border-brand-gold/40 bg-card"
                                                >
                                                    <header className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-border/60 bg-brand-gold/5 px-4 py-3">
                                                        <h3 className="font-semibold">
                                                            {group.gala ??
                                                                'Entered from the archive'}
                                                        </h3>
                                                        <p className="text-xs text-muted-foreground">
                                                            {[
                                                                group.venue,
                                                                group.held_on,
                                                            ]
                                                                .filter(Boolean)
                                                                .join(' · ') ||
                                                                'No meet recorded'}
                                                        </p>
                                                    </header>

                                                    <RecordTable
                                                        records={group.records}
                                                        canRatify={can.ratify}
                                                        showGroup
                                                    />
                                                </article>
                                            ))}
                                        </div>
                                    </section>
                                )}

                                {/*
                                 * Then the standing list, in the shape a
                                 * records table is actually printed: course,
                                 * gender, age group, events in order.
                                 */}
                                {standing.length > 0 && (
                                    <section>
                                        <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                            The standing list
                                        </h2>

                                        <div className="mt-4 flex flex-col gap-5">
                                            {standing.map((group) => (
                                                <article
                                                    key={group.key}
                                                    className="overflow-hidden rounded-xl border border-border bg-card"
                                                >
                                                    <header className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-border/60 bg-muted/40 px-4 py-3">
                                                        <h3 className="font-semibold">
                                                            {
                                                                genderLabels[
                                                                    group.gender
                                                                ]
                                                            }{' '}
                                                            · {group.age_group}
                                                        </h3>
                                                        <p className="text-xs text-muted-foreground">
                                                            {group.course}
                                                        </p>
                                                    </header>

                                                    <RecordTable
                                                        records={group.records}
                                                        canRatify={can.ratify}
                                                    />
                                                </article>
                                            ))}
                                        </div>
                                    </section>
                                )}
                            </>
                        )}
                    </div>

                    {can.ratify && (
                        <section className="h-fit rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">Add a record</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Submitted records wait for ratification before
                                they appear publicly.
                            </p>

                            <Form
                                {...admin.records.store.form()}
                                className="mt-5 space-y-4"
                                resetOnSuccess
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <SelectField
                                            label="Scope"
                                            name="scope"
                                            options={[
                                                {
                                                    value: 'national',
                                                    label: 'National',
                                                },
                                                {
                                                    value: 'provincial',
                                                    label: 'Provincial',
                                                },
                                            ]}
                                            defaultValue="national"
                                            error={errors.scope}
                                        />
                                        <SelectField
                                            label="Course"
                                            name="course"
                                            options={courses}
                                            defaultValue="LCM"
                                            error={errors.course}
                                        />
                                        <SelectField
                                            label="Gender"
                                            name="gender"
                                            options={[
                                                {
                                                    value: 'female',
                                                    label: 'Girls',
                                                },
                                                {
                                                    value: 'male',
                                                    label: 'Boys',
                                                },
                                                {
                                                    value: 'mixed',
                                                    label: 'Mixed',
                                                },
                                            ]}
                                            defaultValue="female"
                                            error={errors.gender}
                                        />
                                        <TextField
                                            label="Age group"
                                            name="age_group"
                                            placeholder="e.g. 11/12"
                                            error={errors.age_group}
                                            required
                                        />
                                        <SelectField
                                            label="Stroke"
                                            name="stroke"
                                            options={strokes}
                                            defaultValue="freestyle"
                                            error={errors.stroke}
                                        />
                                        <TextField
                                            label="Distance (m)"
                                            name="distance"
                                            type="number"
                                            defaultValue={50}
                                            error={errors.distance}
                                            required
                                        />
                                        <TextField
                                            label="Time"
                                            name="time"
                                            placeholder="1:02.34 or 58.07"
                                            error={errors.time}
                                            required
                                        />
                                        <TextField
                                            label="Record holder"
                                            name="holder_name"
                                            error={errors.holder_name}
                                            required
                                        />
                                        <TextField
                                            label="Venue"
                                            name="venue_name"
                                            error={errors.venue_name}
                                        />
                                        <TextField
                                            label="Set on"
                                            name="set_on"
                                            type="date"
                                            error={errors.set_on}
                                        />
                                        <Button
                                            type="submit"
                                            className="w-full"
                                            disabled={processing}
                                        >
                                            Submit record
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
