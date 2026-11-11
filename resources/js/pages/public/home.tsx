import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    CalendarDays,
    ClipboardCheck,
    MapPin,
    Timer,
    Trophy,
    Users,
    Waves,
} from 'lucide-react';

import { FlagRule } from '@/components/flag-rule';
import { PhotoPanel } from '@/components/public/photo';
import { CountUp, Reveal } from '@/components/public/reveal';
import { Section } from '@/components/public/section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import { calendar, clubs, officials, records, venues } from '@/routes';
import galas from '@/routes/galas';
import news from '@/routes/news';
import type { SeasonSummary } from '@/types';

type UpcomingGala = {
    name: string;
    slug: string;
    level: string;
    starts_on: string;
    ends_on: string;
    venue: string | null;
    city: string | null;
    host: string;
};

type Featured = UpcomingGala & {
    days_away: number;
    eligible_ages: string | null;
    entries_close_at: string | null;
    description: string | null;
};

type HomeProps = {
    season: SeasonSummary | null;
    featured: Featured | null;
    upcomingGalas: UpcomingGala[];
    news: {
        title: string;
        slug: string;
        excerpt: string | null;
        category: string;
        published_at: string | null;
    }[];
    provinces: { name: string; slug: string; city: string | null }[];
    venues: {
        name: string;
        slug: string;
        city: string | null;
        pool_length: number | null;
        lanes: number | null;
    }[];
    officialsEntry: {
        category: string;
        minimum_age: number;
        learner_sessions: number;
    };
    sponsors: { name: string; slug: string }[];
    stats: {
        provinces: number;
        clubs: number;
        swimmers: number;
        officials: number;
        records: number;
        galas: number;
    };
};

function shortDate(value: string) {
    return new Date(value).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
}

function dateRange(starts: string, ends: string) {
    return starts === ends
        ? `${shortDate(starts)} ${new Date(ends).getFullYear()}`
        : `${shortDate(starts)} – ${shortDate(ends)} ${new Date(ends).getFullYear()}`;
}

