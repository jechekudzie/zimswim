import { Head, router } from '@inertiajs/react';
import { Trophy } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { ProcessBand } from '@/components/public/process-band';
import { Section } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import { records as recordsRoute } from '@/routes';
import type { Option } from '@/types';

type RecordRow = {
    age_group: string;
    event: string;
    time: string;
    holder: string;
    venue: string | null;
    set_on: string | null;
};

export default function Records({
    records,
    filters,
    courses,
    pendingCount,
}: {
    records: Record<string, RecordRow[]>;
    filters: { course: string; gender: string };
    courses: Option[];
    pendingCount: number;
}) {
    const groups = Object.entries(records);

    function apply(next: Partial<typeof filters>) {
        router.get(
            recordsRoute().url,
            { ...filters, ...next },
            { preserveScroll: true },
        );
    }

    return (
        <PublicLayout
            heroImage={photos.pages.records}
            figures={[
                {
                    label: 'Records on this course',
                    value: Object.values(records).reduce(
                        (total, rows) => total + rows.length,
                        0,
                    ),
                },
                { label: 'Awaiting ratification', value: pendingCount },
            ]}
            title="Zimbabwe Swimming records"
            description="Every record tells a story. Records change only once the authorised body has ratified the swim."
        >
            <Head title="Records" />

            <Section>
                <div className="flex flex-wrap items-center gap-6">
                    <div className="flex flex-wrap gap-2">
                        {courses.map((course) => (
                            <Button
                                key={course.value}
                                size="sm"
                                variant={
                                    filters.course === course.value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => apply({ course: course.value })}
                            >
                                {course.label}
                            </Button>
                        ))}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {[
                            { value: 'female', label: 'Girls' },
                            { value: 'male', label: 'Boys' },
                            { value: 'mixed', label: 'Mixed relays' },
                        ].map((gender) => (
                            <Button
                                key={gender.value}
                                size="sm"
                                variant={
                                    filters.gender === gender.value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => apply({ gender: gender.value })}
                            >
                                {gender.label}
                            </Button>
                        ))}
                    </div>
                </div>

                {pendingCount > 0 && (
                    <p className="mt-6 rounded-lg border border-brand-gold/40 bg-brand-gold/10 px-4 py-3 text-sm">
                        {pendingCount}{' '}
                        {pendingCount === 1 ? 'swim is' : 'swims are'} awaiting
                        ratification and will appear here once approved.
                    </p>
                )}

                {groups.length === 0 ? (
                    <div className="mt-12 rounded-xl border border-dashed border-border p-16 text-center">
                        <Trophy className="mx-auto size-8 text-muted-foreground" />
                        <p className="mt-4 font-medium">
                            No records loaded for this selection yet
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            The historic record lists from the National Handbook
                            are being imported and verified.
                        </p>
                    </div>
                ) : (
                    <div className="mt-10 space-y-10">
                        {groups.map(([ageGroup, rows]) => (
                            <section key={ageGroup}>
                                <h2 className="text-sm font-semibold tracking-[0.16em] text-muted-foreground uppercase">
                                    {ageGroup}
                                </h2>
                                <div className="mt-3 overflow-x-auto rounded-xl border border-border">
                                    <table className="w-full min-w-[640px] text-sm">
                                        <thead className="border-b border-border bg-brand-surface/60 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                            <tr>
                                                <th className="px-5 py-3 font-medium">
                                                    Event
                                                </th>
                                                <th className="px-5 py-3 font-medium">
                                                    Time
                                                </th>
                                                <th className="px-5 py-3 font-medium">
                                                    Record holder
                                                </th>
                                                <th className="px-5 py-3 font-medium">
                                                    Venue
                                                </th>
                                                <th className="px-5 py-3 font-medium">
                                                    Set
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {rows.map((row) => (
                                                <tr
                                                    key={`${row.event}-${row.holder}`}
                                                    className="border-b border-border/60 bg-card last:border-0"
                                                >
                                                    <td className="px-5 py-3 font-medium">
                                                        {row.event}
                                                    </td>
                                                    <td className="px-5 py-3 font-mono font-semibold tabular-nums">
                                                        {row.time}
                                                    </td>
                                                    <td className="px-5 py-3">
                                                        {row.holder}
                                                    </td>
                                                    <td className="px-5 py-3 text-muted-foreground">
                                                        {row.venue ?? '—'}
                                                    </td>
                                                    <td className="px-5 py-3 text-muted-foreground tabular-nums">
                                                        {row.set_on ?? '—'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        ))}
                    </div>
                )}
            </Section>
            <ProcessBand
                title="How a record is ratified"
                lead="A fast swim is not a record until someone has checked it. Until then it sits on the list of swims awaiting ratification."
                steps={[
                    {
                        title: 'Flagged on the day',
                        body: 'When a time beats the standing mark for that event, age group and course, it is flagged as soon as the event is placed.',
                    },
                    {
                        title: 'Checked',
                        body: 'The swim is checked against the conditions a record needs: the right course, the right age group at the age-up date, and a result the referee has released.',
                    },
                    {
                        title: 'Ratified',
                        body: 'The authorised body ratifies the swim. Nothing on the record list moves before that happens.',
                    },
                    {
                        title: 'Published',
                        body: 'The ratified time replaces the old mark here, and the swimmer who held it keeps their place in the history of the event.',
                    },
                ]}
            />
        </PublicLayout>
    );
}
