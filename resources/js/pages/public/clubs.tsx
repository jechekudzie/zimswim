import { Head } from '@inertiajs/react';
import { MapPin } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { ProcessBand } from '@/components/public/process-band';
import { Section } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';

type Province = {
    name: string;
    slug: string;
    code: string | null;
    city: string | null;
    description: string | null;
    clubs: {
        name: string;
        slug: string;
        code: string | null;
        city: string | null;
    }[];
};

export default function Clubs({ provinces }: { provinces: Province[] }) {
    return (
        <PublicLayout
            heroImage={photos.pages.clubs}
            figures={[
                { label: 'Provinces', value: provinces.length },
                {
                    label: 'Affiliated clubs',
                    value: provinces.reduce(
                        (total, province) => total + province.clubs.length,
                        0,
                    ),
                },
            ]}
            title="Clubs and provinces"
            description="Swimming in Zimbabwe runs through eleven provincial bodies and their affiliated clubs. Find the club nearest you and get in touch."
        >
            <Head title="Clubs" />

            <Section>
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {provinces.map((province) => (
                        <article
                            key={province.slug}
                            className="flex flex-col rounded-xl border border-border bg-card p-6"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <h2 className="font-semibold text-balance">
                                    {province.name}
                                </h2>
                                {province.code && (
                                    <Badge
                                        variant="secondary"
                                        className="shrink-0 font-mono text-xs"
                                    >
                                        {province.code}
                                    </Badge>
                                )}
                            </div>

                            {province.city && (
                                <p className="mt-1.5 flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <MapPin className="size-3.5" />
                                    {province.city}
                                </p>
                            )}

                            {province.description && (
                                <p className="mt-3 text-sm text-muted-foreground">
                                    {province.description}
                                </p>
                            )}

                            <div className="mt-5 border-t border-border pt-4">
                                <p className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                    {province.clubs.length === 0
                                        ? 'Clubs'
                                        : `${province.clubs.length} affiliated ${province.clubs.length === 1 ? 'club' : 'clubs'}`}
                                </p>
                                {province.clubs.length === 0 ? (
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Club list being confirmed with the
                                        provincial board.
                                    </p>
                                ) : (
                                    <ul className="mt-2.5 space-y-1.5">
                                        {province.clubs.map((club) => (
                                            <li
                                                key={club.slug}
                                                className="flex items-baseline justify-between gap-3 text-sm"
                                            >
                                                <span>{club.name}</span>
                                                {club.city && (
                                                    <span className="shrink-0 text-xs text-muted-foreground">
                                                        {club.city}
                                                    </span>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </article>
                    ))}
                </div>
            </Section>
            <ProcessBand
                title="How a club joins the national structure"
                lead="Clubs do not affiliate to Zimbabwe Swimming directly. They join through their province, and the province carries them."
                steps={[
                    {
                        title: 'Register with the province',
                        body: 'A new club applies to its provincial body with its committee, its coaches and the pool it trains in.',
                    },
                    {
                        title: 'The province affiliates',
                        body: 'Provinces affiliate to Zimbabwe Swimming for the season and settle what their clubs owe by the deadline they are given.',
                    },
                    {
                        title: 'Register the swimmers',
                        body: 'Every swimmer is registered through the club, with a date of birth and, for a minor, a parent or guardian\u2019s consent.',
                    },
                    {
                        title: 'Enter galas',
                        body: 'Once the club and its province are both in good standing, the club can enter swimmers in any sanctioned gala.',
                    },
                ]}
                footnote="A province that has not met its deadline is not fully affiliated, and its clubs cannot enter swimmers until it is."
            />
        </PublicLayout>
    );
}
