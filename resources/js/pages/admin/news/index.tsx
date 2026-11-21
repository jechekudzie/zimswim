import { Head, Link, router } from '@inertiajs/react';
import { Newspaper, Pencil, Plus, Star } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types';

type PostRow = {
    id: number;
    slug: string;
    title: string;
    category: string;
    excerpt: string | null;
    author: string | null;
    organization: string | null;
    published_at: string | null;
    status: 'published' | 'scheduled' | 'draft';
    is_featured: boolean;
};

/**
 * What the date on an article means, said plainly.
 *
 * A draft has no date, a scheduled article has one that has not arrived, and
 * everything else is on the site now. Spelling that out beats a tick box
 * whose meaning a reader has to remember.
 */
const states = {
    published: { label: 'On the site', variant: 'default' as const },
    scheduled: { label: 'Scheduled', variant: 'secondary' as const },
    draft: { label: 'Draft', variant: 'outline' as const },
};

export default function NewsIndex({
    posts,
    filters,
    statuses,
}: {
    posts: Paginated<PostRow>;
    filters: { search: string | null; status: string | null };
    statuses: Option[];
}) {
    useFlashMessages();

    const show = (status: string | null) =>
        router.get(admin.news.index().url, status ? { status } : {}, {
            preserveScroll: true,
        });

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'News', href: admin.news.index().url },
            ]}
        >
            <Head title="News" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="News"
                    description="Articles on the public news page. An article appears on the date you give it, so one can be written now and published on the morning of a gala."
                    actions={
                        <Button asChild>
                            <Link href={admin.news.create()}>
                                <Plus className="size-4" />
                                Write an article
                            </Link>
                        </Button>
                    }
                />

                <div className="flex flex-wrap gap-1.5">
                    <Button
                        size="sm"
                        variant={filters.status ? 'outline' : 'default'}
                        onClick={() => show(null)}
                    >
                        Everything
                    </Button>
                    {statuses.map((status) => (
                        <Button
                            key={status.value}
                            size="sm"
                            variant={
                                filters.status === status.value
                                    ? 'default'
                                    : 'outline'
                            }
                            onClick={() => show(String(status.value))}
                        >
                            {status.label}
                        </Button>
                    ))}
                </div>

                {posts.data.length === 0 ? (
                    <EmptyState
                        icon={Newspaper}
                        title="Nothing written yet"
                        description="Season openings, gala reports, calls for officials — anything the federation wants swimmers and clubs to read."
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="w-full min-w-[720px] text-sm">
                                <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Article
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Written by
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Date
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {posts.data.map((post) => (
                                        <tr
                                            key={post.id}
                                            className="border-b border-border/60 bg-card last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <p className="flex items-center gap-1.5 font-medium">
                                                    {post.is_featured && (
                                                        <Star
                                                            className="size-3.5 shrink-0 text-brand-gold"
                                                            aria-label="Featured"
                                                        />
                                                    )}
                                                    {post.title}
                                                </p>
                                                {post.excerpt && (
                                                    <p className="mt-0.5 line-clamp-1 max-w-md text-xs text-muted-foreground">
                                                        {post.excerpt}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {post.author ?? '—'}
                                                {post.organization && (
                                                    <span className="block text-xs">
                                                        {post.organization}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground tabular-nums">
                                                {post.published_at ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        states[post.status]
                                                            .variant
                                                    }
                                                >
                                                    {states[post.status].label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="ghost"
                                                >
                                                    <Link
                                                        href={admin.news.edit(
                                                            post.slug,
                                                        )}
                                                    >
                                                        <Pencil className="size-4" />
                                                        Edit
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <Pagination meta={posts} />
                    </>
                )}
            </div>
        </AppLayout>
    );
}
