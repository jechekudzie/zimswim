import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { PageHero } from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import news from '@/routes/news';

type Article = {
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    category: string;
    published_at: string | null;
};

export default function NewsArticle({
    post,
    related,
}: {
    post: Article;
    related: { title: string; slug: string; excerpt: string | null }[];
}) {
    return (
        <PublicLayout overHero>
            <Head title={post.title} />

            <PageHero
                image={photos.palms}
                title={post.title}
                description={post.excerpt ?? undefined}
                breadcrumb={
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="-ml-3 text-white/80 hover:bg-white/10 hover:text-white"
                    >
                        <Link href={news.index()}>
                            <ArrowLeft className="size-4" />
                            All news
                        </Link>
                    </Button>
                }
                figures={[
                    { label: 'Category', value: post.category },
                    ...(post.published_at
                        ? [
                              {
                                  label: 'Published',
                                  value: new Date(
                                      post.published_at,
                                  ).toLocaleDateString('en-GB', {
                                      day: 'numeric',
                                      month: 'long',
                                      year: 'numeric',
                                  }),
                              },
                          ]
                        : []),
                ]}
            />

            {/* The standfirst is in the header, so the article opens on the body. */}
            <article className="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="space-y-5 leading-relaxed">
                    {post.body.split('\n\n').map((paragraph) => (
                        <p key={paragraph.slice(0, 40)}>{paragraph}</p>
                    ))}
                </div>
            </article>

            {related.length > 0 && (
                <section className="border-t border-border/60 bg-brand-surface/40 py-14">
                    <div className="mx-auto w-full max-w-3xl px-4 sm:px-6 lg:px-8">
                        <h2 className="text-sm font-semibold tracking-[0.16em] text-muted-foreground uppercase">
                            More from Zimbabwe Swimming
                        </h2>
                        <ul className="mt-6 space-y-5">
                            {related.map((item) => (
                                <li key={item.slug}>
                                    <Link
                                        href={news.show(item.slug)}
                                        className="group block"
                                    >
                                        <h3 className="font-semibold group-hover:text-primary">
                                            {item.title}
                                        </h3>
                                        {item.excerpt && (
                                            <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                                {item.excerpt}
                                            </p>
                                        )}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                </section>
            )}
        </PublicLayout>
    );
}
