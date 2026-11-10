import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, Plus } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { ExportButton } from '@/components/admin/export-button';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import { galas as exportGalas } from '@/routes/admin/exports';
import type { Option, Paginated } from '@/types';

type GalaRow = {
    id: number;
    slug: string;
    name: string;
    level_label: string;
    status: string;
    status_label: string;
    is_sanctioned: boolean;
    starts_on: string;
    ends_on: string;
    venue: string | null;
    host: string;
    entries_count: number;
    is_published: boolean;
};

const statusTone: Record<string, string> = {
    submitted: 'border-brand-gold/60 text-foreground',
    sanctioned: 'border-brand-green/40 text-brand-green',
    rejected: 'border-brand-red/40 text-brand-red',
    cancelled: 'border-brand-red/40 text-brand-red',
};

export default function GalasIndex({
    galas,
    filters,
    statuses,
    levels,
}: {
    galas: Paginated<GalaRow>;
    filters: { status: string | null; level: string | null };
    statuses: Option[];
    levels: Option[];
}) {
    useFlashMessages();
    const { can } = usePermissions();

    function apply(next: Record<string, string | null>) {
        router.get(
            admin.galas.index().url,
            { ...filters, ...next },
            { preserveScroll: true },
        );
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Galas', href: admin.galas.index().url },
            ]}
        >
            <Head title="Galas" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Galas"
                    description="A gala moves from request to sanction to results. Only sanctioned galas appear on the public calendar."
                    actions={
                        <>
                            <ExportButton
                                url={exportGalas().url}
                                params={{
                                    status: filters.status,
                                    level: filters.level,
                                }}
                            />
                            {can('galas.manage') && (
                                <Button asChild>
                                    <Link href={admin.galas.create()}>
                                        <Plus className="size-4" />
                                        Request a gala
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                <div className="flex flex-wrap gap-4">
                    <div className="flex flex-wrap gap-1.5">
                        <Button
                            size="sm"
                            variant={filters.status ? 'outline' : 'default'}
                            onClick={() => apply({ status: null })}
                        >
                            Any status
                        </Button>
                        {statuses.slice(0, 6).map((status) => (
                            <Button
                                key={status.value}
                                size="sm"
                                variant={
                                    filters.status === status.value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => apply({ status: status.value })}
                            >
                                {status.label}
                            </Button>
                        ))}
                    </div>
                    <div className="flex flex-wrap gap-1.5">
                        {levels.map((level) => (
                            <Button
                                key={level.value}
                                size="sm"
                                variant={
                                    filters.level === level.value
                                        ? 'secondary'
                                        : 'ghost'
                                }
                                onClick={() =>
                                    apply({
                                        level:
                                            filters.level === level.value
                                                ? null
                                                : level.value,
                                    })
                                }
                            >
                                {level.label}
                            </Button>
                        ))}
                    </div>
                </div>

                {galas.data.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title="No galas yet"
                        description="Submit a gala request with dates, venue and level for the board to sanction."
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="w-full min-w-[840px] text-sm">
                                <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Gala
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Dates
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Venue
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Level
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Entries
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {galas.data.map((gala) => (
                                        <tr
                                            key={gala.id}
                                            className="border-b border-border/60 bg-card last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={admin.galas.show(
                                                        gala.slug,
                                                    )}
                                                    className="font-medium hover:text-primary"
                                                >
                                                    {gala.name}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {gala.host}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground tabular-nums">
                                                {gala.starts_on}
                                                {gala.ends_on !== gala.starts_on
                                                    ? ` – ${gala.ends_on}`
                                                    : ''}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {gala.venue ?? (
                                                    <span className="text-brand-red">
                                                        Not booked
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="secondary">
                                                    {gala.level_label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {gala.entries_count}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        statusTone[
                                                            gala.status
                                                        ] ?? ''
                                                    }
                                                >
                                                    {gala.status_label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="ghost"
                                                >
                                                    <Link
                                                        href={admin.galas.show(
                                                            gala.slug,
                                                        )}
                                                    >
                                                        Open
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination meta={galas} />
                    </>
                )}
            </div>
        </AppLayout>
    );
}
