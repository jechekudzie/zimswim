import { Head, Link, router } from '@inertiajs/react';
import { ClipboardCheck, Plus } from 'lucide-react';
import { useState } from 'react';

import { EmptyState } from '@/components/admin/empty-state';
import { ExportButton } from '@/components/admin/export-button';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import { officials as exportOfficials } from '@/routes/admin/exports';
import type { Paginated } from '@/types';

type OfficialRow = {
    id: number;
    registration_number: string;
    name: string;
    club: string;
    province: string | null;
    status: string;
    accreditations: {
        category: string;
        grade: string;
        status: string;
        expires_on: string | null;
        has_lapsed: boolean;
    }[];
};

export default function OfficialsIndex({
    officials,
    filters,
    categories,
}: {
    officials: Paginated<OfficialRow>;
    filters: { search: string | null; category: string | null };
    categories: { value: string; label: string; minimum_age: number }[];
}) {
    useFlashMessages();
    const { can } = usePermissions();
    const [search, setSearch] = useState(filters.search ?? '');

    function apply(next: Record<string, string | null>) {
        router.get(
            admin.officials.index().url,
            { search, category: filters.category, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Officials', href: admin.officials.index().url },
            ]}
        >
            <Head title="Officials" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Officials register"
                    description="Who is qualified, at what level, and whose accreditation has lapsed. Accreditation lapses after two years without officiating at that level."
                    actions={
                        <>
                            <ExportButton
                                url={exportOfficials().url}
                                params={{
                                    search: filters.search,
                                    category: filters.category,
                                }}
                            />
                            {can('officials.manage') && (
                                <Button asChild>
                                    <Link href={admin.officials.create()}>
                                        <Plus className="size-4" />
                                        Add official
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                <div className="flex flex-wrap items-center gap-3">
                    <form
                        className="flex-1 sm:max-w-xs"
                        onSubmit={(event) => {
                            event.preventDefault();
                            apply({});
                        }}
                    >
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search name or registration number"
                        />
                    </form>

                    <div className="flex flex-wrap gap-1.5">
                        <Button
                            size="sm"
                            variant={filters.category ? 'outline' : 'default'}
                            onClick={() => apply({ category: null })}
                        >
                            All
                        </Button>
                        {categories.map((category) => (
                            <Button
                                key={category.value}
                                size="sm"
                                variant={
                                    filters.category === category.value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() =>
                                    apply({ category: category.value })
                                }
                            >
                                {category.label}
                            </Button>
                        ))}
                    </div>
                </div>

                {officials.data.length === 0 ? (
                    <EmptyState
                        icon={ClipboardCheck}
                        title="No officials found"
                        description="Officials must be registered members of a club and the province in their place of domicile."
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="w-full min-w-[800px] text-sm">
                                <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Number
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Name
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Club
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Accreditations
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {officials.data.map((official) => (
                                        <tr
                                            key={official.id}
                                            className="border-b border-border/60 bg-card last:border-0"
                                        >
                                            <td className="px-4 py-3 font-mono text-xs">
                                                {official.registration_number}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {official.name}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {official.club}
                                                {official.province && (
                                                    <span className="block text-xs">
                                                        {official.province}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {official.accreditations
                                                    .length === 0 ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        None yet
                                                    </span>
                                                ) : (
                                                    <div className="flex flex-wrap gap-1.5">
                                                        {official.accreditations.map(
                                                            (accreditation) => (
                                                                <Badge
                                                                    key={`${accreditation.category}-${accreditation.grade}`}
                                                                    variant="outline"
                                                                    className={
                                                                        accreditation.has_lapsed
                                                                            ? 'border-brand-red/40 text-brand-red'
                                                                            : accreditation.status ===
                                                                                'active'
                                                                              ? 'border-brand-green/40 text-brand-green'
                                                                              : ''
                                                                    }
                                                                >
                                                                    {
                                                                        accreditation.category
                                                                    }
                                                                </Badge>
                                                            ),
                                                        )}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="ghost"
                                                >
                                                    <Link
                                                        href={admin.officials.show(
                                                            official.id,
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
                        <Pagination meta={officials} />
                    </>
                )}
            </div>
        </AppLayout>
    );
}
