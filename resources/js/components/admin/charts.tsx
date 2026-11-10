import { useId, useState, type ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * The chart kit.
 *
 * Drawn in SVG against the project's own tokens rather than pulled from a
 * charting library: four shapes is less code than a dependency, and it keeps
 * the marks on the same palette as the rest of the application in both
 * themes. The series colours are `--viz-1` to `--viz-5`, assigned in that
 * order and never cycled; state uses the reserved status colours instead.
 */
export const SERIES = [
    'var(--viz-1)',
    'var(--viz-2)',
    'var(--viz-3)',
    'var(--viz-4)',
    'var(--viz-5)',
] as const;

export type Slice = { label: string; value: number };

/**
 * The frame every chart sits in: a title, an optional note, and the plot.
 */
export function ChartCard({
    title,
    note,
    children,
    className,
    action,
}: {
    title: string;
    note?: string;
    children: ReactNode;
    className?: string;
    action?: ReactNode;
}) {
    return (
        <section
            className={cn(
                '@container rounded-xl border border-border bg-card p-5 sm:p-6',
                className,
            )}
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="font-semibold">{title}</h2>
                    {note && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {note}
                        </p>
                    )}
                </div>
                {action}
            </div>
            <div className="mt-6">{children}</div>
        </section>
    );
}

/**
 * Keep the largest shares and gather the rest into one.
 */
function foldTail(slices: Slice[], limit: number): Slice[] {
    if (slices.length <= limit) {
        return slices;
    }

    const ranked = [...slices].sort((a, b) => b.value - a.value);
    const kept = ranked.slice(0, limit - 1);
    const rest = ranked.slice(limit - 1);

    return [
        ...kept,
        {
            label: `Other (${rest.length})`,
            value: rest.reduce((sum, slice) => sum + slice.value, 0),
        },
    ];
}

function Empty({ children }: { children: ReactNode }) {
    return (
        <p className="py-10 text-center text-sm text-muted-foreground">
            {children}
        </p>
    );
}

/**
 * Magnitude across a handful of named bands, which is a row of bars rather
 * than a pie: a length is read far more accurately than an angle.
 *
 * One series, so there is no legend; every bar is labelled with its own
 * figure instead, which is also what carries the reading when the colour
 * falls below three to one against the card.
 */
