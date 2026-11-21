import { Form, Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';

import {
    CheckboxField,
    Field,
    RichTextField,
    SelectField,
    TextField,
    TextareaField,
} from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import news from '@/routes/news';
import type { Option } from '@/types';

type PostForm = {
    id: number;
    slug: string;
    title: string;
    category: string;
    excerpt: string | null;
    body: string;
    cover_image_url: string | null;
    published_at: string | null;
    is_featured: boolean;
    status: 'published' | 'scheduled' | 'draft';
    author: string | null;
};

const states = {
    published: { label: 'On the site', variant: 'default' as const },
    scheduled: { label: 'Scheduled', variant: 'secondary' as const },
    draft: { label: 'Draft', variant: 'outline' as const },
};

export default function NewsForm({
    post,
    categories,
}: {
    post: PostForm | null;
    categories: Option[];
}) {
    useFlashMessages();
    const editing = post !== null;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'News', href: admin.news.index().url },
                {
                    title: editing ? post.title : 'New article',
                    href: admin.news.index().url,
                },
            ]}
        >
            <Head title={editing ? post.title : 'Write an article'} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.news.index()}>
                        <ArrowLeft className="size-4" />
                        Back to news
                    </Link>
                </Button>

                <PageHeader
                    title={editing ? post.title : 'Write an article'}
                    description="Give it a date to put it on the site. Leave the date empty and it stays a draft that only you can see."
                    actions={
                        editing && (
                            <div className="flex items-center gap-2">
                                <Badge variant={states[post.status].variant}>
                                    {states[post.status].label}
                                </Badge>
                                {post.status === 'published' && (
                                    <Button asChild variant="outline" size="sm">
                                        <a
                                            href={news.show(post.slug).url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            View on the site
                                        </a>
                                    </Button>
                                )}
                            </div>
                        )
                    }
                />

                <Form
                    {...(editing
                        ? admin.news.update.form(post.slug)
                        : admin.news.store.form())}
                    className="grid gap-6 lg:grid-cols-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="space-y-6 lg:col-span-2">
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <div className="space-y-5">
                                        <TextField
                                            label="Headline"
                                            name="title"
                                            defaultValue={post?.title}
                                            required
                                            error={errors.title}
                                        />
                                        <TextareaField
                                            label="Standfirst"
                                            name="excerpt"
                                            rows={2}
                                            defaultValue={post?.excerpt}
                                            hint="The line under the headline, and the summary on the news page. One or two sentences."
                                            error={errors.excerpt}
                                        />
                                        <RichTextField
                                            label="Article"
                                            name="body"
                                            defaultValue={post?.body}
                                            error={errors.body}
                                        />
                                    </div>
                                </section>
                            </div>

                            <aside className="space-y-6">
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">
                                        Publishing
                                    </h2>
                                    <div className="mt-5 space-y-5">
                                        <TextField
                                            label="Publish on"
                                            name="published_at"
                                            type="datetime-local"
                                            defaultValue={post?.published_at}
                                            hint="Empty keeps it a draft. A date in the future puts it on the site that morning."
                                            error={errors.published_at}
                                        />
                                        <SelectField
                                            label="Category"
                                            name="category"
                                            options={categories}
                                            defaultValue={
                                                post?.category ?? 'news'
                                            }
                                            error={errors.category}
                                        />
                                        <CheckboxField
                                            label="Feature this article"
                                            name="is_featured"
                                            defaultChecked={post?.is_featured}
                                            description="Featured articles lead the news page."
                                        />
                                    </div>
                                </section>

                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">
                                        Photograph
                                    </h2>
                                    <div className="mt-5 space-y-4">
                                        {post?.cover_image_url && (
                                            <img
                                                src={post.cover_image_url}
                                                alt=""
                                                className="h-32 w-full rounded-md object-cover"
                                            />
                                        )}
                                        <Field
                                            label={
                                                post?.cover_image_url
                                                    ? 'Replace the photograph'
                                                    : 'Add a photograph'
                                            }
                                            htmlFor="cover_image"
                                            hint="Wide, about two to one. Optional."
                                            error={errors.cover_image}
                                        >
                                            <Input
                                                id="cover_image"
                                                name="cover_image"
                                                type="file"
                                                accept="image/jpeg,image/png,image/webp"
                                            />
                                        </Field>
                                    </div>
                                </section>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {editing
                                        ? 'Save changes'
                                        : 'Save the article'}
                                </Button>

                                {editing && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="w-full text-brand-red hover:text-brand-red"
                                        onClick={() => {
                                            if (
                                                window.confirm(
                                                    `Remove "${post.title}" from the news page?`,
                                                )
                                            ) {
                                                router.delete(
                                                    admin.news.destroy(
                                                        post.slug,
                                                    ).url,
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 className="size-4" />
                                        Remove this article
                                    </Button>
                                )}
                            </aside>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
