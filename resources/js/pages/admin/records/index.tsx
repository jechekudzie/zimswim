import { Form, Head, router } from '@inertiajs/react';
import { Check, Trophy, X } from 'lucide-react';

import { SelectField, TextField } from '@/components/admin/form-field';
import { ExportButton } from '@/components/admin/export-button';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import { records as exportRecords } from '@/routes/admin/exports';
import type { Option, Paginated } from '@/types';

type RecordRow = {
    id: number;
    scope: string;
    course: string;
    gender: string;
    age_group: string;
    event: string;
    time: string;
    holder: string;
    club: string | null;
    venue: string | null;
    set_on: string | null;
    status: string;
    status_label: string;
};

export default function RecordsIndex({
    records,
    filters,
    statuses,
    courses,
    strokes,
    can,
}: {
    records: Paginated<RecordRow>;
    filters: { status: string | null; course: string | null };
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
                    <div className="xl:col-span-2">
                        {records.data.length === 0 ? (
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
                                <div className="overflow-x-auto rounded-xl border border-border">
                                    <table className="w-full min-w-[760px] text-sm">
                                        <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                            <tr>
                                                <th className="px-4 py-3 font-medium">
                                                    Event
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Time
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Holder
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Status
                                                </th>
                                                <th className="px-4 py-3" />
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {records.data.map((record) => (
                                                <tr
                                                    key={record.id}
                                                    className="border-b border-border/60 bg-card last:border-0"
                                                >
                                                    <td className="px-4 py-3">
                                                        <p className="font-medium">
                                                            {record.event}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {record.age_group} ·{' '}
                                                            {record.course} ·{' '}
                                                            {record.gender}
                                                        </p>
                                                    </td>
                                                    <td className="px-4 py-3 font-mono font-semibold tabular-nums">
                                                        {record.time}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <p>{record.holder}</p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {record.venue ??
                                                                '—'}
                                                            {record.set_on
                                                                ? ` · ${record.set_on}`
                                                                : ''}
                                                        </p>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge
                                                            variant="outline"
                                                            className={
                                                                record.status ===
                                                                'ratified'
                                                                    ? 'border-brand-green/40 text-brand-green'
                                                                    : record.status ===
                                                                        'pending'
                                                                      ? 'border-brand-gold/60'
                                                                      : 'text-muted-foreground'
                                                            }
                                                        >
                                                            {
                                                                record.status_label
                                                            }
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {can.ratify &&
                                                            record.status ===
                                                                'pending' && (
                                                                <div className="flex justify-end gap-1">
                                                                    <Button
                                                                        size="sm"
                                                                        variant="ghost"
                                                                        onClick={() =>
                                                                            router.post(
                                                                                admin.records.ratify(
                                                                                    record.id,
                                                                                )
                                                                                    .url,
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
                                                                        onClick={() =>
                                                                            router.post(
                                                                                admin.records.reject(
                                                                                    record.id,
                                                                                )
                                                                                    .url,
                                                                                {},
                                                                                {
                                                                                    preserveScroll: true,
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        <X className="size-4 text-brand-red" />
                                                                    </Button>
                                                                </div>
                                                            )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <Pagination meta={records} />
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
