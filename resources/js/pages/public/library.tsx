import { Head } from '@inertiajs/react';
import { Download, FileText } from 'lucide-react';

import { Prose } from '@/components/prose';
import { FlagRule } from '@/components/flag-rule';
import { Section } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import library from '@/routes/library';

type Document = {
    title: string;
    slug: string;
    description: string | null;
    size: string;
    season: string | null;
    issued_by: string | null;
};

type Shelf = {
    key: string;
    label: string;
    documents: Document[];
};

export default function Library({
    shelves,
    total,
}: {
    shelves: Shelf[];
    total: number;
}) {
    return (
        <PublicLayout
            heroImage={photos.pages.about}
            figures={[{ label: 'Documents', value: total }]}
            title="Handbook and forms"
            description="The national handbook, the rules a gala is run under, and the forms a club needs to register a swimmer or move one."
        >
            <Head title="Handbook and forms" />

            <Section>
                {shelves.length === 0 ? (
                    <p className="rounded-lg border border-dashed border-border p-12 text-center text-muted-foreground">
                        Nothing has been published here yet.
                    </p>
                ) : (
                    <div className="space-y-14">
                        {shelves.map((shelf) => (
                            <section key={shelf.key}>
                                <FlagRule />
                                <h2 className="mt-4 text-2xl font-bold tracking-tight">
                                    {shelf.label}
                                </h2>

                                <ul className="mt-6 divide-y divide-border overflow-hidden rounded-xl border border-border">
                                    {shelf.documents.map((document) => (
                                        <li key={document.slug}>
                                            {/*
                                             * The whole row is the link, so
                                             * the target is the size of the
                                             * thing being asked for rather
                                             * than a word at the end of it.
                                             */}
                                            <a
                                                href={
                                                    library.download(
                                                        document.slug,
                                                    ).url
                                                }
                                                className="group flex items-start gap-4 bg-card p-5 transition-colors hover:bg-brand-surface/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                <FileText className="mt-0.5 size-5 shrink-0 text-primary" />

                                                <div className="min-w-0 flex-1">
                                                    <p className="font-semibold group-hover:text-primary">
                                                        {document.title}
                                                    </p>

                                                    {document.description && (
                                                        <Prose
                                                            className="mt-1 text-sm text-muted-foreground"
                                                            html={
                                                                document.description
                                                            }
                                                        />
                                                    )}

                                                    <p className="mt-2 text-xs text-muted-foreground">
                                                        {[
                                                            document.size,
                                                            document.season,
                                                            document.issued_by,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                    </p>
                                                </div>

                                                <Download className="mt-0.5 size-4 shrink-0 text-muted-foreground group-hover:text-primary" />
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ))}
                    </div>
                )}
            </Section>
        </PublicLayout>
    );
}
