import { Head, Link, router } from '@inertiajs/react';
import { Plus, ShieldAlert, Waves } from 'lucide-react';
import { useState } from 'react';

import { EmptyState } from '@/components/admin/empty-state';
import {
    ScopeFilters,
    type ClubOption,
    type ProvinceOption,
} from '@/components/admin/scope-filters';
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
import { members as exportMembers } from '@/routes/admin/exports';
import type { Option, Paginated, SeasonSummary } from '@/types';

type MemberRow = {
    id: number;
    membership_number: string;
    name: string;
    date_of_birth: string;
    age: number | null;
    gender: string;
    status: string;
    status_label: string;
    club: string;
    province: string | null;
    has_consent: boolean;
};

const statusTone: Record<string, string> = {
    active: 'border-brand-green/40 text-brand-green',
    pending: 'border-brand-gold/50 text-foreground',
    lapsed: 'text-muted-foreground',
    suspended: 'border-brand-red/40 text-brand-red',
};

export default function MembersIndex({
    members,
    filters,
    statuses,
    provinces,
    clubs,
    season,
}: {
    members: Paginated<MemberRow>;
    filters: {
        search: string | null;
        status: string | null;
        province: string | null;
        club: string | null;
    };
    statuses: Option[];
    provinces: ProvinceOption[];
    clubs: ClubOption[];
    season: SeasonSummary | null;
}) {
    useFlashMessages();
    const { can } = usePermissions();
    const [search, setSearch] = useState(filters.search ?? '');

    function apply(next: Record<string, string | null>) {
        router.get(
            admin.members.index().url,
            {
                search,
                status: filters.status,
                province: filters.province,
                club: filters.club,
                ...next,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Swimmers', href: admin.members.index().url },
            ]}
        >
            <Head title="Swimmers" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Swimmers"
                    description={
                        season
                            ? `Age group is taken at ${season.age_up_date}, so a swimmer can never be entered in the wrong group.`
                            : undefined
                    }
                    actions={
                        <>
                            <ExportButton
                                url={exportMembers().url}
                                params={{
                                    search: filters.search,
                                    status: filters.status,
                                    province: filters.province,
                                    club: filters.club,
                                }}
                            />
                            {can('members.manage') && (
                                <Button asChild>
                                    <Link href={admin.members.create()}>
                                        <Plus className="size-4" />
                                        Register swimmer
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
                            placeholder="Search name or ZimSwim number"
                        />
                    </form>

                    <ScopeFilters
                        provinces={provinces}
                        clubs={clubs}
                        province={filters.province}
                        club={filters.club}
                        onChange={(next) => apply(next)}
                    />

                    <div className="flex flex-wrap gap-1.5">
                        <Button
                            size="sm"
                            variant={filters.status ? 'outline' : 'default'}
                            onClick={() => apply({ status: null })}
                        >
                            All
                        </Button>
                        {statuses.map((status) => (
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
                </div>

                {members.data.length === 0 ? (
                    <EmptyState
                        icon={Waves}
                        title="No swimmers found"
                        description="Register swimmers against a club so they can be entered into galas."
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="w-full min-w-[760px] text-sm">
                                <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            ZimSwim number
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Name
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Age
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Club
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Province
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {members.data.map((member) => (
                                        <tr
                                            key={member.id}
                                            className="border-b border-border/60 bg-card last:border-0"
                                        >
                                            <td className="px-4 py-3 font-mono text-xs">
                                                {member.membership_number}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                <span className="flex items-center gap-2">
                                                    {member.name}
                                                    {!member.has_consent && (
                                                        <ShieldAlert
                                                            className="size-3.5 text-brand-red"
                                                            aria-label="Guardian consent missing"
                                                        />
                                                    )}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {member.age ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {member.club}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {member.province ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        statusTone[
                                                            member.status
                                                        ] ?? ''
                                                    }
                                                >
                                                    {member.status_label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {can('members.manage') && (
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="ghost"
                                                    >
                                                        <Link
                                                            href={admin.members.edit(
                                                                member.id,
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
                        <Pagination meta={members} />
                    </>
                )}
            </div>
        </AppLayout>
    );
}
