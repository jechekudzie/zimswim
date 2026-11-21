import { Form, Head, Link, router } from '@inertiajs/react';
import { Download, FileText, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { EmptyState } from '@/components/admin/empty-state';
import {
    CheckboxField,
    Field,
    RichTextField,
    SelectField,
    TextField,
} from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types';

type DocumentRow = {
    id: number;
    slug: string;
    title: string;
    category: string;
    description: string | null;
    size: string;
    season: string | null;
    organization: string | null;
    is_public: boolean;
    uploaded_on: string | null;
};

export default function DocumentsIndex({
    documents,
    filters,
    categories,
    seasons,
}: {
    documents: Paginated<DocumentRow>;
    filters: { search: string | null; category: string | null };
    categories: Option[];
    seasons: Option<number>[];
}) {
    useFlashMessages();
    const [adding, setAdding] = useState(false);

    const label = (category: string) =>
        categories.find((option) => option.value === category)?.label ??
        category;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Documents', href: admin.documents.index().url },
            ]}
        >
            <Head title="Documents" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Documents"
                    description="The handbook, the rules, the forms clubs fill in and the minutes. Anything marked public appears in the library on the website."
                    actions={
                        <Button onClick={() => setAdding(!adding)}>
                            <Plus className="size-4" />
                            Upload a document
                        </Button>
                    }
                />

                {adding && (
                    <section className="rounded-xl border border-border bg-card p-6">
                        <h2 className="font-semibold">A new document</h2>
                        <Form
                            {...admin.documents.store.form()}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setAdding(false)}
                            className="mt-5 grid gap-5 lg:grid-cols-2"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <TextField
                                        label="Title"
                                        name="title"
                                        required
                                        error={errors.title}
                                    />
                                    <SelectField
                                        label="Filed under"
                                        name="category"
                                        options={categories}
                                        defaultValue="general"
                                        error={errors.category}
                                    />
                                    <div className="lg:col-span-2">
                                        <RichTextField
                                            label="What it is"
                                            name="description"
                                            hint="Shown under the title in the library."
                                            error={errors.description}
                                        />
                                    </div>
                                    <Field
                                        label="File"
                                        htmlFor="file"
                                        hint="PDF, Word, Excel or CSV, up to 20MB."
                                        error={errors.file}
                                    >
                                        <Input
                                            id="file"
                                            name="file"
                                            type="file"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.csv"
                                            required
                                        />
                                    </Field>
                                    <SelectField
                                        label="Season"
                                        name="season_id"
                                        options={seasons}
                                        placeholder="Not tied to a season"
                                        error={errors.season_id}
                                    />
                                    <CheckboxField
                                        label="Publish in the library"
                                        name="is_public"
                                        defaultChecked
                                        description="Leave this off while a document is still being drafted."
                                    />
                                    <div className="flex items-end gap-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Upload
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() => setAdding(false)}
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </section>
                )}

                <div className="flex flex-wrap gap-1.5">
                    <Button
                        size="sm"
                        variant={filters.category ? 'outline' : 'default'}
                        onClick={() =>
                            router.get(
                                admin.documents.index().url,
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Everything
                    </Button>
                    {categories.map((category) => (
                        <Button
                            key={category.value}
                            size="sm"
                            variant={
                                filters.category === category.value
                                    ? 'default'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    admin.documents.index().url,
                                    { category: category.value },
                                    { preserveScroll: true },
                                )
                            }
                        >
                            {category.label}
                        </Button>
                    ))}
                </div>

                {documents.data.length === 0 ? (
                    <EmptyState
                        icon={FileText}
                        title="Nothing in the library yet"
                        description="Upload the handbook, the rules and the forms clubs need, and they will be there for anybody who asks for them."
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="w-full min-w-[760px] text-sm">
                                <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Document
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Filed under
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Season
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            In the library
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {documents.data.map((document) => (
                                        <tr
                                            key={document.id}
                                            className="border-b border-border/60 bg-card last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <p className="font-medium">
                                                    {document.title}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {document.size}
                                                    {document.uploaded_on &&
                                                        ` · added ${document.uploaded_on}`}
                                                    {document.organization &&
                                                        ` · ${document.organization}`}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {label(document.category)}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {document.season ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {document.is_public ? (
                                                    <Badge variant="secondary">
                                                        Published
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Held back
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="ghost"
                                                    >
                                                        <a
                                                            href={
                                                                admin.documents.download(
                                                                    document.slug,
                                                                ).url
                                                            }
                                                        >
                                                            <Download className="size-4" />
                                                        </a>
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        className="text-brand-red hover:text-brand-red"
                                                        onClick={() => {
                                                            if (
                                                                window.confirm(
                                                                    `Remove "${document.title}" from the library? The file is deleted too.`,
                                                                )
                                                            ) {
                                                                router.delete(
                                                                    admin.documents.destroy(
                                                                        document.slug,
                                                                    ).url,
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                );
                                                            }
                                                        }}
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <Pagination meta={documents} />

                        <p className="text-xs text-muted-foreground">
                            A gala's results file is attached to the gala
                            itself, and is managed from{' '}
                            <Link
                                href={admin.galas.index()}
                                className="underline underline-offset-2"
                            >
                                its own screen
                            </Link>
                            .
                        </p>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
