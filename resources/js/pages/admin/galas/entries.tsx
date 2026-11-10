import { Form, Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Download,
    Plus,
    Trash2,
    Upload,
    Users,
    Users2,
} from 'lucide-react';
import { useState } from 'react';

import { EmptyState } from '@/components/admin/empty-state';
import { SelectField } from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type EntryRow = {
    id: number;
    event_number: number;
    event: string;
    age_group: string;
    swimmer: string;
    membership_number: string;
    club: string;
    entry_time: string;
    heat: number | null;
    lane: number | null;
    status: string;
    is_late_entry: boolean;
};

export default function GalaEntries({
    gala,
    entries,
    events,
    swimmers,
    importProblems,
    can,
}: {
    gala: {
        slug: string;
        name: string;
        status_label: string;
        accepting_entries: boolean;
        entries_close_at: string | null;
        age_up_date: string;
        qualifying_times_apply: boolean;
        lanes: number;
    };
    entries: EntryRow[];
    events: (Option<number> & { seeded: boolean; entry_count: number })[];
    swimmers: Option<number>[];
    importProblems?: string[];
    can: { manage: boolean; seed: boolean };
}) {
    useFlashMessages();
    const [selected, setSelected] = useState<number[]>([]);

    const toggle = (id: number) =>
        setSelected((current) =>
            current.includes(id)
                ? current.filter((value) => value !== id)
                : [...current, id],
        );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Galas', href: admin.galas.index().url },
                { title: gala.name, href: admin.galas.show(gala.slug).url },
                {
                    title: 'Entries',
                    href: admin.galas.entries.index(gala.slug).url,
                },
            ]}
        >
            <Head title={`${gala.name} entries`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.galas.show(gala.slug)}>
                        <ArrowLeft className="size-4" />
                        Back to gala
                    </Link>
                </Button>

                <PageHeader
                    title="Entries"
                    description={`${gala.name} · ages taken at ${gala.age_up_date}${gala.qualifying_times_apply ? ' · qualifying times apply' : ''}`}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link
                                    href={admin.galas.relays.index(gala.slug)}
                                >
                                    <Users2 className="size-4" />
                                    Relays
                                </Link>
                            </Button>
                            {can.seed && (
                                <Button asChild variant="outline">
                                    <Link
                                        href={admin.galas.heatSheet(gala.slug)}
                                    >
                                        Heat sheet
                                    </Link>
                                </Button>
                            )}
                        </div>
                    }
                />

                {!gala.accepting_entries && (
                    <p className="rounded-lg border border-brand-gold/50 bg-brand-gold/10 px-4 py-3 text-sm">
                        Entries are not open for this gala
                        {gala.entries_close_at
                            ? ` (closed ${gala.entries_close_at})`
                            : ''}
                        . Anything added now is marked as a late entry.
                    </p>
                )}

                <div className="grid gap-6 xl:grid-cols-3">
                    <div className="xl:col-span-2">
                        {entries.length === 0 ? (
                            <EmptyState
                                icon={Users}
                                title="No entries yet"
                                description="Pick an event and add swimmers from your club."
                            />
                        ) : (
                            <div className="overflow-x-auto rounded-xl border border-border">
                                <table className="w-full min-w-[760px] text-sm">
                                    <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">
                                                Event
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Swimmer
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Entry time
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Heat / lane
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="px-4 py-3" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {entries.map((entry) => (
                                            <tr
                                                key={entry.id}
                                                className="border-b border-border/60 bg-card last:border-0"
                                            >
                                                <td className="px-4 py-3">
                                                    <span className="font-mono text-xs text-muted-foreground">
                                                        {entry.event_number}
                                                    </span>{' '}
                                                    {entry.event}
                                                    <span className="block text-xs text-muted-foreground">
                                                        {entry.age_group}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <p className="font-medium">
                                                        {entry.swimmer}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {entry.club}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-3 font-mono tabular-nums">
                                                    {entry.entry_time}
                                                </td>
                                                <td className="px-4 py-3 tabular-nums">
                                                    {entry.heat
                                                        ? `H${entry.heat} L${entry.lane}`
                                                        : '—'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant="outline"
                                                        className="capitalize"
                                                    >
                                                        {entry.status}
                                                    </Badge>
                                                    {entry.is_late_entry && (
                                                        <Badge
                                                            variant="outline"
                                                            className="ml-1 border-brand-gold/50"
                                                        >
                                                            Late
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {can.manage && (
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                router.delete(
                                                                    admin.galas.entries.destroy(
                                                                        [
                                                                            gala.slug,
                                                                            entry.id,
                                                                        ],
                                                                    ).url,
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="size-4 text-brand-red" />
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    {can.manage && (
                        <section className="h-fit rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">Add entries</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Age group, capitation and qualifying times are
                                checked on save.
                            </p>

                            <Form
                                {...admin.galas.entries.store.form(gala.slug)}
                                className="mt-5 space-y-4"
                                onSuccess={() => setSelected([])}
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <SelectField
                                            label="Event"
                                            name="gala_event_id"
                                            options={events.map((event) => ({
                                                value: event.value,
                                                label: `${event.label}${event.seeded ? ' · seeded' : ''}`,
                                            }))}
                                            defaultValue={events[0]?.value}
                                            error={errors.gala_event_id}
                                        />

                                        <div className="grid gap-2">
                                            <Label>Swimmers</Label>
                                            <div className="max-h-64 space-y-2 overflow-y-auto rounded-md border border-border p-3">
                                                {swimmers.map((swimmer) => (
                                                    <label
                                                        key={swimmer.value}
                                                        className="flex items-start gap-2.5 text-sm"
                                                    >
                                                        <Checkbox
                                                            name="member_ids[]"
                                                            value={
                                                                swimmer.value
                                                            }
                                                            checked={selected.includes(
                                                                swimmer.value,
                                                            )}
                                                            onCheckedChange={() =>
                                                                toggle(
                                                                    swimmer.value,
                                                                )
                                                            }
                                                        />
                                                        {swimmer.label}
                                                    </label>
                                                ))}
                                            </div>
                                            <InputError
                                                message={errors.member_ids}
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            className="w-full"
                                            disabled={
                                                processing ||
                                                selected.length === 0
                                            }
                                        >
                                            <Plus className="size-4" />
                                            Enter {selected.length || ''}{' '}
                                            {selected.length === 1
                                                ? 'swimmer'
                                                : 'swimmers'}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    {can.manage && (
                        <section className="h-fit rounded-xl border border-border bg-card p-6 xl:col-start-3">
                            <h2 className="font-semibold">Import and export</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Lenex is the format Team Manager, Meet Manager
                                and Splash all read. The CSV sheet is for clubs
                                who keep their squad in a spreadsheet. Both run
                                the same eligibility checks.
                            </p>

                            <Form
                                {...admin.galas.import.form(gala.slug)}
                                className="mt-5 space-y-3"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <input
                                            type="file"
                                            name="file"
                                            accept=".lef,.lxf,.xml,.csv,.txt"
                                            className="w-full cursor-pointer rounded-md border border-input bg-transparent px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-muted file:px-2 file:py-1 file:text-xs"
                                        />
                                        <InputError message={errors.file} />
                                        <Button
                                            type="submit"
                                            variant="secondary"
                                            className="w-full"
                                            disabled={processing}
                                        >
                                            <Upload className="size-4" />
                                            Import entries
                                        </Button>
                                    </>
                                )}
                            </Form>

                            {importProblems && importProblems.length > 0 && (
                                <div className="mt-4 rounded-lg border border-brand-gold/50 bg-brand-gold/10 p-3">
                                    <p className="text-xs font-medium">
                                        {importProblems.length}{' '}
                                        {importProblems.length === 1
                                            ? 'row was'
                                            : 'rows were'}{' '}
                                        not imported
                                    </p>
                                    <ul className="mt-2 space-y-1 text-xs text-muted-foreground">
                                        {importProblems.map(
                                            (problem, index) => (
                                                <li key={index}>• {problem}</li>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            )}

                            <div className="mt-5 space-y-2 border-t border-border pt-5">
                                <Button
                                    asChild
                                    variant="outline"
                                    className="w-full"
                                >
                                    <a href={admin.galas.lenex(gala.slug).url}>
                                        <Download className="size-4" />
                                        Download Lenex file
                                    </a>
                                </Button>
                                <Button
                                    asChild
                                    variant="ghost"
                                    size="sm"
                                    className="w-full"
                                >
                                    <a
                                        href={
                                            admin.galas.entrySheet(gala.slug)
                                                .url
                                        }
                                    >
                                        Blank CSV entry sheet
                                    </a>
                                </Button>
                            </div>
                        </section>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
