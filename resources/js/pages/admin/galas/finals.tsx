import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Medal, TriangleAlert, Waves } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';

type Competitor = {
    rank?: number;
    swimmer: string;
    club: string;
    time: string;
};

type Final = {
    id: number;
    number: number;
    title: string;
    age_group: string;
    places: number;
    heats_event: {
        number: number;
        recorded: number;
        published: boolean;
    } | null;
    entered: number;
    seeded: boolean;
    contested_places: number;
    qualifiers: Competitor[];
    swim_off: Competitor[];
};

export default function GalaFinals({
    gala,
    finals,
    can,
}: {
    gala: { slug: string; name: string; lanes: number };
    finals: Final[];
    can: { promote: boolean };
}) {
    useFlashMessages();

    const promote = (id: number) =>
        router.post(
            admin.galas.finals.promote(gala.slug).url,
            { gala_event_id: id },
            { preserveScroll: true },
        );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Galas', href: admin.galas.index().url },
                { title: gala.name, href: admin.galas.show(gala.slug).url },
                {
                    title: 'Finals',
                    href: admin.galas.finals.index(gala.slug).url,
                },
            ]}
        >
            <Head title={`${gala.name} finals`} />

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
                    title="Finals"
                    description={`${gala.name} · qualifiers are taken on heat time and seeded fastest into the centre lane`}
                    actions={
                        <Button asChild variant="outline">
                            <Link href={admin.galas.heatSheet(gala.slug)}>
                                Heat sheet
                            </Link>
                        </Button>
                    }
                />

                {finals.length === 0 ? (
                    <EmptyState
                        icon={Medal}
                        title="No finals in this programme"
                        description="A final is an event linked to the heats it is swum from. Add one to the gala programme and point it at its heats."
                    />
                ) : (
                    <div className="space-y-6">
                        {finals.map((final) => (
                            <section
                                key={final.id}
                                className="overflow-hidden rounded-xl border border-border bg-card"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-muted/40 px-5 py-3">
                                    <div>
                                        <h2 className="font-semibold">
                                            <span className="font-mono text-sm text-muted-foreground">
                                                {final.number}
                                            </span>{' '}
                                            {final.title}
                                        </h2>
                                        <p className="text-xs text-muted-foreground">
                                            {final.age_group} · top{' '}
                                            {final.places} from event{' '}
                                            {final.heats_event?.number ?? '—'}
                                            {final.heats_event
                                                ? ` · ${final.heats_event.recorded} heat times recorded`
                                                : ''}
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Badge
                                            variant={
                                                final.seeded
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            {final.seeded
                                                ? `Seeded · ${final.entered} in`
                                                : 'Not seeded'}
                                        </Badge>
                                        {can.promote && (
                                            <Button
                                                size="sm"
                                                disabled={
                                                    (final.heats_event
                                                        ?.recorded ?? 0) === 0
                                                }
                                                onClick={() =>
                                                    promote(final.id)
                                                }
                                            >
                                                <Waves className="size-3.5" />
                                                {final.seeded
                                                    ? 'Re-promote'
                                                    : 'Promote and seed'}
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                {final.swim_off.length > 0 && (
                                    <div className="flex gap-3 border-b border-border bg-brand-gold/10 px-5 py-4">
                                        <TriangleAlert className="mt-0.5 size-4 shrink-0 text-brand-gold" />
                                        <div className="text-sm">
                                            <p className="font-medium">
                                                Swim-off needed for{' '}
                                                {final.contested_places}{' '}
                                                {final.contested_places === 1
                                                    ? 'place'
                                                    : 'places'}
                                            </p>
                                            <p className="mt-0.5 text-muted-foreground">
                                                {final.swim_off
                                                    .map(
                                                        (competitor) =>
                                                            `${competitor.swimmer} (${competitor.club}) ${competitor.time}`,
                                                    )
                                                    .join(' · ')}
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                These swimmers are tied on the
                                                cut-off time. The place is left
                                                open until the referee orders a
                                                swim-off and the time is
                                                recorded.
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {final.qualifiers.length === 0 ? (
                                    <p className="px-5 py-6 text-sm text-muted-foreground">
                                        No heat times yet, so there is nobody to
                                        promote.
                                    </p>
                                ) : (
                                    <table className="w-full text-sm">
                                        <thead className="text-left text-xs text-muted-foreground">
                                            <tr className="border-b border-border">
                                                <th className="px-5 py-2 font-medium">
                                                    #
                                                </th>
                                                <th className="px-5 py-2 font-medium">
                                                    Swimmer
                                                </th>
                                                <th className="px-5 py-2 font-medium">
                                                    Club
                                                </th>
                                                <th className="px-5 py-2 text-right font-medium">
                                                    Heat time
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {final.qualifiers.map(
                                                (competitor) => (
                                                    <tr
                                                        key={`${competitor.rank}-${competitor.swimmer}`}
                                                        className="border-b border-border/50 last:border-0"
                                                    >
                                                        <td className="px-5 py-2 font-mono text-xs text-muted-foreground">
                                                            {competitor.rank}
                                                        </td>
                                                        <td className="px-5 py-2 font-medium">
                                                            {competitor.swimmer}
                                                        </td>
                                                        <td className="px-5 py-2 text-muted-foreground">
                                                            {competitor.club}
                                                        </td>
                                                        <td className="px-5 py-2 text-right font-mono tabular-nums">
                                                            {competitor.time}
                                                        </td>
                                                    </tr>
                                                ),
                                            )}
                                        </tbody>
                                    </table>
                                )}
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
