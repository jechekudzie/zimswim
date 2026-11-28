import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, Lock, Plus, Trash2 } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type Event = {
    id: number;
    event_number: number | null;
    gender: string;
    age_group: string;
    min_age: number | null;
    max_age: number | null;
    stroke: string;
    stroke_label: string;
    distance: number;
    round: string;
    is_relay: boolean;
    relay_legs: number | null;
    is_locked: boolean;
};

type Session = {
    id: number;
    name: string;
    position: number;
    held_on: string;
    warmup_at: string | null;
    starts_at: string | null;
    events: Event[];
};

const GENDERS: Option[] = [
    { value: 'girls', label: 'Girls' },
    { value: 'boys', label: 'Boys' },
    { value: 'mixed', label: 'Mixed' },
];

const ROUNDS: Option[] = [
    { value: 'timed_final', label: 'Timed final' },
    { value: 'heat', label: 'Heat' },
    { value: 'final', label: 'Final' },
    { value: 'semi_final', label: 'Semi-final' },
];

function Field({
    label,
    children,
    error,
}: {
    label: string;
    children: React.ReactNode;
    error?: string;
}) {
    return (
        <div className="grid gap-1.5">
            <Label className="text-xs">{label}</Label>
            {children}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none';

export default function GalaProgramme({
    gala,
    sessions,
    strokes,
    can,
}: {
    gala: {
        id: number;
        slug: string;
        name: string;
        starts_on: string;
        ends_on: string;
    };
    sessions: Session[];
    strokes: Option[];
    can: { manage: boolean };
}) {
    useFlashMessages();
    const [addingTo, setAddingTo] = useState<number | null>(null);

    const nextNumber =
        Math.max(
            0,
            ...sessions.flatMap((session) =>
                session.events.map((event) => event.event_number ?? 0),
            ),
        ) + 1;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Galas', href: admin.galas.index().url },
                {
                    title: gala.name,
                    href: admin.galas.show(gala.slug).url,
                },
                {
                    title: 'Programme',
                    href: admin.galas.programme(gala.slug).url,
                },
            ]}
        >
            <Head title={`${gala.name} — programme`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Programme of events"
                    description="Sessions and the events in each. This is what appears on the gala's public page, so swimmers know what they are training for."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href={admin.galas.show(gala.slug)}>
                                <ArrowLeft className="size-4" />
                                Back to gala
                            </Link>
                        </Button>
                    }
                />

                {can.manage && (
                    <section className="rounded-xl border border-border bg-card p-5">
                        <h2 className="font-semibold">Add a session</h2>
                        <Form
                            {...admin.galas.sessions.store.form(gala.slug)}
                            options={{ preserveScroll: true }}
                            resetOnSuccess
                        >
                            {({ errors, processing }) => (
                                <div className="mt-4 flex flex-wrap items-end gap-3">
                                    <div className="w-44">
                                        <Field label="Name" error={errors.name}>
                                            <Input
                                                name="name"
                                                placeholder="Saturday AM"
                                            />
                                        </Field>
                                    </div>
                                    <div className="w-40">
                                        <Field
                                            label="Day"
                                            error={errors.held_on}
                                        >
                                            <Input
                                                name="held_on"
                                                type="date"
                                                min={gala.starts_on}
                                                max={gala.ends_on}
                                                defaultValue={gala.starts_on}
                                            />
                                        </Field>
                                    </div>
                                    <div className="w-28">
                                        <Field
                                            label="Warm-up"
                                            error={errors.warmup_at}
                                        >
                                            <Input
                                                name="warmup_at"
                                                type="time"
                                            />
                                        </Field>
                                    </div>
                                    <div className="w-28">
                                        <Field
                                            label="Start"
                                            error={errors.starts_at}
                                        >
                                            <Input
                                                name="starts_at"
                                                type="time"
                                            />
                                        </Field>
                                    </div>
                                    <Button type="submit" disabled={processing}>
                                        <Plus className="size-4" />
                                        Add session
                                    </Button>
                                </div>
                            )}
                        </Form>
                    </section>
                )}

                {sessions.length === 0 ? (
                    <EmptyState
                        icon={Plus}
                        title="No programme yet"
                        description="Add a session, then the events swum in it. The gala's public page says the programme is not published until you do."
                    />
                ) : (
                    sessions.map((session) => (
                        <section
                            key={session.id}
                            className="overflow-hidden rounded-xl border border-border"
                        >
                            <header className="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-muted/40 px-5 py-3">
                                <div>
                                    <h2 className="font-semibold">
                                        {session.name}
                                    </h2>
                                    <p className="text-xs text-muted-foreground">
                                        {[
                                            session.held_on,
                                            session.warmup_at &&
                                                `warm-up ${session.warmup_at}`,
                                            session.starts_at &&
                                                `start ${session.starts_at}`,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </div>

                                <div className="flex items-center gap-2">
                                    <span className="text-xs text-muted-foreground">
                                        {session.events.length}{' '}
                                        {session.events.length === 1
                                            ? 'event'
                                            : 'events'}
                                    </span>
                                    {can.manage &&
                                        session.events.length === 0 && (
                                            <Form
                                                {...admin.galas.sessions.destroy.form(
                                                    [gala.slug, session.id],
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        size="sm"
                                                        variant="ghost"
                                                        disabled={processing}
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </Button>
                                                )}
                                            </Form>
                                        )}
                                </div>
                            </header>

                            {session.events.length > 0 && (
                                <table className="w-full text-sm">
                                    <thead className="border-b border-border text-left text-xs tracking-wide text-muted-foreground uppercase">
                                        <tr>
                                            <th className="px-5 py-2 font-medium">
                                                No.
                                            </th>
                                            <th className="px-5 py-2 font-medium">
                                                Event
                                            </th>
                                            <th className="px-5 py-2 font-medium">
                                                Age group
                                            </th>
                                            <th className="px-5 py-2 font-medium">
                                                Round
                                            </th>
                                            <th className="px-5 py-2" />
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {session.events.map((event) => (
                                            <tr key={event.id}>
                                                <td className="px-5 py-2.5 font-mono text-xs">
                                                    {event.event_number ?? '—'}
                                                </td>
                                                <td className="px-5 py-2.5">
                                                    {event.gender === 'mixed'
                                                        ? 'Mixed'
                                                        : event.gender ===
                                                            'girls'
                                                          ? 'Girls'
                                                          : 'Boys'}{' '}
                                                    {event.is_relay &&
                                                        `${event.relay_legs} × `}
                                                    {event.distance}m{' '}
                                                    {event.stroke_label}
                                                </td>
                                                <td className="px-5 py-2.5 text-muted-foreground">
                                                    {event.age_group}
                                                </td>
                                                <td className="px-5 py-2.5 text-muted-foreground">
                                                    {ROUNDS.find(
                                                        (round) =>
                                                            round.value ===
                                                            event.round,
                                                    )?.label ?? event.round}
                                                </td>
                                                <td className="px-5 py-2.5 text-right">
                                                    {event.is_locked ? (
                                                        <Badge
                                                            variant="secondary"
                                                            className="text-xs font-normal"
                                                        >
                                                            <Lock className="size-3" />
                                                            Seeded
                                                        </Badge>
                                                    ) : (
                                                        can.manage && (
                                                            <Form
                                                                {...admin.galas.events.destroy.form(
                                                                    [
                                                                        gala.slug,
                                                                        event.id,
                                                                    ],
                                                                )}
                                                                options={{
                                                                    preserveScroll: true,
                                                                }}
                                                            >
                                                                {({
                                                                    processing,
                                                                }) => (
                                                                    <Button
                                                                        type="submit"
                                                                        size="sm"
                                                                        variant="ghost"
                                                                        disabled={
                                                                            processing
                                                                        }
                                                                    >
                                                                        <Trash2 className="size-3.5" />
                                                                    </Button>
                                                                )}
                                                            </Form>
                                                        )
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}

                            {can.manage && (
                                <div className="border-t border-border bg-muted/20 px-5 py-4">
                                    {addingTo === session.id ? (
                                        <Form
                                            {...admin.galas.events.store.form(
                                                gala.slug,
                                            )}
                                            options={{ preserveScroll: true }}
                                            resetOnSuccess
                                        >
                                            {({ errors, processing }) => (
                                                <div className="flex flex-wrap items-end gap-3">
                                                    <input
                                                        type="hidden"
                                                        name="gala_session_id"
                                                        value={session.id}
                                                    />
                                                    <div className="w-20">
                                                        <Field
                                                            label="No."
                                                            error={
                                                                errors.event_number
                                                            }
                                                        >
                                                            <Input
                                                                name="event_number"
                                                                type="number"
                                                                defaultValue={
                                                                    nextNumber
                                                                }
                                                            />
                                                        </Field>
                                                    </div>
                                                    <div className="w-28">
                                                        <Field
                                                            label="Gender"
                                                            error={
                                                                errors.gender
                                                            }
                                                        >
                                                            <select
                                                                name="gender"
                                                                className={
                                                                    selectClass
                                                                }
                                                            >
                                                                {GENDERS.map(
                                                                    (
                                                                        option,
                                                                    ) => (
                                                                        <option
                                                                            key={
                                                                                option.value
                                                                            }
                                                                            value={
                                                                                option.value
                                                                            }
                                                                        >
                                                                            {
                                                                                option.label
                                                                            }
                                                                        </option>
                                                                    ),
                                                                )}
                                                            </select>
                                                        </Field>
                                                    </div>
                                                    <div className="w-24">
                                                        <Field
                                                            label="Distance"
                                                            error={
                                                                errors.distance
                                                            }
                                                        >
                                                            <Input
                                                                name="distance"
                                                                type="number"
                                                                defaultValue={
                                                                    50
                                                                }
                                                            />
                                                        </Field>
                                                    </div>
                                                    <div className="w-44">
                                                        <Field
                                                            label="Stroke"
                                                            error={
                                                                errors.stroke
                                                            }
                                                        >
                                                            <select
                                                                name="stroke"
                                                                className={
                                                                    selectClass
                                                                }
                                                            >
                                                                {strokes.map(
                                                                    (
                                                                        option,
                                                                    ) => (
                                                                        <option
                                                                            key={
                                                                                option.value
                                                                            }
                                                                            value={
                                                                                option.value
                                                                            }
                                                                        >
                                                                            {
                                                                                option.label
                                                                            }
                                                                        </option>
                                                                    ),
                                                                )}
                                                            </select>
                                                        </Field>
                                                    </div>
                                                    <div className="w-32">
                                                        <Field
                                                            label="Age group"
                                                            error={
                                                                errors.age_group
                                                            }
                                                        >
                                                            <Input
                                                                name="age_group"
                                                                defaultValue="Open"
                                                            />
                                                        </Field>
                                                    </div>
                                                    <div className="w-20">
                                                        <Field
                                                            label="Min age"
                                                            error={
                                                                errors.min_age
                                                            }
                                                        >
                                                            <Input
                                                                name="min_age"
                                                                type="number"
                                                            />
                                                        </Field>
                                                    </div>
                                                    <div className="w-20">
                                                        <Field
                                                            label="Max age"
                                                            error={
                                                                errors.max_age
                                                            }
                                                        >
                                                            <Input
                                                                name="max_age"
                                                                type="number"
                                                            />
                                                        </Field>
                                                    </div>
                                                    <div className="w-36">
                                                        <Field
                                                            label="Round"
                                                            error={errors.round}
                                                        >
                                                            <select
                                                                name="round"
                                                                className={
                                                                    selectClass
                                                                }
                                                            >
                                                                {ROUNDS.map(
                                                                    (
                                                                        option,
                                                                    ) => (
                                                                        <option
                                                                            key={
                                                                                option.value
                                                                            }
                                                                            value={
                                                                                option.value
                                                                            }
                                                                        >
                                                                            {
                                                                                option.label
                                                                            }
                                                                        </option>
                                                                    ),
                                                                )}
                                                            </select>
                                                        </Field>
                                                    </div>
                                                    <div className="flex items-center gap-2 pb-1.5">
                                                        <input
                                                            id={`relay-${session.id}`}
                                                            type="checkbox"
                                                            name="is_relay"
                                                            value="1"
                                                            className="size-4 rounded border-input"
                                                        />
                                                        <Label
                                                            htmlFor={`relay-${session.id}`}
                                                            className="text-xs"
                                                        >
                                                            Relay
                                                        </Label>
                                                    </div>
                                                    <Button
                                                        type="submit"
                                                        disabled={processing}
                                                    >
                                                        Add event
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            setAddingTo(null)
                                                        }
                                                    >
                                                        Done
                                                    </Button>
                                                </div>
                                            )}
                                        </Form>
                                    ) : (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                setAddingTo(session.id)
                                            }
                                        >
                                            <Plus className="size-4" />
                                            Add an event to {session.name}
                                        </Button>
                                    )}
                                </div>
                            )}
                        </section>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
