import { Reveal } from '@/components/public/reveal';
import { cn } from '@/lib/utils';

export type ChainLink = {
    name: string;
    note: string;
    scope: string;
};

/**
 * The chain of command, drawn as one.
 *
 * A club secretary's real question is "who do I take this to?", and a list of
 * eight bullet points does not answer it. The rail on the left carries the
 * reader down through the tiers; the three Zimbabwean ones are marked in the
 * flag colours so the national body's place in the line is visible at a
 * glance rather than counted.
 */
export function CommunicationChain({ links }: { links: ChainLink[] }) {
    return (
        <ol className="relative mt-10">
            {links.map((link, index) => {
                const isLast = index === links.length - 1;

                return (
                    <Reveal
                        as="li"
                        key={link.name}
                        delay={index * 70}
                        className="relative grid grid-cols-[auto_minmax(0,1fr)] gap-x-5 pb-5 last:pb-0 sm:grid-cols-[7rem_auto_minmax(0,1fr)] sm:gap-x-6"
                    >
                        <p className="hidden self-center pt-px text-right text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase sm:block">
                            {link.scope}
                        </p>

                        {/* The rail: a marker on the line, and the line itself. */}
                        <div className="relative flex w-5 justify-center">
                            <span
                                aria-hidden="true"
                                className={cn(
                                    'relative z-10 mt-6 size-3 shrink-0 rounded-full ring-4 ring-background',
                                    tierColour(index, links.length),
                                )}
                            />
                            {!isLast && (
                                <span
                                    aria-hidden="true"
                                    className="absolute top-6 bottom-0 left-1/2 w-px -translate-x-1/2 bg-border"
                                />
                            )}
                        </div>

                        <div className="rounded-xl border border-border bg-card p-5 transition-colors hover:border-primary/40">
                            <p className="font-semibold">{link.name}</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {link.note}
                            </p>
                            <p className="mt-2 text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase sm:hidden">
                                {link.scope}
                            </p>
                        </div>
                    </Reveal>
                );
            })}
        </ol>
    );
}

/**
 * The three tiers Zimbabwe Swimming sits inside take the flag colours; the
 * federations above it and the people below are left neutral.
 */
function tierColour(index: number, total: number): string {
    if (index === total - 1) {
        return 'bg-brand-red';
    }

    if (index === total - 2) {
        return 'bg-brand-gold';
    }

    if (index === total - 3) {
        return 'bg-brand-green';
    }

    return 'bg-muted-foreground/45';
}

/**
 * Written out rather than built from a template string, so Tailwind can see
 * every class it has to generate.
 */
const ROW_COLUMNS: Record<number, string | undefined> = {
    2: 'lg:grid-cols-2',
    3: 'lg:grid-cols-3',
    4: 'lg:grid-cols-4',
    5: 'lg:grid-cols-5',
};

export type Officer = {
    name: string;
    role: string;
    email?: string | null;
};

/**
 * A committee drawn as an organogram rather than listed.
 *
 * The chair sits alone at the top, the deputy below, and the portfolio
 * officers across a row beneath them, joined by the lines that actually
 * describe who reports to whom. Where a post is vacant the card still
 * appears, because an empty seat on a board is information.
 */
export function Organogram({
    title,
    officers,
}: {
    title: string;
    officers: Officer[];
}) {
    const chair = officers.find((officer) => rank(officer.role) === 0);
    const deputy = officers.find((officer) => rank(officer.role) === 1);
    const rest = officers.filter((officer) => rank(officer.role) === 2);

    // The branch only lines up where the posts are actually on one row,
    // which is the large breakpoint and nothing narrower.
    const columns = ROW_COLUMNS[rest.length];

    return (
        <div className="rounded-2xl border border-border bg-card p-6 sm:p-8">
            <p className="text-center text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                {title}
            </p>

            <div className="mt-6 flex flex-col items-center">
                {chair && (
                    <div className="w-full max-w-xs">
                        <OfficerCard officer={chair} lead />
                    </div>
                )}

                {deputy && (
                    <>
                        <Spine />
                        <div className="w-full max-w-xs">
                            <OfficerCard officer={deputy} />
                        </div>
                    </>
                )}

                {rest.length > 0 && (
                    <>
                        <Spine />

                        {/*
                         * The branch. A bar across the row with a stub
                         * dropping into each post is what makes this an
                         * organogram rather than a stack of cards: it says
                         * these four answer to the same person, not to each
                         * other. It is drawn only at the width where the row
                         * is actually a row.
                         */}
                        {columns && (
                            <div
                                aria-hidden="true"
                                className={cn('hidden w-full lg:grid', columns)}
                            >
                                {rest.map((officer, index) => (
                                    <div
                                        key={officer.role}
                                        className="relative h-5"
                                    >
                                        <span
                                            className={cn(
                                                'absolute top-0 h-px bg-border',
                                                index === 0 &&
                                                    'right-0 left-1/2',
                                                index === rest.length - 1 &&
                                                    'right-1/2 left-0',
                                                index > 0 &&
                                                    index < rest.length - 1 &&
                                                    'inset-x-0',
                                            )}
                                        />
                                        <span className="absolute inset-y-0 left-1/2 w-px -translate-x-1/2 bg-border" />
                                    </div>
                                ))}
                            </div>
                        )}

                        <div
                            className={cn(
                                'grid w-full gap-4 sm:grid-cols-2',
                                columns ?? 'lg:grid-cols-4',
                            )}
                        >
                            {rest.map((officer, index) => (
                                <Reveal key={officer.role} delay={index * 80}>
                                    <OfficerCard officer={officer} />
                                </Reveal>
                            ))}
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

function Spine() {
    return (
        <span
            aria-hidden="true"
            className="my-4 block h-6 w-px bg-gradient-to-b from-border to-primary/50"
        />
    );
}

function OfficerCard({
    officer,
    lead = false,
}: {
    officer: Officer;
    lead?: boolean;
}) {
    return (
        <div
            className={cn(
                'h-full w-full rounded-xl border p-4 text-center',
                lead
                    ? 'border-primary/40 bg-primary/5 shadow-sm'
                    : 'border-border bg-background',
            )}
        >
            <p
                className={cn(
                    'text-xs font-semibold tracking-[0.12em] uppercase',
                    lead ? 'text-primary' : 'text-muted-foreground',
                )}
            >
                {officer.role}
            </p>
            <p className="mt-1.5 font-semibold text-balance">
                {officer.name !== '' ? officer.name : 'Vacant'}
            </p>
            {officer.email && (
                <a
                    href={`mailto:${officer.email}`}
                    className="mt-1 block truncate text-xs text-primary hover:underline"
                >
                    {officer.email}
                </a>
            )}
        </div>
    );
}

/**
 * Where a post sits in the board: chair, deputy, then everyone else.
 */
function rank(role: string): 0 | 1 | 2 {
    const name = role.toLowerCase();

    if (name.includes('vice') || name.includes('deputy')) {
        return 1;
    }

    if (name.includes('chair') || name.includes('president')) {
        return 0;
    }

    return 2;
}
