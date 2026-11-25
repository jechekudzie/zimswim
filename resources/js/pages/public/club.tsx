import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Globe,
    Mail,
    MapPin,
    Phone,
    Users,
    Waves,
} from 'lucide-react';

import { FlagRule } from '@/components/flag-rule';
import { Prose } from '@/components/prose';
import { Section } from '@/components/public/section';
import { Badge } from '@/components/ui/badge';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import provinceRoutes from '@/routes/provinces';

type Coach = {
    name: string;
    role: string;
    qualification: string | null;
    email: string | null;
    phone: string | null;
};

export default function Club({
    club,
    province,
    venue,
    coaches,
}: {
    club: {
        name: string;
        slug: string;
        code: string | null;
        city: string | null;
        description: string | null;
        email: string | null;
        phone: string | null;
        website: string | null;
        address: string | null;
        swimmers: number;
        training_times: string | null;
    };
    province: { name: string; slug: string };
    venue: {
        name: string;
        city: string | null;
        pool_length: number | null;
        lanes: number | null;
    } | null;
    coaches: Coach[];
}) {
    const contacts = [
        club.email && {
            icon: Mail,
            label: club.email,
            href: `mailto:${club.email}`,
        },
        club.phone && { icon: Phone, label: club.phone, href: null },
        club.website && {
            icon: Globe,
            label: club.website.replace(/^https?:\/\//, ''),
            href: club.website,
        },
        club.address && { icon: MapPin, label: club.address, href: null },
    ].filter(Boolean) as {
        icon: typeof Mail;
        label: string;
        href: string | null;
    }[];

    return (
        <PublicLayout
            heroImage={photos.pages.clubs}
            figures={[
                ...(club.swimmers > 0
                    ? [{ label: 'Registered swimmers', value: club.swimmers }]
                    : []),
                ...(coaches.length > 0
                    ? [{ label: 'Coaches', value: coaches.length }]
                    : []),
            ]}
            title={club.name}
            description={`Affiliated to ${province.name}.`}
        >
            <Head title={club.name} />

            <Section>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Link
                        href={provinceRoutes.show(province.slug).url}
                        className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                    >
                        <ArrowLeft className="size-3.5" />
                        {province.name}
                    </Link>

                    {club.code && (
                        <Badge
                            variant="secondary"
                            className="font-mono text-xs"
                        >
                            {club.code}
                        </Badge>
                    )}
                </div>

                {club.description && (
                    <Prose
                        className="mt-6 max-w-2xl text-muted-foreground"
                        html={club.description}
                    />
                )}

                <div className="mt-12 grid gap-10 lg:grid-cols-[1.2fr_1fr]">
                    <div>
                        <FlagRule />
                        <h2 className="mt-4 text-2xl font-bold tracking-tight">
                            Coaching
                        </h2>

                        {coaches.length === 0 ? (
                            <p className="mt-5 rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                                This club has not registered its coaches yet.
                                Contact the club directly and they will point
                                you to the right person.
                            </p>
                        ) : (
                            <ul className="mt-5 divide-y divide-border overflow-hidden rounded-xl border border-border">
                                {coaches.map((coach) => (
                                    <li
                                        key={coach.name}
                                        className="bg-card p-5"
                                    >
                                        <div className="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1">
                                            <p className="font-semibold">
                                                {coach.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {coach.role}
                                            </p>
                                        </div>

                                        {coach.qualification && (
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {coach.qualification}
                                            </p>
                                        )}

                                        {(coach.email || coach.phone) && (
                                            <p className="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm">
                                                {coach.email && (
                                                    <a
                                                        className="text-primary hover:underline"
                                                        href={`mailto:${coach.email}`}
                                                    >
                                                        {coach.email}
                                                    </a>
                                                )}
                                                {coach.phone && (
                                                    <span className="text-muted-foreground">
                                                        {coach.phone}
                                                    </span>
                                                )}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}

                        <h2 className="mt-12 text-2xl font-bold tracking-tight">
                            Where they train
                        </h2>

                        {venue === null && !club.training_times ? (
                            <p className="mt-5 rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                                Training details have not been published for
                                this club.
                            </p>
                        ) : (
                            <div className="mt-5 rounded-xl border border-border bg-card p-6">
                                {venue && (
                                    <div className="flex items-start gap-3">
                                        <Waves className="mt-0.5 size-5 shrink-0 text-primary" />
                                        <div>
                                            <p className="font-semibold">
                                                {venue.name}
                                            </p>
                                            <p className="mt-0.5 text-sm text-muted-foreground">
                                                {[
                                                    venue.city,
                                                    venue.pool_length &&
                                                        `${venue.pool_length}m`,
                                                    venue.lanes &&
                                                        `${venue.lanes} lanes`,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {club.training_times && (
                                    <Prose
                                        className={`text-sm text-muted-foreground ${venue ? 'mt-5 border-t border-border pt-5' : ''}`}
                                        html={club.training_times}
                                    />
                                )}
                            </div>
                        )}
                    </div>

                    <div>
                        <FlagRule />
                        <h2 className="mt-4 text-2xl font-bold tracking-tight">
                            Get in touch
                        </h2>

                        {contacts.length === 0 ? (
                            <p className="mt-5 rounded-lg border border-dashed border-border p-8 text-sm text-muted-foreground">
                                No contact details on file. Your provincial
                                board can put you in touch.
                            </p>
                        ) : (
                            <ul className="mt-5 space-y-3 rounded-xl border border-border bg-card p-6">
                                {contacts.map((contact) => (
                                    <li
                                        key={contact.label}
                                        className="flex items-start gap-3 text-sm"
                                    >
                                        <contact.icon className="mt-0.5 size-4 shrink-0 text-primary" />
                                        {contact.href ? (
                                            <a
                                                className="break-all hover:text-primary"
                                                href={contact.href}
                                            >
                                                {contact.label}
                                            </a>
                                        ) : (
                                            <span>{contact.label}</span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}

                        {club.swimmers > 0 && (
                            <div className="mt-6 flex items-start gap-3 rounded-xl border border-border bg-brand-surface/50 p-6">
                                <Users className="mt-0.5 size-5 shrink-0 text-primary" />
                                <div>
                                    <p className="text-2xl font-bold tracking-tight">
                                        {club.swimmers}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        registered{' '}
                                        {club.swimmers === 1
                                            ? 'swimmer'
                                            : 'swimmers'}{' '}
                                        this season
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </Section>
        </PublicLayout>
    );
}
