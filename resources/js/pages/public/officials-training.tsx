import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, MapPin, Monitor } from 'lucide-react';

import { FlagRule } from '@/components/flag-rule';
import { Section } from '@/components/public/section';
import { Badge } from '@/components/ui/badge';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import officialRoutes from '@/routes/officials';

export type TrainingSummary = {
    title: string;
    slug: string;
    kind: string;
    kind_label: string;
    category: string | null;
    host: string;
    venue: string | null;
    city: string | null;
    is_online: boolean;
    held_on: string;
    ends_on: string | null;
    starts_at: string | null;
    ends_at: string | null;
    registration_closes_at: string | null;
    places_left: number | null;
    is_open: boolean;
    has_finished: boolean;
    attending: number;
};

export function held(training: TrainingSummary): string {
    const start = new Date(`${training.held_on}T00:00:00`);
    const format: Intl.DateTimeFormatOptions = {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    };

    if (!training.ends_on || training.ends_on === training.held_on) {
        return start.toLocaleDateString('en-GB', format);
    }

    const end = new Date(`${training.ends_on}T00:00:00`);

    return `${start.toLocaleDateString('en-GB', { day: 'numeric', month: 'long' })} to ${end.toLocaleDateString('en-GB', format)}`;
}

export function clockTime(value: string | null): string | null {
    return value ? value.slice(0, 5) : null;
}

function Card({ training }: { training: TrainingSummary }) {
    const start = clockTime(training.starts_at);

    return (
        <Link
            href={officialRoutes.training.show(training.slug).url}
            className="group flex flex-col rounded-xl border border-border bg-card p-6 transition-colors hover:border-primary/40 hover:bg-brand-surface/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            <div className="flex flex-wrap items-center gap-2">
                <Badge variant="secondary" className="text-xs">
                    {training.kind_label}
                </Badge>
                {training.category && (
                    <Badge variant="outline" className="text-xs font-normal">
                        {training.category}
                    </Badge>
                )}
                {training.places_left !== null && !training.has_finished && (
                    <Badge
                        variant={
                            training.places_left === 0
                                ? 'destructive'
                                : 'outline'
                        }
                        className="text-xs font-normal"
                    >
                        {training.places_left === 0
                            ? 'Full'
                            : `${training.places_left} places left`}
                    </Badge>
                )}
            </div>

            <h3 className="mt-3 font-semibold text-balance group-hover:text-primary">
                {training.title}
            </h3>

            <dl className="mt-3 space-y-1.5 text-sm text-muted-foreground">
                <div className="flex items-center gap-2">
                    <CalendarDays className="size-3.5 shrink-0" />
                    <dd>
                        {held(training)}
                        {start && ` · ${start}`}
                    </dd>
                </div>
                {(training.is_online || training.venue || training.city) && (
                    <div className="flex items-center gap-2">
                        {training.is_online ? (
                            <Monitor className="size-3.5 shrink-0" />
                        ) : (
                            <MapPin className="size-3.5 shrink-0" />
                        )}
                        <dd>
                            {training.is_online
                                ? 'Online'
                                : [training.venue, training.city]
                                      .filter(Boolean)
                                      .join(' · ')}
                        </dd>
                    </div>
                )}
            </dl>

            <p className="mt-4 text-xs text-muted-foreground">
                Run by {training.host}
            </p>

            <p className="mt-4 flex items-center gap-1.5 text-sm font-semibold text-primary">
                {training.has_finished
                    ? 'What was covered'
                    : training.is_open
                      ? 'Put your name down'
                      : 'Details'}
                <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" />
            </p>
        </Link>
    );
}

export default function OfficialsTraining({
    upcoming,
    recent,
    kinds,
}: {
    upcoming: TrainingSummary[];
    recent: TrainingSummary[];
    kinds: { value: string; label: string; description: string }[];
}) {
    return (
        <PublicLayout
            heroImage={photos.pages.officials}
            figures={[{ label: 'Coming up', value: upcoming.length }]}
            title="Clinics and exams"
            description="Every clinic, exam sitting and refresher on the officials' calendar. Anybody may attend a clinic — no accreditation, no account, no invitation."
        >
            <Head title="Clinics and exams" />

            <Section>
                {upcoming.length === 0 ? (
                    <p className="rounded-lg border border-dashed border-border p-12 text-center text-muted-foreground">
                        Nothing is on the calendar at the moment. Provinces
                        schedule clinics through the season — check back, or ask
                        your provincial board when the next one runs.
                    </p>
                ) : (
                    <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        {upcoming.map((training) => (
                            <Card key={training.slug} training={training} />
                        ))}
                    </div>
                )}

                <div className="mt-16">
                    <FlagRule />
                    <h2 className="mt-4 text-2xl font-bold tracking-tight">
                        What each one is
                    </h2>

                    <dl className="mt-6 grid gap-5 md:grid-cols-3">
                        {kinds.map((kind) => (
                            <div
                                key={kind.value}
                                className="rounded-xl border border-border bg-card p-5"
                            >
                                <dt className="font-semibold">{kind.label}</dt>
                                <dd className="mt-2 text-sm text-muted-foreground">
                                    {kind.description}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>

                {recent.length > 0 && (
                    <div className="mt-16">
                        <FlagRule />
                        <h2 className="mt-4 text-2xl font-bold tracking-tight">
                            Recently held
                        </h2>
                        <p className="mt-2 max-w-2xl text-muted-foreground">
                            If you missed one of these, the same clinic is
                            usually run again. Ask the host.
                        </p>

                        <div className="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                            {recent.map((training) => (
                                <Card key={training.slug} training={training} />
                            ))}
                        </div>
                    </div>
                )}
            </Section>
        </PublicLayout>
    );
}