export default function Home({
    season,
    featured,
    upcomingGalas,
    news: articles,
    provinces,
    venues: nationalVenues,
    officialsEntry,
    sponsors,
    stats,
}: HomeProps) {
    const figures = [
        { label: 'Provinces', value: stats.provinces, icon: MapPin },
        { label: 'Affiliated clubs', value: stats.clubs, icon: Users },
        { label: 'Registered swimmers', value: stats.swimmers, icon: Waves },
        {
            label: 'Technical officials',
            value: stats.officials,
            icon: ClipboardCheck,
        },
    ];

    const pathways = [
        {
            title: 'Swim for a club',
            body: 'Find an affiliated club near you, register once, and swim the whole season under one ZimSwim number.',
            href: clubs(),
            cta: 'Find a club',
            photo: photos.squad,
            icon: Waves,
        },
        {
            title: 'Become an official',
            body: 'Start as a timekeeper, work your learner sessions on the pool deck, and qualify nationally.',
            href: officials(),
            cta: 'See the pathway',
            photo: photos.start,
            icon: ClipboardCheck,
        },
        {
            title: 'Find a pool',
            body: 'Every pool that can host a gala, graded by the level of competition it can carry.',
            href: venues(),
            cta: 'Venue register',
            photo: photos.palms,
            icon: MapPin,
        },
    ];

    /*
     * The three things that decide whether a reader thinks they are eligible
     * to officiate: how old you have to be, how much deck time it costs, and
     * whether the accreditation travels. The first two come from the
     * certification standards, so the invitation cannot drift from the rules
     * the register enforces.
     */
    const officiatingFacts = [
        {
            icon: Timer,
            term: `From age ${officialsEntry.minimum_age}`,
            detail: `The youngest a ${officialsEntry.category.toLowerCase()} can be accredited. There is no upper age, and no requirement ever to have raced.`,
        },
        {
            icon: ClipboardCheck,
            term: `${officialsEntry.learner_sessions} learner sessions`,
            detail: 'Worked poolside next to a qualified official, at galas your club is already attending, before you sit the exam.',
        },
        {
            icon: Award,
            term: 'One national standard',
            detail: 'The same requirements in every province, and the accreditation is recognised countrywide once you hold it.',
        },
    ];

    const rotation = [photos.field, photos.palms, photos.marquees, photos.dusk];
    const podiums = [
        photos.podiumChampions,
        photos.podiumYouth,
        photos.podiumTeens,
    ];

    return (
        <PublicLayout overHero>
            <Head title="Zimbabwe Swimming" />

            {/*
             * The start, full bleed and running under the navigation. It is
             * the most characteristic second in the sport: eight swimmers in
             * the air, nothing decided yet.
             */}
            <section className="on-dark relative isolate flex min-h-[62vh] items-end overflow-hidden bg-brand-deeper sm:min-h-[70vh] lg:min-h-svh">
                {/*
                 * One photograph at every width, so the page opens on the
                 * same picture whatever you are holding. The banner is three
                 * to one and a phone asks it to fill a tall box, so the crop
                 * is pulled back towards the swimmer rather than left in the
                 * centre, where a portrait window finds only water.
                 */}
                <img
                    src={photos.heroBanner.src}
                    srcSet={photos.heroBanner.srcSet}
                    // A phone sees about a seventh of a three-to-one
                    // photograph, so the browser is magnifying whatever it
                    // picks. `100vw` would have it choose the 1100px file
                    // and upscale it; the larger one is 80KB more and the
                    // difference is the whole first impression.
                    sizes="(max-width: 767px) 2200px, 100vw"
                    alt={photos.heroBanner.alt}
                    fetchPriority="high"
                    decoding="async"
                    className="animate-hero-drift absolute inset-0 -z-20 size-full object-cover object-[76%_42%] md:object-[72%_45%]"
                />
                {/*
                 * The banner is already deep water on the left, which is where
                 * the heading sits, so the washes here are light: enough to
                 * hold white type and the figures, not enough to flatten the
                 * swimmer the picture was taken for.
                 */}
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-gradient-to-t from-brand-deeper/65 via-brand-deeper/10 to-transparent"
                />
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-gradient-to-r from-brand-deeper/92 via-brand-deeper/45 to-brand-deeper/5"
                />

                <div className="mx-auto flex w-full max-w-7xl flex-col justify-end px-4 pt-28 pb-16 sm:px-6 lg:px-8 lg:pt-36">
                    <div className="max-w-3xl">
                        <span className="animate-lane-rise inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-1.5 text-xs font-semibold tracking-wide text-white backdrop-blur">
                            <span className="size-1.5 rounded-full bg-brand-gold" />
                            {season?.name ?? 'Season'} · entries open
                        </span>

                        <h1
                            className="animate-lane-rise mt-7 text-5xl leading-[0.95] font-bold tracking-tight text-balance text-white drop-shadow-sm sm:text-6xl lg:text-7xl"
                            style={{ animationDelay: '70ms' }}
                        >
                            From the first splash
                            <span className="block text-brand-gold">
                                to the Olympic stage
                            </span>
                        </h1>

                        <p
                            className="animate-lane-rise mt-7 max-w-xl text-lg leading-relaxed text-white/80"
                            style={{ animationDelay: '150ms' }}
                        >
                            One national home for the calendar, clubs, pools,
                            the officials pathway and every ratified Zimbabwean
                            record.
                        </p>

                        <div
                            className="animate-lane-rise mt-10 flex flex-wrap gap-3"
                            style={{ animationDelay: '230ms' }}
                        >
                            <Button asChild size="lg">
                                <Link href={calendar()}>
                                    See the season calendar
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                asChild
                                size="lg"
                                variant="outline"
                                className="border-white/30 bg-white/5 text-white backdrop-blur hover:bg-white/15 hover:text-white"
                            >
                                <Link href={clubs()}>Find a club</Link>
                            </Button>
                            <Button
                                asChild
                                size="lg"
                                variant="outline"
                                className="border-white/30 bg-white/5 text-white backdrop-blur hover:bg-white/15 hover:text-white"
                            >
                                <Link href={officials()}>
                                    Officials Academy
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <dl className="mt-16 grid grid-cols-2 gap-x-8 gap-y-8 border-t border-white/20 pt-10 lg:grid-cols-4">
                        {figures.map((figure) => (
                            <div key={figure.label}>
                                <dt className="flex items-center gap-2 text-xs font-semibold tracking-[0.14em] text-white/65 uppercase">
                                    <figure.icon className="size-3.5" />
                                    {figure.label}
                                </dt>
                                <dd className="mt-2 text-4xl font-bold tracking-tight text-white">
                                    <CountUp value={figure.value} />
                                </dd>
                            </div>
                        ))}
                    </dl>

                    <p className="mt-10 text-sm font-semibold tracking-[0.2em] text-brand-gold uppercase">
                        Same Waters · Brighter Futures
                    </p>
                </div>
            </section>

            {/* Featured national gala */}
            {featured && (
                <Section innerClassName="py-16 lg:py-20">
                    <div className="grid overflow-hidden rounded-2xl border border-border bg-card lg:grid-cols-2">
                        <PhotoPanel
                            photo={photos.field}
                            className="min-h-[300px] lg:min-h-full"
                            overlay="soft"
                        />

                        <div className="p-8 lg:p-12">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge>{featured.level}</Badge>
                                {featured.days_away > 0 && (
                                    <Badge
                                        variant="outline"
                                        className="gap-1.5 border-brand-gold/50"
                                    >
                                        <Timer className="size-3" />
                                        {featured.days_away} days away
                                    </Badge>
                                )}
                            </div>

                            <p className="mt-6 text-xs font-semibold tracking-[0.16em] text-primary uppercase">
                                Next on the national calendar
                            </p>
                            <h2 className="mt-2 text-3xl font-bold tracking-tight text-balance sm:text-4xl">
                                {featured.name}
                            </h2>

                            <dl className="mt-8 grid grid-cols-2 gap-x-6 gap-y-5 border-y border-border py-6 text-sm">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Dates
                                    </dt>
                                    <dd className="mt-0.5 font-semibold">
                                        {dateRange(
                                            featured.starts_on,
                                            featured.ends_on,
                                        )}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Venue
                                    </dt>
                                    <dd className="mt-0.5 font-semibold">
                                        {featured.venue ?? 'To be confirmed'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Eligible ages
                                    </dt>
                                    <dd className="mt-0.5 font-semibold">
                                        {featured.eligible_ages ?? 'See rules'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Entries close
                                    </dt>
                                    <dd className="mt-0.5 font-semibold">
                                        {featured.entries_close_at ??
                                            'To be advised'}
                                    </dd>
                                </div>
                            </dl>

                            <Button asChild size="lg" className="mt-8">
                                <Link href={galas.show(featured.slug)}>
                                    Gala details and programme
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </Section>
            )}

            {/* Upcoming */}
            <Section tone="surface">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <FlagRule />
                        <h2 className="mt-4 text-3xl font-bold tracking-tight">
                            What is coming up
                        </h2>
                        <p className="mt-2 max-w-2xl text-muted-foreground">
                            Only meets on the official provincial and national
                            calendars are recognised by Zimbabwe Swimming.
                        </p>
                    </div>
                    <Button asChild variant="ghost">
                        <Link href={calendar()}>
                            Full calendar
                            <ArrowRight className="size-4" />
                        </Link>
                    </Button>
                </div>

                {upcomingGalas.length === 0 ? (
                    <p className="mt-10 rounded-xl border border-dashed border-border p-10 text-center text-muted-foreground">
                        No galas are on the calendar yet.
                    </p>
                ) : (
                    <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {upcomingGalas.map((gala, index) => (
                            <Link
                                key={gala.slug}
                                href={galas.show(gala.slug)}
                                className="group overflow-hidden rounded-xl border border-border bg-card transition-all hover:-translate-y-1 hover:border-primary/40 hover:shadow-lg"
                            >
                                <PhotoPanel
                                    photo={rotation[index % rotation.length]}
                                    sizes="(min-width: 1024px) 25vw, 100vw"
                                    className="aspect-[5/3]"
                                    overlay="soft"
                                />
                                <div className="p-5">
                                    <Badge
                                        variant="secondary"
                                        className="text-xs"
                                    >
                                        {gala.level}
                                    </Badge>
                                    <h3 className="mt-3 font-semibold text-balance group-hover:text-primary">
                                        {gala.name}
                                    </h3>
                                    <p className="mt-3 flex items-center gap-1.5 text-sm text-muted-foreground">
                                        <CalendarDays className="size-3.5 shrink-0" />
                                        {dateRange(
                                            gala.starts_on,
                                            gala.ends_on,
                                        )}
                                    </p>
                                    {gala.venue && (
                                        <p className="mt-1.5 flex items-center gap-1.5 text-sm text-muted-foreground">
                                            <MapPin className="size-3.5 shrink-0" />
                                            <span className="truncate">
                                                {gala.venue}
                                            </span>
                                        </p>
                                    )}
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </Section>

            {/*
             * The recruitment ask, placed directly under the fixtures that
             * motivate it: a reader who has just seen four galas coming up is
             * the reader most likely to hear that those galas need a deck
             * crew. Deep water rather than the ink used for the records band
             * below, so two dark grounds on one page stay distinguishable.
             */}
            <Section tone="deep">
                <div className="grid gap-12 lg:grid-cols-[1.1fr_1fr] lg:items-center lg:gap-16">
                    <Reveal>
                        <FlagRule className="h-1.5 w-20" />
                        <h2 className="mt-4 text-3xl font-bold tracking-tight text-balance text-white sm:text-4xl">
                            Somebody has to time the race
                        </h2>
                        <p className="mt-5 max-w-xl leading-relaxed text-white/70">
                            Every gala on this calendar is run by volunteers at
                            the water's edge — timekeepers, inspectors of turns,
                            judges of stroke, a starter and a referee. Short a
                            crew and the swimmers wait behind the blocks.
                        </p>
                        <p className="mt-4 max-w-xl leading-relaxed text-white/70">
                            Almost every national referee in the country started
                            with a stopwatch and one Saturday morning.
                        </p>

                        <div className="mt-9 flex flex-wrap gap-3">
                            <Button asChild size="lg">
                                <Link href={officials()}>
                                    Start as a{' '}
                                    {officialsEntry.category.toLowerCase()}
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                asChild
                                size="lg"
                                variant="outline"
                                className="border-white/30 bg-white/5 text-white hover:bg-white/15 hover:text-white"
                            >
                                <Link href={clubs()}>Find a club near you</Link>
                            </Button>
                        </div>

                        <p className="mt-5 max-w-lg text-sm text-white/70">
                            Officials are accredited through an affiliated club,
                            so joining one is the first step if you are not
                            already a member.
                        </p>
                    </Reveal>

                    <Reveal delay={120}>
                        <dl className="divide-y divide-white/15 overflow-hidden rounded-2xl border border-white/15 bg-white/5 backdrop-blur">
                            {officiatingFacts.map((fact) => (
                                <div
                                    key={fact.term}
                                    className="flex gap-4 p-6 sm:p-7"
                                >
                                    <fact.icon className="mt-0.5 size-5 shrink-0 text-brand-gold" />
                                    <div>
                                        <dt className="font-semibold text-white">
                                            {fact.term}
                                        </dt>
                                        <dd className="mt-1.5 text-sm leading-relaxed text-white/70">
                                            {fact.detail}
                                        </dd>
                                    </div>
                                </div>
                            ))}
                        </dl>
                    </Reveal>
                </div>
            </Section>

            {/* Pathways */}
            <Section>
                <div>
                    <FlagRule />
                    <h2 className="mt-4 text-3xl font-bold tracking-tight">
                        There is a place for you on the pool deck
                    </h2>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        Different roles. Same team. Whether you swim, time,
                        judge or drive to galas on a Saturday morning, the sport
                        runs on people.
                    </p>

                    <div className="mt-10 grid gap-6 md:grid-cols-3">
                        {pathways.map((pathway) => (
                            <Link
                                key={pathway.title}
                                href={pathway.href}
                                className="group overflow-hidden rounded-2xl border border-border bg-card transition-all hover:-translate-y-1 hover:shadow-lg"
                            >
                                <PhotoPanel
                                    photo={pathway.photo}
                                    sizes="(min-width: 768px) 33vw, 100vw"
                                    className="aspect-[7/4]"
                                    overlay="soft"
                                />
                                <div className="p-7">
                                    <pathway.icon className="size-6 text-primary" />
                                    <h3 className="mt-4 text-lg font-semibold">
                                        {pathway.title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                        {pathway.body}
                                    </p>
                                    <span className="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-primary">
                                        {pathway.cta}
                                        <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </Section>

            {/* Records band */}
            <section className="on-dark relative isolate overflow-hidden bg-brand-ink py-20 lg:py-24">
                <img
                    src={photos.lanes.src}
                    srcSet={photos.lanes.srcSet}
                    sizes="100vw"
                    alt=""
                    loading="lazy"
                    decoding="async"
                    style={{ objectPosition: photos.lanes.position }}
                    className="absolute inset-0 -z-20 size-full object-cover opacity-30"
                />
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-gradient-to-r from-brand-ink via-brand-ink/92 to-brand-ink/55"
                />

                <div className="mx-auto grid w-full max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8">
                    <div>
                        <FlagRule />
                        <h2 className="mt-4 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            Every record tells a story.
                            <span className="block text-brand-gold">
                                What will yours be?
                            </span>
                        </h2>
                        <p className="mt-5 max-w-xl leading-relaxed text-white/70">
                            Records are proposed automatically when a time beats
                            the standing mark, and only change once the
                            authorised body ratifies them. Long course and short
                            course, every age group, girls and boys.
                        </p>
                        <Button
                            asChild
                            size="lg"
                            variant="outline"
                            className="mt-8 border-white/30 bg-white/5 text-white hover:bg-white/15 hover:text-white"
                        >
                            <Link href={records()}>
                                Browse the record list
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {[
                            {
                                label: 'Ratified records',
                                value: stats.records,
                                icon: Trophy,
                            },
                            {
                                label: 'Galas this season',
                                value: stats.galas,
                                icon: CalendarDays,
                            },
                            {
                                label: 'Registered swimmers',
                                value: stats.swimmers,
                                icon: Waves,
                            },
                            {
                                label: 'Qualified officials',
                                value: stats.officials,
                                icon: ClipboardCheck,
                            },
                        ].map((tile, index) => (
                            <Reveal
                                key={tile.label}
                                delay={index * 90}
                                className="rounded-xl border border-white/15 bg-white/5 p-6 backdrop-blur"
                            >
                                <tile.icon className="size-5 text-brand-gold" />
                                <p className="mt-4 text-3xl font-bold text-white">
                                    <CountUp value={tile.value} />
                                </p>
                                <p className="mt-1 text-sm text-white/70">
                                    {tile.label}
                                </p>
                            </Reveal>
                        ))}
                    </div>
                </div>
            </section>

            {/* National venues */}
            {nationalVenues.length > 0 && (
                <Section>
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <FlagRule />
                            <h2 className="mt-4 text-3xl font-bold tracking-tight">
                                Where the nationals are swum
                            </h2>
                            <p className="mt-2 max-w-2xl text-muted-foreground">
                                Pools graded to carry senior nationals, the
                                sprint gala and international meets.
                            </p>
                        </div>
                        <Button asChild variant="ghost">
                            <Link href={venues()}>
                                All venues
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>

                    <div className="mt-10 grid gap-6 md:grid-cols-3">
                        {nationalVenues.map((venue) => (
                            <article
                                key={venue.slug}
                                className="overflow-hidden rounded-2xl border border-border bg-card"
                            >
                                <PhotoPanel
                                    photo={
                                        rotation[
                                            venue.slug.length % rotation.length
                                        ]
                                    }
                                    sizes="(min-width: 768px) 33vw, 100vw"
                                    className="aspect-[7/4]"
                                    overlay="bottom"
                                >
                                    <div className="flex aspect-[7/4] flex-col justify-end p-6">
                                        <h3 className="text-lg font-semibold text-white">
                                            {venue.name}
                                        </h3>
                                        {venue.city && (
                                            <p className="text-sm text-white/70">
                                                {venue.city}
                                            </p>
                                        )}
                                    </div>
                                </PhotoPanel>
                                <div className="flex items-center gap-5 p-5 text-sm text-muted-foreground">
                                    <span>
                                        {venue.pool_length
                                            ? `${venue.pool_length}m pool`
                                            : 'Pool length TBC'}
                                    </span>
                                    {venue.lanes && (
                                        <span>{venue.lanes} lanes</span>
                                    )}
                                </div>
                            </article>
                        ))}
                    </div>
                </Section>
            )}

            {/* News */}
            {articles.length > 0 && (
                <Section tone="surface">
                    <div>
                        <div className="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <FlagRule />
                                <h2 className="mt-4 text-3xl font-bold tracking-tight">
                                    Latest from the pool deck
                                </h2>
                            </div>
                            <Button asChild variant="ghost">
                                <Link href={news.index()}>
                                    All news
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </div>

                        <div className="mt-10 grid gap-6 md:grid-cols-3">
                            {articles.map((article) => (
                                <Link
                                    key={article.slug}
                                    href={news.show(article.slug)}
                                    className="group overflow-hidden rounded-2xl border border-border bg-card transition-all hover:-translate-y-1 hover:shadow-lg"
                                >
                                    <PhotoPanel
                                        photo={
                                            podiums[
                                                article.slug.length %
                                                    podiums.length
                                            ]
                                        }
                                        sizes="(min-width: 768px) 33vw, 100vw"
                                        className="aspect-[5/3]"
                                        overlay="soft"
                                    />
                                    <div className="p-6">
                                        <p className="text-xs font-semibold tracking-[0.14em] text-primary uppercase">
                                            {article.category}
                                        </p>
                                        <h3 className="mt-2 text-lg font-semibold text-balance group-hover:text-primary">
                                            {article.title}
                                        </h3>
                                        {article.excerpt && (
                                            <p className="mt-3 line-clamp-3 text-sm text-muted-foreground">
                                                {article.excerpt}
                                            </p>
                                        )}
                                        {article.published_at && (
                                            <time
                                                className="mt-4 block text-xs text-muted-foreground"
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
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                </Section>
            )}

            {/* Provinces */}
            <Section tone="ink">
                <div>
                    <FlagRule />
                    <h2 className="mt-4 text-3xl font-bold tracking-tight text-balance sm:text-4xl">
                        One sport. One nation. One team.
                    </h2>
                    <p className="mt-3 max-w-2xl text-muted-foreground">
                        Swimming in Zimbabwe runs through {stats.provinces}{' '}
                        provincial bodies and their affiliated clubs. Every
                        swimmer who races at a national gala is entered through
                        one of them.
                    </p>

                    <div className="mt-9 flex flex-wrap gap-3">
                        {provinces.map((province) => (
                            <Link
                                key={province.slug}
                                href={clubs()}
                                className="rounded-full border border-white/20 bg-white/5 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:border-brand-gold/60 hover:bg-white/10"
                            >
                                {province.name}
                            </Link>
                        ))}
                    </div>
                </div>
            </Section>

            {/* Sponsors */}
            {sponsors.length > 0 && (
                <section className="border-t border-border/60 bg-card py-12">
                    <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                        <p className="text-center text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                            We thank our sponsors for their support
                        </p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-x-16 gap-y-6">
                            {sponsors.map((sponsor) => (
                                <span
                                    key={sponsor.slug}
                                    className="text-xl font-bold tracking-tight text-muted-foreground"
                                >
                                    {sponsor.name}
                                </span>
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </PublicLayout>
    );
}
