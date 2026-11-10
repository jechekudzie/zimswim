import { Head, Link } from '@inertiajs/react';

import { Section } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import news from '@/routes/news';
import type { Paginated } from '@/types';

type Article = {
    title: string;
    slug: string;
    excerpt: string | null;
    category: string;
    published_at: string | null;
};

export default function News({ posts }: { posts: Paginated<Article> }) {
    return (
        <PublicLayout
            heroImage={photos.pages.news}
            figures={[{ label: 'Published notices', value: posts.total }]}
            title="News and notices"
            description="Announcements, gala notices and guidance from Zimbabwe Swimming and the provinces."
        >
            <Head title="News" />

            <Section>
                {posts.data.length === 0 ? (
                    <p className="rounded-lg border border-dashed border-border p-12 text-center text-muted-foreground">
                        Nothing has been published yet.
                    </p>
                ) : (
                    <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        {posts.data.map((article) => (
                            <Link
                                key={article.slug}
                                href={news.show(article.slug)}
                                className="group flex flex-col"
                            >
                                <div className="h-1.5 w-full rounded-full bg-gradient-to-r from-brand-green via-brand-gold to-brand-red" />
                                <p className="mt-5 text-xs font-semibold tracking-[0.14em] text-primary uppercase">
                                    {article.category}
                                </p>
                                <h2 className="mt-2 text-lg font-semibold text-balance group-hover:text-primary">
                                    {article.title}
                                </h2>
                                {article.excerpt && (
                                    <p className="mt-3 line-clamp-3 text-sm text-muted-foreground">
                                        {article.excerpt}
                                    </p>
                                )}
                                {article.published_at && (
                                    <time
                                        className="mt-4 text-xs text-muted-foreground"
                                        dateTime={article.published_at}
                                    >
                                        {new Date(
                                            article.published_at,
                                        ).toLocaleDateString('en-GB', {
                                            day: 'numeric',
                                            month: 'long',
                                            year: 'numeric',
                                        })}
                                    </time>
                                )}
                            </Link>
                        ))}
                    </div>
                )}
            </Section>
        </PublicLayout>
    );
}
