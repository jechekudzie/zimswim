import { Head, Link } from '@inertiajs/react';
import { Info } from 'lucide-react';

import { FlagRule } from '@/components/flag-rule';
import { Section } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import galas from '@/routes/galas';

type Fee = {
    name: string;
    amount: number | null;
    unit: string | null;
    notes: string | null;
};

type GalaFees = {
    name: string;
    slug: string;
    starts_on: string;
    entries_close_at: string | null;
    entry_fee: number | null;
    late_entry_fee: number | null;
    late_entry_event_fee: number | null;
    no_show_fine: number | null;
    appeal_deposit: number | null;
};

function money(amount: number | null): string {
    return amount === null ? '—' : `$${amount.toFixed(2)}`;
}

function day(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export default function Fees({
    season,
    fees,
    galas: meets,
}: {
    season: string | null;
    fees: Fee[];
    galas: GalaFees[];
}) {
    return (
        <PublicLayout
            heroImage={photos.pages.about}
            figures={
                season
                    ? [
                          {
                              label: 'Season',
                              // Named "2026/2027 Season", which under a label
                              // reading Season says it twice.
                              value: season.replace(/\s*Season$/i, ''),
                          },
                      ]
                    : undefined
            }
            title="Fees at a glance"
            description="What a club, a province and a swimmer pay this season, with the late fees and fines that apply once a deadline has passed."
        >
            <Head title="Fees" />

            <Section>
                {/*
                 * First thing on the page, because a club that pays the right
                 * amount to the wrong place has still not entered anybody.
                 */}
                <div className="flex items-start gap-3 rounded-xl border border-brand-gold/40 bg-brand-gold/10 p-5">
                    <Info className="mt-0.5 size-5 shrink-0 text-foreground" />
                    <p className="text-sm">
                        <span className="font-semibold">
                            Entries are accepted through provincial programme
                            convenors only.
                        </span>{' '}
                        A club lodges its entries and its fees with its
                        province, and the province carries them to the national
                        body. Entries sent directly are not recorded.
                    </p>
                </div>

                <div className="mt-14">
                    <FlagRule />
                    <h2 className="mt-4 text-2xl font-bold tracking-tight">
                        Affiliation and registration
                    </h2>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        Payable for the season, through the province.
                    </p>

                    {fees.length === 0 ? (
                        <p className="mt-6 rounded-lg border border-dashed border-border p-10 text-center text-muted-foreground">
                            The fee schedule for this season has not been
                            published yet.
                        </p>
                    ) : (
                        <ul className="mt-6 divide-y divide-border overflow-hidden rounded-xl border border-border">
                            {fees.map((fee) => (
                                <li
                                    key={fee.name}
                                    className="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 bg-card p-5"
                                >
                                    <div>
                                        <p className="font-semibold">
                                            {fee.name}
                                        </p>
                                        {fee.notes && (
                                            <p className="text-sm text-muted-foreground">
                                                {fee.notes}
                                            </p>
                                        )}
                                    </div>
                                    <p className="shrink-0 text-right">
                                        <span className="text-lg font-bold tracking-tight">
                                            {fee.amount === null
                                                ? 'To be advised'
                                                : money(fee.amount)}
                                        </span>
                                        {fee.unit && (
                                            <span className="ml-2 text-sm text-muted-foreground">
                                                {fee.unit}
                                            </span>
                                        )}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="mt-14">
                    <FlagRule />
                    <h2 className="mt-4 text-2xl font-bold tracking-tight">
                        Entering a gala
                    </h2>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        Entry is per swimmer. Once entries have closed, the late
                        fee applies in place of the entry fee, plus a charge for
                        each event added.
                    </p>

                    {meets.length === 0 ? (
                        <p className="mt-6 rounded-lg border border-dashed border-border p-10 text-center text-muted-foreground">
                            No gala on this season&rsquo;s calendar has been
                            priced yet.
                        </p>
                    ) : (
                        <div className="mt-6 overflow-x-auto rounded-xl border border-border">
                            <table className="w-full min-w-[46rem] text-sm">
                                <thead className="bg-brand-surface/60 text-left">
                                    <tr>
                                        <th className="p-4 font-semibold">
                                            Gala
                                        </th>
                                        <th className="p-4 text-right font-semibold">
                                            Entry
                                        </th>
                                        <th className="p-4 text-right font-semibold">
                                            Late entry
                                        </th>
                                        <th className="p-4 text-right font-semibold">
                                            Late, per event
                                        </th>
                                        <th className="p-4 text-right font-semibold">
                                            No-show fine
                                        </th>
                                        <th className="p-4 text-right font-semibold">
                                            Appeal deposit
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border bg-card">
                                    {meets.map((meet) => (
                                        <tr key={meet.slug}>
                                            <td className="p-4">
                                                <Link
                                                    href={
                                                        galas.show(meet.slug)
                                                            .url
                                                    }
                                                    className="font-semibold hover:text-primary"
                                                >
                                                    {meet.name}
                                                </Link>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    {meet.entries_close_at
                                                        ? `Entries close ${day(meet.entries_close_at)}`
                                                        : day(meet.starts_on)}
                                                </p>
                                            </td>
                                            <td className="p-4 text-right font-semibold">
                                                {money(meet.entry_fee)}
                                            </td>
                                            <td className="p-4 text-right">
                                                {money(meet.late_entry_fee)}
                                            </td>
                                            <td className="p-4 text-right">
                                                {money(
                                                    meet.late_entry_event_fee,
                                                )}
                                            </td>
                                            <td className="p-4 text-right">
                                                {money(meet.no_show_fine)}
                                            </td>
                                            <td className="p-4 text-right">
                                                {money(meet.appeal_deposit)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <p className="mt-4 text-sm text-muted-foreground">
                        A dash means the fee has not been set for that gala. The
                        gala&rsquo;s own page carries its entry instructions and
                        the address entries are lodged at.
                    </p>
                </div>
            </Section>
        </PublicLayout>
    );
}
