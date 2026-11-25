import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Mail,
    MapPin,
    Phone,
    Waves,
} from 'lucide-react';

import { FlagRule } from '@/components/flag-rule';
import { Prose } from '@/components/prose';
import { Section } from '@/components/public/section';
import { Badge } from '@/components/ui/badge';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import { clubs as clubsIndex } from '@/routes';
import clubRoutes from '@/routes/clubs';

type Club = {
    name: string;
    slug: string;
    code: string | null;
    city: string | null;
    swimmers: number;
    trains_at: string | null;
};

type Officer = {
    name: string;
    role: string;
    email: string | null;
};

export default function Province({
    province,
    officers,
    clubs,
}: {
    province: {
        name: string;
        slug: string;
        code: string | null;
        city: string | null;
        description: string | null;
        email: string | null;
        phone: string | null;
    };
    officers: Officer[];
    clubs: Club[];
}) {
    const swimmers = clubs.reduce((total, club) => total + club.swimmers, 0);

    return (
        <PublicLayout
            heroImage={photos.pages.clubs}
            figures={[
                { label: 'Affiliated clubs', value: clubs.length },
                ...(swimmers > 0
                    ? [{ label: 'Registered swimmers', value: swimmers }]
                    : []),
            ]}
            title={province.name}
            description="The clubs affiliated to this province, and the board that carries them to the national body."
        >
            <Head title={province.name} />

            <Section>
                <Link
                    href={clubsIndex().url}
                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-3.5" />
                    All provinces
                </Link>

                {province.description && (
                    <Prose
                        className="mt-6 max-w-2xl text-muted-foreground"
                        html={province.description}
                    />
                )}

                {(province.email || province.phone || province.city) && (
                    <dl className="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                        {province.city && (
                            <div className="flex items-center gap-2">
                                <MapPin className="size-4 text-primary" />
                                <dd>{province.city}</dd>
                            </div>
                        )}
                        {province.email && (
                            <div className="flex items-center gap-2">
                                <Mail className="size-4 text-primary" />
                                <dd>
                                    <a
                                        className="hover:text-primary"
                                        href={`mailto:${province.email}`}
                                    >
                                        {province.email}
                                    </a>
                                </dd>
                            </div>
                        )}
                        {province.phone && (
                            <div className="flex items-center gap-2">
                                <Phone className="size-4 text-primary" />
                                <dd>{province.phone}</dd>
                            </div>
                        )}
                    </dl>
                )}

                <div className="mt-14">
                    <FlagRule />
                    <h2 className="mt-4 text-2xl font-bold tracking-tight">
                        Affiliated clubs
                    </h2>

                    {clubs.length === 0 ? (
                        <p className="mt-6 rounded-lg border border-dashed border-border p-10 text-center text-muted-foreground">
                            The club list for this province is still being
                            confirmed with the provincial board.
                        </p>
                    ) : (
                        <div className="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {clubs.map((club) => (
                                <Link
                                    key={club.slug}
                                    href={
                                        clubRoutes.show([
                                            province.slug,
                                            club.slug,
                                        ]).url
                                    }
                                    className="group flex flex-col rounded-xl border border-border bg-card p-5 transition-colors hover:border-primary/40 hover:bg-brand-surface/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <h3 className="font-semibold text-balance group-hover:text-primary">
                                            {club.name}
                                        </h3>
                                        {club.code && (
                                            <Badge
                                                variant="secondary"
                                                className="shrink-0 font-mono text-xs"
                                            >
                                                {club.code}
                                            </Badge>
                                        )}
                                    </div>

                                    <dl className="mt-3 space-y-1.5 text-sm text-muted-foreground">
                                        {club.trains_at && (
                                            <div className="flex items-center gap-1.5">
                                                <Waves className="size-3.5 shrink-0" />
                                                <dd>{club.trains_at}</dd>
                                            </div>
                                        )}
                                        {club.city && !club.trains_at && (
                                            <div className="flex items-center gap-1.5">
                                                <MapPin className="size-3.5 shrink-0" />
                                                <dd>{club.city}</dd>
                                            </div>
                                        )}
                                        {club.swimmers > 0 && (
                                            <div>
                                                <dd>
                                                    {club.swimmers} registered{' '}
                                                    {club.swimmers === 1
                                                        ? 'swimmer'
                                                        : 'swimmers'}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>

                                    <p className="mt-4 flex items-center gap-1.5 text-sm font-semibold text-primary">
                                        Club details
                                        <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" />
                                    </p>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

                {officers.length > 0 && (
                    <div className="mt-14">
                        <FlagRule />
                        <h2 className="mt-4 text-2xl font-bold tracking-tight">
                            Provincial executive board
                        </h2>

                        <ul className="mt-6 divide-y divide-border overflow-hidden rounded-xl border border-border">
                            {officers.map((officer) => (
                                <li
                                    key={`${officer.role}-${officer.name}`}
                                    className="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 bg-card p-5"
                                >
                                    <div>
                                        <p className="font-semibold">
                                            {officer.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {officer.role}
                                        </p>
                                    </div>
                                    {officer.email && (
                                        <a
                                            className="text-sm text-muted-foreground hover:text-primary"
                                            href={`mailto:${officer.email}`}
                                        >
                                            {officer.email}
                                        </a>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </Section>
        </PublicLayout>
    );
}
