import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Trophy } from 'lucide-react';

import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';

type EventRow = {
    id: number;
    number: number;
    title: string;
    age_group: string;
    session: string | null;
    seeded: boolean;
    published: boolean;
    entries: number;
    recorded: number;
};

export default function GalaResults({
    gala,
    events,
    pointsTable,
}: {
    gala: { slug: string; name: string; status_label: string };
    events: EventRow[];
    pointsTable: {
        club: string;
        points: number;
        golds: number;
        silvers: number;
        bronzes: number;
    }[];
}) {
    useFlashMessages();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Galas', href: admin.galas.index().url },
                { title: gala.name, href: admin.galas.show(gala.slug).url },
                {
                    title: 'Results',
                    href: admin.galas.results.index(gala.slug).url,
                },
            ]}
        >
            <Head title={`${gala.name} results`} />

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
                    title="Results"
                    description={`${gala.name} · record times event by event, then publish each one to the website`}
                />

                <div className="grid gap-6 xl:grid-cols-3">
                    <div className="space-y-2 xl:col-span-2">
                        {events.map((event) => (
                            <Link
                                key={event.id}
                                href={admin.galas.results.edit([
                                    gala.slug,
                                    event.id,
                                ])}
                                className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-card p-5 transition-colors hover:border-primary/40"
                            >
                                <div>
                                    <p className="font-medium">
                                        <span className="font-mono text-sm text-muted-foreground">
                                            {event.number}
                                        </span>{' '}
                                        {event.title}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {event.age_group}
                                        {event.session
                                            ? ` · ${event.session}`
                                            : ''}{' '}
                                        · {event.recorded} of {event.entries}{' '}
                                        recorded
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    {!event.seeded && (
                                        <Badge variant="outline">
                                            Not seeded
                                        </Badge>
                                    )}
                                    <Badge
                                        variant={
                                            event.published
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {event.published
                                            ? 'Published'
                                            : 'Unpublished'}
                                    </Badge>
                                </div>
                            </Link>
                        ))}
                    </div>

                    <section className="h-fit rounded-xl border border-border bg-card p-6">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <Trophy className="size-4 text-primary" />
                            Club points
                        </h2>
                        {pointsTable.length === 0 ? (
                            <p className="mt-4 text-sm text-muted-foreground">
                                No scored swims yet.
                            </p>
                        ) : (
                            <ol className="mt-4 space-y-3">
                                {pointsTable.map((row, index) => (
                                    <li
                                        key={row.club}
                                        className="flex items-center justify-between gap-3 text-sm"
                                    >
                                        <span className="flex items-center gap-2">
                                            <span className="w-4 text-right text-xs text-muted-foreground tabular-nums">
                                                {index + 1}
                                            </span>
                                            {row.club}
                                        </span>
                                        <span className="flex items-center gap-3 text-xs text-muted-foreground">
                                            <span>
                                                {row.golds}/{row.silvers}/
                                                {row.bronzes}
                                            </span>
                                            <span className="font-semibold text-foreground tabular-nums">
                                                {row.points}
                                            </span>
                                        </span>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
