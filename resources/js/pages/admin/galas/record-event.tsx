import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Award, Send, Star } from 'lucide-react';

import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';

type EntryRow = {
    id: number;
    heat: number | null;
    lane: number | null;
    swimmer: string;
    club: string;
    entry_time: string;
    recorded_time: string;
    status: string;
    dq_code: string | null;
    place: number | null;
    is_personal_best: boolean;
    record_flagged: boolean;
    legs?: string[];
    scores_points?: boolean;
};

type RowState = {
    gala_entry_id: number;
    time: string;
    status: string;
    dq_code: string | null;
};

export default function RecordEvent({
    gala,
    event,
    entries,
    disqualificationCodes,
    can,
}: {
    gala: { slug: string; name: string };
    event: {
        id: number;
        number: number;
        title: string;
        age_group: string;
        is_relay: boolean;
        seeded: boolean;
        published: boolean;
    };
    entries: EntryRow[];
    disqualificationCodes: { value: string; label: string; group: string }[];
    can: { record: boolean };
}) {
    useFlashMessages();

    const { data, setData, post, processing } = useForm<{
        results: RowState[];
    }>({
        results: entries.map((entry) => ({
            gala_entry_id: entry.id,
            time: entry.recorded_time,
            status: entry.status,
            dq_code: entry.dq_code,
        })),
    });

    const update = (index: number, patch: Partial<RowState>) =>
        setData(
            'results',
            data.results.map((row, i) =>
                i === index ? { ...row, ...patch } : row,
            ),
        );

    const heats = [...new Set(entries.map((entry) => entry.heat ?? 0))].sort(
        (a, b) => a - b,
    );

    return (
        <AppLayout
            breadcrumbs={[
                { title: gala.name, href: admin.galas.show(gala.slug).url },
                {
                    title: 'Results',
                    href: admin.galas.results.index(gala.slug).url,
                },
                { title: `Event ${event.number}`, href: '#' },
            ]}
        >
            <Head title={`Event ${event.number} · ${gala.name}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.galas.results.index(gala.slug)}>
                        <ArrowLeft className="size-4" />
                        All events
                    </Link>
                </Button>

                <PageHeader
                    title={`Event ${event.number} · ${event.title}`}
                    description={`${event.age_group}${event.is_relay ? ' · relay, one time per team' : ''} · enter times as 1:02.34 or 58.07`}
                    actions={
                        can.record && (
                            <div className="flex gap-2">
                                <Button
                                    onClick={() =>
                                        post(
                                            admin.galas.results.store([
                                                gala.slug,
                                                event.id,
                                            ]).url,
                                            { preserveScroll: true },
                                        )
                                    }
                                    disabled={processing}
                                >
                                    Save times
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.post(
                                            admin.galas.results.publish([
                                                gala.slug,
                                                event.id,
                                            ]).url,
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <Send className="size-4" />
                                    Publish
                                </Button>
                            </div>
                        )
                    }
                />

                {event.published && (
                    <p className="rounded-lg border border-brand-green/40 bg-brand-green/10 px-4 py-3 text-sm">
                        These results are published on the public website.
                    </p>
                )}

                {heats.map((heat) => (
                    <section
                        key={heat}
                        className="overflow-hidden rounded-xl border border-border"
                    >
                        <h2 className="border-b border-border bg-muted/40 px-5 py-3 text-sm font-semibold">
                            {heat === 0 ? 'Unseeded' : `Heat ${heat}`}
                        </h2>
                        <table className="w-full text-sm">
                            <thead className="border-b border-border text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-2.5 font-medium">
                                        Lane
                                    </th>
                                    <th className="px-4 py-2.5 font-medium">
                                        Swimmer
                                    </th>
                                    <th className="px-4 py-2.5 font-medium">
                                        Seed
                                    </th>
                                    <th className="px-4 py-2.5 font-medium">
                                        Time
                                    </th>
                                    <th className="px-4 py-2.5 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-2.5 font-medium">
                                        Rule
                                    </th>
                                    <th className="px-4 py-2.5 font-medium">
                                        Place
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {entries.map((entry, index) =>
                                    (entry.heat ?? 0) !== heat ? null : (
                                        <tr
                                            key={entry.id}
                                            className="border-b border-border/60 bg-card last:border-0"
                                        >
                                            <td className="px-4 py-2.5 font-mono tabular-nums">
                                                {entry.lane ?? '—'}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <span className="font-medium">
                                                    {entry.swimmer}
                                                </span>
                                                <span className="block text-xs text-muted-foreground">
                                                    {entry.club}
                                                    {entry.scores_points ===
                                                        false &&
                                                        ' · medals only, scores no points'}
                                                </span>
                                                {entry.legs && (
                                                    <span className="block text-xs text-muted-foreground">
                                                        {entry.legs.join(', ')}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5 font-mono text-xs text-muted-foreground tabular-nums">
                                                {entry.entry_time}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <Input
                                                    value={
                                                        data.results[index]
                                                            ?.time ?? ''
                                                    }
                                                    onChange={(e) =>
                                                        update(index, {
                                                            time: e.target
                                                                .value,
                                                        })
                                                    }
                                                    placeholder="1:02.34"
                                                    inputMode="decimal"
                                                    disabled={!can.record}
                                                    className="h-9 w-28 font-mono tabular-nums"
                                                />
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <select
                                                    value={
                                                        data.results[index]
                                                            ?.status ??
                                                        'official'
                                                    }
                                                    onChange={(e) =>
                                                        update(index, {
                                                            status: e.target
                                                                .value,
                                                        })
                                                    }
                                                    disabled={!can.record}
                                                    className="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                                                >
                                                    <option value="official">
                                                        Official
                                                    </option>
                                                    <option value="dq">
                                                        DQ
                                                    </option>
                                                    <option value="dns">
                                                        DNS
                                                    </option>
                                                    <option value="dnf">
                                                        DNF
                                                    </option>
                                                </select>
                                            </td>
                                            <td className="px-4 py-2.5">
                                                {data.results[index]?.status ===
                                                'dq' ? (
                                                    <select
                                                        value={
                                                            data.results[index]
                                                                ?.dq_code ?? ''
                                                        }
                                                        onChange={(e) =>
                                                            update(index, {
                                                                dq_code:
                                                                    e.target
                                                                        .value ||
                                                                    null,
                                                            })
                                                        }
                                                        className="h-9 max-w-[16rem] rounded-md border border-input bg-transparent px-2 text-sm"
                                                    >
                                                        <option value="">
                                                            Select a rule
                                                        </option>
                                                        {disqualificationCodes.map(
                                                            (code) => (
                                                                <option
                                                                    key={
                                                                        code.value
                                                                    }
                                                                    value={
                                                                        code.value
                                                                    }
                                                                >
                                                                    {code.label}
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <span className="flex items-center gap-1.5">
                                                    <span className="font-semibold tabular-nums">
                                                        {entry.place ?? '—'}
                                                    </span>
                                                    {entry.is_personal_best && (
                                                        <Star
                                                            className="size-3.5 text-brand-gold"
                                                            aria-label="Personal best"
                                                        />
                                                    )}
                                                    {entry.record_flagged && (
                                                        <Badge
                                                            variant="outline"
                                                            className="gap-1 border-brand-gold/60 text-xs"
                                                        >
                                                            <Award className="size-3" />
                                                            Record
                                                        </Badge>
                                                    )}
                                                </span>
                                            </td>
                                        </tr>
                                    ),
                                )}
                            </tbody>
                        </table>
                    </section>
                ))}
            </div>
        </AppLayout>
    );
}
