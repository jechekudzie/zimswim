import { Form, Head, router, useForm } from '@inertiajs/react';
import { ImageUp, RotateCcw, Save } from 'lucide-react';
import { useState } from 'react';

import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { RichTextEditor } from '@/components/rich-text-editor';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type Block = {
    key: string;
    label: string;
    type: 'text' | 'rich' | 'image';
    hint: string | null;
    original: string;
    value: string;
    image_url: string | null;
    changed: boolean;
    changed_by: string | null;
    changed_on: string | null;
};

type Group = { key: string; label: string; blocks: Block[] };

/**
 * One photograph on a page.
 *
 * Sits outside the wording form because a file upload is its own request:
 * putting it inside would mean a half-finished page of edits had to be
 * submitted before a picture could be changed.
 */
function ImageBlock({ block }: { block: Block }) {
    return (
        <div className="grid gap-3 rounded-lg border border-border bg-muted/30 p-4">
            <div className="flex flex-wrap items-baseline justify-between gap-2">
                <Label>{block.label}</Label>
                {block.changed && <Badge variant="secondary">Changed</Badge>}
            </div>

            {block.image_url && (
                <img
                    src={block.image_url}
                    alt=""
                    className="h-32 w-full rounded-md object-cover"
                />
            )}

            {block.hint && (
                <p className="text-xs text-muted-foreground">{block.hint}</p>
            )}

            <div className="flex flex-wrap items-center gap-2">
                <Form
                    {...admin.content.image.form()}
                    options={{ preserveScroll: true }}
                    className="flex flex-wrap items-center gap-2"
                >
                    {({ errors, processing }) => (
                        <>
                            <input type="hidden" name="key" value={block.key} />
                            <Input
                                type="file"
                                name="image"
                                accept="image/jpeg,image/png,image/webp"
                                className="max-w-xs"
                                required
                            />
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                            >
                                <ImageUp className="size-4" />
                                {processing ? 'Uploading…' : 'Replace'}
                            </Button>
                            <InputError message={errors.image} />
                        </>
                    )}
                </Form>

                {block.image_url && (
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() =>
                            router.delete(admin.content.image.reset().url, {
                                data: { key: block.key },
                                preserveScroll: true,
                            })
                        }
                    >
                        <RotateCcw className="size-4" />
                        Use the original
                    </Button>
                )}
            </div>
        </div>
    );
}

export default function ContentIndex({
    page,
    pages,
    groups,
}: {
    page: string;
    pages: Option[];
    groups: Group[];
}) {
    useFlashMessages();

    const wording = groups.flatMap((group) =>
        group.blocks.filter((block) => block.type !== 'image'),
    );

    const form = useForm({
        page,
        blocks: Object.fromEntries(
            wording.map((block) => [block.key, block.value]),
        ) as Record<string, string>,
    });

    const [open, setOpen] = useState<string | null>(null);

    function save() {
        form.put(admin.content.update().url, { preserveScroll: true });
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Website text', href: admin.content.index().url },
            ]}
        >
            <Head title="Website text" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Website text"
                    description="What the public pages say, and the photographs on them. Empty a box and save to put the original wording back."
                    actions={
                        <Button onClick={save} disabled={form.processing}>
                            <Save className="size-4" />
                            {form.processing ? 'Saving…' : 'Save changes'}
                        </Button>
                    }
                />

                {pages.length > 1 && (
                    <div className="flex flex-wrap gap-1.5">
                        {pages.map((item) => (
                            <Button
                                key={item.value}
                                size="sm"
                                variant={
                                    item.value === page ? 'default' : 'outline'
                                }
                                onClick={() =>
                                    router.get(
                                        admin.content.index().url,
                                        { page: item.value },
                                        { preserveState: false },
                                    )
                                }
                            >
                                {item.label}
                            </Button>
                        ))}
                    </div>
                )}

                <div className="grid gap-5 xl:grid-cols-2">
                    {groups.map((group) => (
                        <section
                            key={group.key}
                            className="h-fit rounded-xl border border-border bg-card"
                        >
                            <header className="border-b border-border/60 px-5 py-3">
                                <h2 className="font-semibold">{group.label}</h2>
                            </header>

                            <div className="grid gap-5 p-5">
                                {group.blocks.map((block) =>
                                    block.type === 'image' ? (
                                        <ImageBlock
                                            key={block.key}
                                            block={block}
                                        />
                                    ) : (
                                        <div
                                            key={block.key}
                                            className="grid gap-2"
                                        >
                                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                                <Label htmlFor={block.key}>
                                                    {block.label}
                                                </Label>
                                                {block.changed && (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setOpen(
                                                                open ===
                                                                    block.key
                                                                    ? null
                                                                    : block.key,
                                                            )
                                                        }
                                                        className="text-xs text-muted-foreground underline underline-offset-2 hover:text-foreground"
                                                    >
                                                        {block.changed_by
                                                            ? `Changed by ${block.changed_by}`
                                                            : 'Changed'}
                                                    </button>
                                                )}
                                            </div>

                                            {block.type === 'rich' ? (
                                                <RichTextEditor
                                                    id={block.key}
                                                    label={block.label}
                                                    value={
                                                        form.data.blocks[
                                                            block.key
                                                        ] ?? ''
                                                    }
                                                    onChange={(html) =>
                                                        form.setData('blocks', {
                                                            ...form.data.blocks,
                                                            [block.key]: html,
                                                        })
                                                    }
                                                />
                                            ) : (
                                                <Input
                                                    id={block.key}
                                                    value={
                                                        form.data.blocks[
                                                            block.key
                                                        ] ?? ''
                                                    }
                                                    onChange={(event) =>
                                                        form.setData('blocks', {
                                                            ...form.data.blocks,
                                                            [block.key]:
                                                                event.target
                                                                    .value,
                                                        })
                                                    }
                                                />
                                            )}

                                            {block.hint && (
                                                <p className="text-xs text-muted-foreground">
                                                    {block.hint}
                                                </p>
                                            )}

                                            {open === block.key && (
                                                <p className="rounded-md bg-muted/60 px-3 py-2 text-xs leading-relaxed text-muted-foreground">
                                                    <span className="font-medium text-foreground">
                                                        Originally:
                                                    </span>{' '}
                                                    {block.original}
                                                    {block.changed_on &&
                                                        ` · changed ${block.changed_on}`}
                                                </p>
                                            )}

                                            <InputError
                                                message={
                                                    form.errors[
                                                        `blocks.${block.key}` as keyof typeof form.errors
                                                    ] as string | undefined
                                                }
                                            />
                                        </div>
                                    ),
                                )}
                            </div>
                        </section>
                    ))}
                </div>

                <div>
                    <Button onClick={save} disabled={form.processing}>
                        <Save className="size-4" />
                        {form.processing ? 'Saving…' : 'Save changes'}
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
