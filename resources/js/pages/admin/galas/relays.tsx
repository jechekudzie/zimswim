import { Form, Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2, Users2 } from 'lucide-react';
import { useMemo, useState } from 'react';

import { EmptyState } from '@/components/admin/empty-state';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';

type RelayEvent = {
    value: number;
    label: string;
    legs: number;
    gender: string;
    age_group: string;
    medley: boolean;
    seeded: boolean;
};

type Swimmer = {
    value: number;
    label: string;
    gender: string;
    club_id: number;
    age: number;
};

type Team = {
    id: number;
    event_id: number;
    event_number: number;
    event: string;
    age_group: string;
    club: string;
    letter: string;
    entry_time: string;
    heat: number | null;
    lane: number | null;
    status: string;
    scores_points: boolean;
    legs: {
        leg: number;
        swimmer: string;
        stroke: string | null;
        is_borrowed: boolean;
    }[];
};

const MEDLEY_ORDER = ['Backstroke', 'Breaststroke', 'Butterfly', 'Freestyle'];

export default function GalaRelays({
    gala,
    events,
    teams,
    clubs,
    swimmers,
    can,
}: {
    gala: {
        id: number;
        slug: string;
        name: string;
        accepting_entries: boolean;
        age_up_date: string;
        lanes: number;
    };
    events: RelayEvent[];
    teams: Team[];
    clubs: { value: number; label: string }[];
    swimmers: Swimmer[];
    can: { manage: boolean };
}) {
    useFlashMessages();

    const [eventId, setEventId] = useState<number | undefined>(
        events[0]?.value,
    );
    const [clubId, setClubId] = useState<number | undefined>(clubs[0]?.value);
    const [legs, setLegs] = useState<(number | '')[]>([]);
    const [borrowed, setBorrowed] = useState<Record<number, boolean>>({});

    const event = events.find((candidate) => candidate.value === eventId);
    const legCount = event?.legs ?? 4;

    // Only swimmers of the right club, inside the age band, and of a gender the
    // event allows can fill a leg. The server checks all of this again.
    const available = useMemo(
        () =>
            swimmers.filter((swimmer) => {
                if (event === undefined) {
                    return false;
                }

                if (
                    event.gender !== 'mixed' &&
                    swimmer.gender !== event.gender
                ) {
                    return false;
                }

                return true;
            }),
        [swimmers, event],
    );

    const ownClub = available.filter((swimmer) => swimmer.club_id === clubId);
    const others = available.filter((swimmer) => swimmer.club_id !== clubId);

    const setLeg = (index: number, value: number | '') =>
        setLegs((current) => {
            const next = [...current];
            next[index] = value;

            return next;
        });

    const chosen = legs
        .slice(0, legCount)
        .filter(
            (value): value is number => value !== '' && value !== undefined,
        );

    const complete = chosen.length === legCount;

    const byEvent = events
        .map((candidate) => ({
            event: candidate,
            teams: teams.filter((team) => team.event_id === candidate.value),
        }))
        .filter((group) => group.teams.length > 0);

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Galas', href: admin.galas.index().url },
                { title: gala.name, href: admin.galas.show(gala.slug).url },
                {
                    title: 'Relays',
                    href: admin.galas.relays.index(gala.slug).url,
                },
            ]}
        >
            <Head title={`${gala.name} relays`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.galas.show(gala.slug)}>
                        <ArrowLeft className="size-4" />
                        Back to gala
                    </Link>
                </Button>

                <PageHeader
                    title="Relay teams"
                    description={`${gala.name} · ages taken at ${gala.age_up_date} · a club may enter at most two teams per event`}
                    actions={
                        <Button asChild variant="outline">
                            <Link href={admin.galas.entries.index(gala.slug)}>
                                Individual entries
                            </Link>
                        </Button>
                    }
                />

                {events.length === 0 ? (
                    <EmptyState
                        icon={Users2}
                        title="No relay events in this programme"
                        description="Add a relay event to the gala before declaring teams."
                    />
                ) : (
                    <div className="grid gap-6 xl:grid-cols-3">
                        <div className="space-y-6 xl:col-span-2">
                            {byEvent.length === 0 ? (
                                <EmptyState
                                    icon={Users2}
                                    title="No teams declared yet"
                                    description="Build a team on the right. Swimmers must be in the age band and the gender the event allows."
                                />
                            ) : (
                                byEvent.map(
                                    ({ event: relayEvent, teams: rows }) => (
                                        <section
                                            key={relayEvent.value}
                                            className="overflow-hidden rounded-xl border border-border bg-card"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-muted/40 px-5 py-3">
                                                <h2 className="font-semibold">
                                                    {relayEvent.label}
                                                </h2>
                                                <Badge
                                                    variant={
                                                        relayEvent.seeded
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                >
                                                    {relayEvent.seeded
                                                        ? 'Seeded'
                                                        : 'Not seeded'}
                                                </Badge>
                                            </div>

                                            <ul className="divide-y divide-border">
                                                {rows.map((team) => (
                                                    <li
                                                        key={team.id}
                                                        className="flex flex-wrap items-start justify-between gap-4 px-5 py-4"
                                                    >
                                                        <div className="min-w-0">
                                                            <p className="font-medium">
                                                                {team.club}{' '}
                                                                <span className="text-muted-foreground">
                                                                    {
                                                                        team.letter
                                                                    }
                                                                </span>
                                                                {!team.scores_points && (
                                                                    <Badge
                                                                        variant="outline"
                                                                        className="ml-2 align-middle text-brand-gold"
                                                                    >
                                                                        Medals
                                                                        only
                                                                    </Badge>
                                                                )}
                                                            </p>
                                                            <ol className="mt-1.5 space-y-0.5 text-sm text-muted-foreground">
                                                                {team.legs.map(
                                                                    (leg) => (
                                                                        <li
                                                                            key={
                                                                                leg.leg
                                                                            }
                                                                        >
                                                                            <span className="font-mono text-xs">
                                                                                {
                                                                                    leg.leg
                                                                                }
                                                                            </span>{' '}
                                                                            {
                                                                                leg.swimmer
                                                                            }
                                                                            {leg.stroke
                                                                                ? ` · ${leg.stroke.replace('_', ' ')}`
                                                                                : ''}
                                                                            {leg.is_borrowed
                                                                                ? ' · borrowed'
                                                                                : ''}
                                                                        </li>
                                                                    ),
                                                                )}
                                                            </ol>
                                                        </div>

                                                        <div className="flex items-center gap-3">
                                                            <div className="text-right">
                                                                <p className="font-mono text-sm tabular-nums">
                                                                    {
                                                                        team.entry_time
                                                                    }
                                                                </p>
                                                                {team.heat !==
                                                                    null && (
                                                                    <p className="text-xs text-muted-foreground">
                                                                        H
                                                                        {
                                                                            team.heat
                                                                        }{' '}
                                                                        L
                                                                        {
                                                                            team.lane
                                                                        }
                                                                    </p>
                                                                )}
                                                            </div>
                                                            {can.manage && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() =>
                                                                        router.delete(
                                                                            admin.galas.relays.destroy(
                                                                                [
                                                                                    gala.slug,
                                                                                    team.id,
                                                                                ],
                                                                            )
                                                                                .url,
                                                                            {
                                                                                preserveScroll: true,
                                                                            },
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="size-4 text-brand-red" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </li>
                                                ))}
                                            </ul>
                                        </section>
                                    ),
                                )
                            )}
                        </div>

                        {can.manage && (
                            <section className="h-fit rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">
                                    Declare a team
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Put swimmers in the order they will swim.
                                    {event?.medley
                                        ? ' A medley relay swims back, breast, fly, free.'
                                        : ''}
                                </p>

                                <Form
                                    {...admin.galas.relays.store.form(
                                        gala.slug,
                                    )}
                                    className="mt-5 space-y-4"
                                    onSuccess={() => {
                                        setLegs([]);
                                        setBorrowed({});
                                    }}
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="gala_event_id">
                                                    Event
                                                </Label>
                                                <select
                                                    id="gala_event_id"
                                                    name="gala_event_id"
                                                    value={eventId ?? ''}
                                                    onChange={(e) => {
                                                        setEventId(
                                                            Number(
                                                                e.target.value,
                                                            ),
                                                        );
                                                        setLegs([]);
                                                    }}
                                                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                                >
                                                    {events.map((option) => (
                                                        <option
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={
                                                        errors.gala_event_id
                                                    }
                                                />
                                            </div>

                                            <div className="grid grid-cols-[1fr_auto] gap-3">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="organization_id">
                                                        Club
                                                    </Label>
                                                    <select
                                                        id="organization_id"
                                                        name="organization_id"
                                                        value={clubId ?? ''}
                                                        onChange={(e) =>
                                                            setClubId(
                                                                Number(
                                                                    e.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                                    >
                                                        {clubs.map((club) => (
                                                            <option
                                                                key={club.value}
                                                                value={
                                                                    club.value
                                                                }
                                                            >
                                                                {club.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <InputError
                                                        message={
                                                            errors.organization_id
                                                        }
                                                    />
                                                </div>

                                                <div className="grid w-20 gap-2">
                                                    <Label htmlFor="letter">
                                                        Team
                                                    </Label>
                                                    <select
                                                        id="letter"
                                                        name="letter"
                                                        defaultValue="A"
                                                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                                    >
                                                        <option value="A">
                                                            A
                                                        </option>
                                                        <option value="B">
                                                            B
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div className="grid gap-3">
                                                <Label>Legs ({legCount})</Label>
                                                {Array.from({
                                                    length: legCount,
                                                }).map((_, index) => {
                                                    const value =
                                                        legs[index] ?? '';

                                                    return (
                                                        <div
                                                            key={index}
                                                            className="grid gap-1.5"
                                                        >
                                                            <div className="flex items-center gap-2">
                                                                <span className="w-16 shrink-0 text-xs text-muted-foreground">
                                                                    {event?.medley
                                                                        ? MEDLEY_ORDER[
                                                                              index
                                                                          ]
                                                                        : `Leg ${index + 1}`}
                                                                </span>
                                                                <select
                                                                    name="member_ids[]"
                                                                    value={
                                                                        value
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        setLeg(
                                                                            index,
                                                                            e
                                                                                .target
                                                                                .value ===
                                                                                ''
                                                                                ? ''
                                                                                : Number(
                                                                                      e
                                                                                          .target
                                                                                          .value,
                                                                                  ),
                                                                        )
                                                                    }
                                                                    className="h-9 min-w-0 flex-1 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs"
                                                                >
                                                                    <option value="">
                                                                        Choose a
                                                                        swimmer
                                                                    </option>
                                                                    <optgroup label="This club">
                                                                        {ownClub.map(
                                                                            (
                                                                                swimmer,
                                                                            ) => (
                                                                                <option
                                                                                    key={
                                                                                        swimmer.value
                                                                                    }
                                                                                    value={
                                                                                        swimmer.value
                                                                                    }
                                                                                    disabled={
                                                                                        chosen.includes(
                                                                                            swimmer.value,
                                                                                        ) &&
                                                                                        value !==
                                                                                            swimmer.value
                                                                                    }
                                                                                >
                                                                                    {
                                                                                        swimmer.label
                                                                                    }{' '}
                                                                                    (
                                                                                    {
                                                                                        swimmer.age
                                                                                    }
                                                                                    )
                                                                                </option>
                                                                            ),
                                                                        )}
                                                                    </optgroup>
                                                                    <optgroup label="Borrowed from another club">
                                                                        {others.map(
                                                                            (
                                                                                swimmer,
                                                                            ) => (
                                                                                <option
                                                                                    key={
                                                                                        swimmer.value
                                                                                    }
                                                                                    value={
                                                                                        swimmer.value
                                                                                    }
                                                                                    disabled={
                                                                                        chosen.includes(
                                                                                            swimmer.value,
                                                                                        ) &&
                                                                                        value !==
                                                                                            swimmer.value
                                                                                    }
                                                                                >
                                                                                    {
                                                                                        swimmer.label
                                                                                    }{' '}
                                                                                    (
                                                                                    {
                                                                                        swimmer.age
                                                                                    }
                                                                                    )
                                                                                </option>
                                                                            ),
                                                                        )}
                                                                    </optgroup>
                                                                </select>
                                                            </div>

                                                            {value !== '' &&
                                                                others.some(
                                                                    (swimmer) =>
                                                                        swimmer.value ===
                                                                        value,
                                                                ) && (
                                                                    <label className="ml-18 flex items-center gap-2 text-xs text-muted-foreground">
                                                                        <input
                                                                            type="checkbox"
                                                                            name={`borrowed[${value}]`}
                                                                            value="1"
                                                                            checked={
                                                                                borrowed[
                                                                                    value
                                                                                ] ??
                                                                                true
                                                                            }
                                                                            onChange={(
                                                                                e,
                                                                            ) =>
                                                                                setBorrowed(
                                                                                    (
                                                                                        current,
                                                                                    ) => ({
                                                                                        ...current,
                                                                                        [value]:
                                                                                            e
                                                                                                .target
                                                                                                .checked,
                                                                                    }),
                                                                                )
                                                                            }
                                                                        />
                                                                        Borrowed
                                                                        — the
                                                                        team can
                                                                        win
                                                                        medals
                                                                        but
                                                                        scores
                                                                        no
                                                                        points
                                                                    </label>
                                                                )}
                                                        </div>
                                                    );
                                                })}
                                                <InputError
                                                    message={errors.member_ids}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="entry_time">
                                                    Entry time
                                                </Label>
                                                <Input
                                                    id="entry_time"
                                                    name="entry_time"
                                                    placeholder="2:11.45 or leave blank for NT"
                                                />
                                                <InputError
                                                    message={errors.entry_time}
                                                />
                                            </div>

                                            <Button
                                                type="submit"
                                                className="w-full"
                                                disabled={
                                                    processing || !complete
                                                }
                                            >
                                                <Plus className="size-4" />
                                                {complete
                                                    ? 'Declare team'
                                                    : `Choose ${legCount - chosen.length} more`}
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </section>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
