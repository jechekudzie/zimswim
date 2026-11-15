import { Form, Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, FileText, Trash2, Trophy, Upload } from 'lucide-react';

import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

type ResultsFile = {
    slug: string;
    title: string;
    description: string | null;
    size: string;
    uploaded_on: string | null;
    url: string;
};

/**
 * The MeetManager export a gala is published from.
 *
 * Times are recorded there on the day rather than typed in here, so this
 * file is the official record of what was swum. More than one is normal: a
 * two-day meet is usually exported per day, and a corrected reissue sits
 * beside the original rather than quietly replacing it, so anybody who
 * downloaded the first can see there was a second.
 */
function ResultsFiles({
    gala,
    files,
    canPublish,
}: {
    gala: { slug: string; name: string };
    files: ResultsFile[];
    canPublish: boolean;
}) {
    return (
        <section className="rounded-xl border border-border bg-card">
            <header className="border-b border-border/60 px-5 py-4">
                <h2 className="flex items-center gap-2 font-semibold">
                    <FileText className="size-4 text-primary" />
                    Official results file
                </h2>
                <p className="mt-1 text-sm text-muted-foreground">
                    The export from MeetManager. This is what the public
                    downloads from the gala's results page.
                </p>
            </header>

            {files.length === 0 ? (
                <p className="px-5 py-4 text-sm text-muted-foreground">
                    Nothing attached yet.
                </p>
            ) : (
                <ul className="divide-y divide-border/60">
                    {files.map((file) => (
                        <li
                            key={file.slug}
                            className="flex flex-wrap items-center gap-3 px-5 py-3"
                        >
                            <div className="min-w-0 flex-1">
                                <a
                                    href={file.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="font-medium hover:underline"
                                >
                                    {file.title}
                                </a>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    {file.size}
                                    {file.uploaded_on &&
                                        ` · added ${file.uploaded_on}`}
                                    {file.description &&
                                        ` · ${file.description}`}
                                </p>
                            </div>

                            {canPublish && (
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    aria-label={`Remove ${file.title}`}
                                    onClick={() =>
                                        router.delete(
                                            admin.galas.resultsFiles.destroy([
                                                gala.slug,
                                                file.slug,
                                            ]).url,
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <Trash2 className="size-4 text-brand-red" />
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {canPublish && (
                <Form
                    {...admin.galas.resultsFiles.store.form(gala.slug)}
                    className="space-y-4 border-t border-border/60 px-5 py-4"
                    resetOnSuccess
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">
                                    What is this file?
                                </Label>
                                <Input
                                    id="title"
                                    name="title"
                                    placeholder="Full results, day 1"
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="file">
                                    PDF from MeetManager
                                </Label>
                                <Input
                                    id="file"
                                    name="file"
                                    type="file"
                                    accept="application/pdf"
                                    required
                                />
                                <InputError message={errors.file} />
                            </div>

                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                            >
                                <Upload className="size-4" />
                                {processing ? 'Attaching…' : 'Attach results'}
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </section>
    );
}

export default function GalaResults({
    gala,
    events,
    pointsTable,
    resultsFiles,
    can,
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
    resultsFiles: ResultsFile[];
    can: { record: boolean; publishFiles: boolean };
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

                <ResultsFiles
                    gala={gala}
                    files={resultsFiles}
                    canPublish={can.publishFiles}
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
