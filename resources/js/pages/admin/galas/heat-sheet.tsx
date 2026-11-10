import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ListOrdered, Printer, Shuffle } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';

type Heat = {
    heat: number;
    lanes: {
        lane: number;
        swimmer: string;
        club: string;
        entry_time: string;
        legs?: string[];
    }[];
};

type EventRow = {
    id: number;
    number: number;
    title: string;
    age_group: string;
    session: string | null;
    is_relay: boolean;
    seeded: boolean;
    entry_count: number;
    heats: Heat[];
};

export default function HeatSheet({
    gala,
    events,
    can,
}: {
    gala: {
        slug: string;
        name: string;
        venue: string | null;
        lanes: number;
        status_label: string;
    };
    events: EventRow[];
    can: { seed: boolean };
}) {
    useFlashMessages();

    const seedAll = () =>
        router.post(
            admin.galas.seed(gala.slug).url,
            {},
            { preserveScroll: true },
        );

    const seedOne = (id: number) =>
        router.post(
            admin.galas.seed(gala.slug).url,
            { gala_event_id: id },
            { preserveScroll: true },
        );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Galas', href: admin.galas.index().url },
                { title: gala.name, href: admin.galas.show(gala.slug).url },
                {
                    title: 'Heat sheet',
                    href: admin.galas.heatSheet(gala.slug).url,
                },
            ]}
        >
            <Head title={`${gala.name} heat sheet`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit print:hidden"
                >
                    <Link href={admin.galas.show(gala.slug)}>
                        <ArrowLeft className="size-4" />
                        Back to gala
                    </Link>
                </Button>

                <PageHeader
                    title="Heat sheet"
                    description={`${gala.name}${gala.venue ? ` · ${gala.venue}` : ''} · ${gala.lanes} lanes · heats run slowest first, lanes seeded from the centre out`}
                    actions={
                        <div className="flex gap-2 print:hidden">
                            <Button
                                variant="outline"
                                onClick={() => window.print()}
                            >
                                <Printer className="size-4" />
                                Print
                            </Button>
                            {can.seed && (
                                <Button onClick={seedAll}>
                                    <Shuffle className="size-4" />
                                    Seed all events
                                </Button>
                            )}
                        </div>
                    }
                />

                {events.length === 0 ? (
                    <EmptyState
                        icon={ListOrdered}
                        title="No events on this gala yet"
                        description="Build the programme before seeding."
                    />
                ) : (
                    <div className="space-y-6">
                        {events.map((event) => (
                            <section
                                key={event.id}
                                className="overflow-hidden rounded-xl border border-border bg-card"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-muted/40 px-5 py-3">
                                    <div>
                                        <h2 className="font-semibold">
                                            <span className="font-mono text-sm text-muted-foreground">
                                                {event.number}
                                            </span>{' '}
                                            {event.title}
                                        </h2>
                                        <p className="text-xs text-muted-foreground">
                                            {event.age_group}
                                            {event.session
                                                ? ` · ${event.session}`
                                                : ''}{' '}
                                            · {event.entry_count}{' '}
                                            {event.is_relay
                                                ? 'teams'
                                                : 'entries'}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2 print:hidden">
                                        <Badge
                                            variant={
                                                event.seeded
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            {event.seeded
                                                ? 'Seeded'
                                                : 'Not seeded'}
                                        </Badge>
                                        {can.seed && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    seedOne(event.id)
                                                }
                                            >
                                                <Shuffle className="size-3.5" />
                                                Seed
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                {event.heats.length === 0 ? (
                                    <p className="px-5 py-6 text-sm text-muted-foreground">
                                        No heats yet.
                                    </p>
                                ) : (
                                    <div className="grid gap-4 p-5 md:grid-cols-2">
                                        {event.heats.map((heat) => (
                                            <div
                                                key={heat.heat}
                                                className="rounded-lg border border-border p-4"
                                            >
                                                <h3 className="text-sm font-semibold">
                                                    Heat {heat.heat}
                                                </h3>
                                                <table className="mt-3 w-full text-sm">
                                                    <tbody>
                                                        {heat.lanes.map(
                                                            (lane) => (
                                                                <tr
                                                                    key={
                                                                        lane.lane
                                                                    }
                                                                    className="border-b border-border/50 last:border-0"
                                                                >
                                                                    <td className="py-1.5 pr-3 font-mono text-xs text-muted-foreground">
                                                                        L
                                                                        {
                                                                            lane.lane
                                                                        }
                                                                    </td>
                                                                    <td className="py-1.5 pr-3 font-medium">
                                                                        {
                                                                            lane.swimmer
                                                                        }
                                                                        {lane.legs && (
                                                                            <span className="block text-xs font-normal text-muted-foreground">
                                                                                {lane.legs.join(
                                                                                    ', ',
                                                                                )}
                                                                            </span>
                                                                        )}
                                                                    </td>
                                                                    <td className="py-1.5 pr-3 text-xs text-muted-foreground">
                                                                        {
                                                                            lane.club
                                                                        }
                                                                    </td>
                                                                    <td className="py-1.5 text-right font-mono tabular-nums">
                                                                        {
                                                                            lane.entry_time
                                                                        }
                                                                    </td>
                                                                </tr>
                                                            ),
                                                        )}
                                                    </tbody>
                                                </table>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
