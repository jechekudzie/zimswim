import { Head, Link, router } from '@inertiajs/react';
import { Building2, Check, Minus, Plus } from 'lucide-react';

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
import { venues as exportVenues } from '@/routes/admin/exports';
import type { Option, Paginated } from '@/types';

type VenueRow = {
    id: number;
    slug: string;
    name: string;
    city: string | null;
    province: string | null;
    pool_length: number | null;
    lanes: number | null;
    grade: string;
    grade_label: string;
    electronic_timing: boolean;
    starting_blocks: boolean;
    is_active: boolean;
    condition_notes: string | null;
};

export default function VenuesIndex({
    venues,
    filters,
    grades,
}: {
    venues: Paginated<VenueRow>;
    filters: { search: string | null; grade: string | null };
    grades: Option[];
}) {
    useFlashMessages();
    const { can } = usePermissions();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Venues', href: admin.venues.index().url },
            ]}
        >
            <Head title="Venues" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Venue register"
                    description="Each pool is graded for the level of gala it can carry. The gala module checks this grade before a gala is sanctioned."
                    actions={
                        <>
                            <ExportButton
                                url={exportVenues().url}
                                params={{
                                    search: filters.search,
                                    grade: filters.grade,
                                }}
                            />
                            {can('venues.manage') && (
                                <Button asChild>
                                    <Link href={admin.venues.create()}>
                                        <Plus className="size-4" />
                                        Add venue
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                <div className="flex flex-wrap gap-1.5">
                    <Button
                        size="sm"
                        variant={filters.grade ? 'outline' : 'default'}
                        onClick={() =>
                            router.get(
                                admin.venues.index().url,
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        All grades
                    </Button>
                    {grades.map((grade) => (
                        <Button
                            key={grade.value}
                            size="sm"
                            variant={
                                filters.grade === grade.value
                                    ? 'default'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    admin.venues.index().url,
                                    { grade: grade.value },
                                    { preserveScroll: true },
                                )
                            }
                        >
                            {grade.label}
                        </Button>
                    ))}
                </div>

                {venues.data.length === 0 ? (
                    <EmptyState
                        icon={Building2}
                        title="No venues yet"
                        description="Add the pools in your area so galas can be booked against real facilities."
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="w-full min-w-[820px] text-sm">
                                <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Venue
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Province
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Pool
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Blocks
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Timing
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Grade
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {venues.data.map((venue) => (
                                        <tr
                                            key={venue.id}
                                            className="border-b border-border/60 bg-card last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <p className="font-medium">
                                                    {venue.name}
                                                </p>
                                                {venue.city && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {venue.city}
                                                    </p>
                                                )}
                                                {venue.condition_notes && (
                                                    <p className="mt-1 text-xs text-brand-red">
                                                        {venue.condition_notes}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {venue.province ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {venue.pool_length
                                                    ? `${venue.pool_length}m`
                                                    : '—'}
                                                {venue.lanes
                                                    ? ` · ${venue.lanes} lanes`
                                                    : ''}
                                            </td>
                                            <td className="px-4 py-3">
                                                {venue.starting_blocks ? (
                                                    <Check className="size-4 text-brand-green" />
                                                ) : (
                                                    <Minus className="size-4 text-muted-foreground" />
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {venue.electronic_timing ? (
                                                    <Check className="size-4 text-brand-green" />
                                                ) : (
                                                    <Minus className="size-4 text-muted-foreground" />
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        venue.grade ===
                                                        'national'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {venue.grade_label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {can('venues.manage') && (
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="ghost"
                                                    >
                                                        <Link
                                                            href={admin.venues.edit(
                                                                venue.slug,
                                                            )}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination meta={venues} />
                    </>
                )}
            </div>
        </AppLayout>
    );
}