export function BarRows({
    rows,
    empty = 'Nothing to show yet.',
    colour = SERIES[0],
    unit,
    showShare = false,
}: {
    rows: Slice[];
    empty?: string;
    colour?: string;
    unit?: string;
    showShare?: boolean;
}) {
    const [hovered, setHovered] = useState<number | null>(null);
    const total = rows.reduce((sum, row) => sum + row.value, 0);

    if (rows.length === 0 || total === 0) {
        return <Empty>{empty}</Empty>;
    }

    const peak = Math.max(...rows.map((row) => row.value));

    return (
        <ul className="space-y-3.5">
            {rows.map((row, index) => {
                const isZero = row.value === 0;

                return (
                    <li
                        key={row.label}
                        onMouseEnter={() => setHovered(index)}
                        onMouseLeave={() => setHovered(null)}
                        onFocus={() => setHovered(index)}
                        onBlur={() => setHovered(null)}
                        tabIndex={0}
                        className="group rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <div className="flex items-baseline justify-between gap-4 text-sm">
                            <span
                                className={cn(
                                    'truncate',
                                    isZero && 'text-muted-foreground',
                                )}
                            >
                                {row.label}
                            </span>
                            <span
                                className={cn(
                                    'shrink-0 font-semibold tabular-nums',
                                    isZero &&
                                        'font-normal text-muted-foreground',
                                )}
                            >
                                {row.value}
                                {unit ? ` ${unit}` : ''}
                                {showShare && (
                                    <span className="ml-2 text-xs font-normal text-muted-foreground">
                                        {Math.round((row.value / total) * 100)}%
                                    </span>
                                )}
                            </span>
                        </div>
                        <div className="mt-1.5 h-2.5 w-full overflow-hidden rounded-full bg-muted">
                            {/*
                             * Nothing is drawn for a zero. Giving it a
                             * minimum width would put a quantity on the page
                             * that the register does not hold.
                             */}
                            {!isZero && (
                                <div
                                    className="h-full rounded-full transition-[width,opacity] duration-700 ease-out"
                                    style={{
                                        width: `${Math.max(1.5, (row.value / peak) * 100)}%`,
                                        backgroundColor: colour,
                                        opacity:
                                            hovered === null ||
                                            hovered === index
                                                ? 1
                                                : 0.45,
                                    }}
                                />
                            )}
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}

/**
 * Identity across a few parts of a whole.
 *
 * A ring rather than a pie, so the total can sit in the middle where it is
 * actually useful. Every segment is in the legend with its own figure, so
 * nothing depends on telling two colours apart.
 */
export function Donut({
    slices: given,
    centreLabel,
    empty = 'Nothing to show yet.',
}: {
    slices: Slice[];
    centreLabel?: string;
    empty?: string;
}) {
    const [hovered, setHovered] = useState<number | null>(null);
    const gradientId = useId();

    /*
     * There are five series colours and there is no sixth. Anything past the
     * fifth largest share folds into one "Other" slice rather than being
     * given a generated hue, which would put two unrelated categories in
     * colours nobody can tell apart.
     */
    const slices = foldTail(given, SERIES.length);
    const total = slices.reduce((sum, slice) => sum + slice.value, 0);

    if (total === 0) {
        return <Empty>{empty}</Empty>;
    }

    // A ring drawn from stroke dash offsets: one circle per segment, each
    // starting where the last finished, with a two-pixel gap in the card
    // colour between them.
    const radius = 56;
    const circumference = 2 * Math.PI * radius;
    let offset = 0;

    const arcs = slices.map((slice, index) => {
        const length = (slice.value / total) * circumference;
        const arc = {
            slice,
            index,
            dash: Math.max(0, length - 3),
            offset,
        };

        offset += length;

        return arc;
    });

    return (
        <div className="flex flex-col items-center gap-7 @md:flex-row @md:items-center @md:gap-8">
            <div className="relative shrink-0">
                <svg
                    viewBox="0 0 140 140"
                    className="size-36"
                    role="img"
                    aria-label={slices
                        .map((slice) => `${slice.label}: ${slice.value}`)
                        .join(', ')}
                >
                    <title id={gradientId}>
                        {slices
                            .map((slice) => `${slice.label} ${slice.value}`)
                            .join(', ')}
                    </title>
                    <g transform="rotate(-90 70 70)">
                        {arcs.map((arc) => (
                            <circle
                                key={arc.slice.label}
                                cx="70"
                                cy="70"
                                r={radius}
                                fill="none"
                                strokeWidth="18"
                                stroke={SERIES[arc.index % SERIES.length]}
                                strokeDasharray={`${arc.dash} ${circumference - arc.dash}`}
                                strokeDashoffset={-arc.offset}
                                className="transition-opacity duration-200"
                                opacity={
                                    hovered === null || hovered === arc.index
                                        ? 1
                                        : 0.4
                                }
                                onMouseEnter={() => setHovered(arc.index)}
                                onMouseLeave={() => setHovered(null)}
                            />
                        ))}
                    </g>
                </svg>
                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-2xl font-bold tabular-nums">
                        {hovered === null ? total : slices[hovered].value}
                    </span>
                    <span className="max-w-20 text-center text-[11px] leading-tight text-muted-foreground">
                        {hovered === null
                            ? (centreLabel ?? 'Total')
                            : slices[hovered].label}
                    </span>
                </div>
            </div>

            <ul className="w-full min-w-0 space-y-2.5">
                {slices.map((slice, index) => (
                    <li
                        key={slice.label}
                        onMouseEnter={() => setHovered(index)}
                        onMouseLeave={() => setHovered(null)}
                        className="flex items-center gap-3 text-sm"
                    >
                        <span
                            aria-hidden="true"
                            className="size-2.5 shrink-0 rounded-full"
                            style={{
                                backgroundColor: SERIES[index % SERIES.length],
                            }}
                        />
                        <span className="min-w-0 flex-1 truncate">
                            {slice.label}
                        </span>
                        <span className="shrink-0 font-semibold tabular-nums">
                            {slice.value}
                        </span>
                        <span className="w-10 shrink-0 text-right text-xs text-muted-foreground tabular-nums">
                            {Math.round((slice.value / total) * 100)}%
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/**
 * A count over the months of the season.
 *
 * Time runs left to right and the bars sit on a baseline, which is the only
 * arrangement that lets someone see a busy month at a glance. Only the
 * months that have something in them are labelled.
 */
export function Columns({
    points,
    empty = 'Nothing scheduled yet.',
    colour = SERIES[0],
    highlight,
}: {
    points: { label: string; value: number }[];
    empty?: string;
    colour?: string;
    /** The label of the month being lived through, marked rather than coloured. */
    highlight?: string;
}) {
    const [hovered, setHovered] = useState<number | null>(null);

    if (points.every((point) => point.value === 0)) {
        return <Empty>{empty}</Empty>;
    }

    const peak = Math.max(...points.map((point) => point.value));

    return (
        <div>
            <div className="flex gap-1.5">
                {points.map((point, index) => (
                    <div
                        key={point.label}
                        className={cn(
                            'relative flex flex-1 flex-col justify-end rounded-t-sm',
                            point.label === highlight && 'bg-brand-gold/15',
                        )}
                        onMouseEnter={() => setHovered(index)}
                        onMouseLeave={() => setHovered(null)}
                    >
                        {/*
                         * The figure sits in its own fixed row above the
                         * plot, so the tallest column cannot push it out of
                         * the card.
                         */}
                        <span
                            className={cn(
                                'h-5 text-center text-xs font-semibold tabular-nums',
                                point.value === 0
                                    ? 'opacity-0'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {point.value}
                        </span>
                        <div className="flex h-36 items-end">
                            <div
                                className="w-full rounded-t-[4px] transition-[height,opacity] duration-700 ease-out"
                                style={{
                                    height:
                                        point.value === 0
                                            ? '2px'
                                            : `${(point.value / peak) * 100}%`,
                                    backgroundColor:
                                        point.value === 0
                                            ? 'var(--viz-grid)'
                                            : colour,
                                    opacity:
                                        hovered === null || hovered === index
                                            ? 1
                                            : 0.45,
                                }}
                            />
                        </div>
                    </div>
                ))}
            </div>
            <div className="mt-2 flex gap-1.5 border-t border-border pt-2">
                {points.map((point) => (
                    <span
                        key={point.label}
                        className={cn(
                            'flex-1 text-center text-[11px]',
                            point.label === highlight
                                ? 'font-semibold text-foreground'
                                : 'text-muted-foreground',
                        )}
                    >
                        {point.label}
                    </span>
                ))}
            </div>
        </div>
    );
}

export type StatusSlice = {
    label: string;
    value: number;
    tone: 'good' | 'warn' | 'serious' | 'critical' | 'neutral';
};

/**
 * One bar carrying the whole register, split by the state each record is in.
 *
 * State gets the reserved status colours, never a series colour, and every
 * segment is named in the key beneath with its own count: the colour says
 * "look here", the words say what it is.
 */
export function StatusStrip({
    slices,
    empty = 'No records yet.',
}: {
    slices: StatusSlice[];
    empty?: string;
}) {
    const present = slices.filter((slice) => slice.value > 0);
    const absent = slices.filter((slice) => slice.value === 0);
    const total = present.reduce((sum, slice) => sum + slice.value, 0);

    if (total === 0) {
        return <Empty>{empty}</Empty>;
    }

    return (
        <div>
            <div className="flex h-4 w-full gap-0.5 overflow-hidden rounded-full">
                {present.map((slice) => (
                    <div
                        key={slice.label}
                        title={`${slice.label}: ${slice.value}`}
                        className="h-full first:rounded-l-full last:rounded-r-full"
                        style={{
                            width: `${(slice.value / total) * 100}%`,
                            backgroundColor: `var(--viz-${slice.tone})`,
                        }}
                    />
                ))}
            </div>

            <ul className="mt-5 grid gap-x-6 gap-y-2.5 @sm:grid-cols-2">
                {present.map((slice) => (
                    <li
                        key={slice.label}
                        className="flex items-center gap-2.5 text-sm"
                    >
                        <span
                            aria-hidden="true"
                            className="size-2.5 shrink-0 rounded-full"
                            style={{
                                backgroundColor: `var(--viz-${slice.tone})`,
                            }}
                        />
                        <span className="min-w-0 flex-1 truncate text-muted-foreground">
                            {slice.label}
                        </span>
                        <span className="shrink-0 font-semibold tabular-nums">
                            {slice.value}
                        </span>
                    </li>
                ))}
            </ul>

            {/*
             * The states nobody is in are worth saying once, in a line,
             * rather than as four key entries all reading zero.
             */}
            {absent.length > 0 && (
                <p className="mt-4 text-xs text-muted-foreground">
                    Nobody is{' '}
                    {new Intl.ListFormat('en-GB', {
                        type: 'disjunction',
                    }).format(absent.map((slice) => slice.label.toLowerCase()))}
                    .
                </p>
            )}
        </div>
    );
}

/**
 * Whether the officials on the books can actually staff a gala.
 *
 * A count per role rather than a chart, because eight roles sitting at one
 * apiece make eight identical bars that say nothing. What matters is which
 * roles are covered and which are empty, and that an empty referee or
 * starter stops a gala being run at all - so those two are named as
 * required and marked in the status red when they are missing.
 */
export function ReadinessGrid({
    roles,
    empty = 'No current accreditations.',
}: {
    roles: { label: string; value: number; required: boolean }[];
    empty?: string;
}) {
    if (roles.length === 0) {
        return <Empty>{empty}</Empty>;
    }

    return (
        <ul className="grid grid-cols-2 gap-2">
            {roles.map((role) => {
                const missing = role.value === 0;
                const blocking = missing && role.required;

                return (
                    <li
                        key={role.label}
                        className={cn(
                            'rounded-lg border px-3 py-2.5',
                            blocking
                                ? 'border-viz-critical/40 bg-viz-critical/5'
                                : missing
                                  ? 'border-dashed border-border'
                                  : 'border-border bg-muted/40',
                        )}
                    >
                        <div className="flex items-baseline justify-between gap-3">
                            <span className="text-sm text-pretty">
                                {role.label}
                            </span>
                            <span
                                className={cn(
                                    'text-lg leading-none font-bold tabular-nums',
                                    missing && 'text-muted-foreground',
                                )}
                            >
                                {role.value}
                            </span>
                        </div>
                        {role.required && (
                            <p
                                className={cn(
                                    'mt-1 text-xs',
                                    blocking
                                        ? 'font-medium text-brand-red'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {blocking
                                    ? 'No gala can be started'
                                    : 'Required at every gala'}
                            </p>
                        )}
                    </li>
                );
            })}
        </ul>
    );
}
