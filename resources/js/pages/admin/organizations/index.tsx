import { Head, Link, router } from '@inertiajs/react';
import { Globe, LayoutList, Network, Plus } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { ExportButton } from '@/components/admin/export-button';
import { OrgTree, type OrgNode } from '@/components/admin/org-tree';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import { organizations as exportOrganizations } from '@/routes/admin/exports';
import type { Option, Paginated } from '@/types';

type OrganizationRow = {
    id: number;
    name: string;
    slug: string;
    code: string | null;
    type: string;
    type_label: string;
    parent: string | null;
    city: string | null;
    email: string | null;
    affiliation_status: string;
    is_active: boolean;
    swimmers_count: number;
    children_count: number;
};

const tierTints: Record<string, string> = {
    national: 'bg-brand-green',
    province: 'bg-brand-deep',
    club: 'bg-viz-5',
};

export default function OrganizationsIndex({
    view,
    tiers,
    tree,
    organizations,
    filters,
    types,
}: {
    view: 'hierarchy' | 'table';
    tiers: { value: string; label: string; count: number }[];
    tree: OrgNode[];
    organizations: Paginated<OrganizationRow> | null;
    filters: { search: string | null; type: string | null };
    types: Option[];
}) {
    useFlashMessages();
    const { can } = usePermissions();

    const goTo = (params: Record<string, string>) =>
        router.get(admin.organizations.index().url, params, {
            preserveScroll: true,
        });

    const views = [
        { key: 'hierarchy', label: 'Hierarchy', icon: Network },
        { key: 'table', label: 'Table', icon: LayoutList },
    ] as const;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                {
                    title: 'Organisations',
                    href: admin.organizations.index().url,
                },
            ]}
        >
            <Head title="Organisations" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Organisations"
                    description="The national body, its provinces and their affiliated clubs. Your access follows this tree."
                    actions={
                        <>
                            <ExportButton
                                url={exportOrganizations().url}
                                params={{
                                    search: filters.search,
                                    type: filters.type,
                                }}
                            />
                            {can('organizations.manage') && (
                                <Button asChild>
                                    <Link href={admin.organizations.create()}>
                                        <Plus className="size-4" />
                                        Add organisation
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                {/*
                 * The tiers of the structure, counted. A reader arriving
                 * here first wants to know how big the thing is and what it
                 * is made of, before any one body in it.
                 */}
                <div className="grid grid-cols-1 divide-y divide-border overflow-hidden rounded-xl border border-border bg-card sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    {tiers.map((tier) => (
                        <button
                            key={tier.value}
                            type="button"
                            onClick={() =>
                                goTo({ view: 'table', type: tier.value })
                            }
                            className="flex items-center gap-4 p-5 text-left transition-colors hover:bg-muted/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:-outline-offset-2 focus-visible:outline-none"
                        >
                            <span
                                aria-hidden="true"
                                className={cn(
                                    'h-10 w-1 shrink-0 rounded-full',
                                    tierTints[tier.value],
                                )}
                            />
                            <span>
                                <span className="block text-3xl leading-none font-bold tabular-nums">
                                    {tier.count}
                                </span>
                                <span className="mt-1.5 block text-sm text-muted-foreground">
                                    {tier.count === 1 &&
                                    tier.value !== 'national'
                                        ? tier.label
                                        : `${tier.label}${tier.value === 'national' ? '' : 's'}`}
                                </span>
                            </span>
                        </button>
                    ))}
                </div>

                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="inline-flex rounded-lg border border-border bg-card p-1">
                        {views.map((option) => {
                            const active = view === option.key;

                            return (
                                <button
                                    key={option.key}
                                    type="button"
                                    aria-pressed={active}
                                    onClick={() => goTo({ view: option.key })}
                                    className={cn(
                                        'inline-flex items-center gap-2 rounded-md px-3.5 py-1.5 text-sm font-medium transition-colors',
                                        active
                                            ? 'bg-brand-deep text-white'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    <option.icon className="size-4" />
                                    {option.label}
                                </button>
                            );
                        })}
                    </div>

                    {view === 'table' && (
                        <div className="flex flex-wrap gap-1.5">
                            <Button
                                size="sm"
                                variant={filters.type ? 'outline' : 'default'}
                                onClick={() => goTo({ view: 'table' })}
                            >
                                All
                            </Button>
                            {types.map((type) => (
                                <Button
                                    key={type.value}
                                    size="sm"
                                    variant={
                                        filters.type === type.value
                                            ? 'default'
                                            : 'outline'
                                    }
                                    onClick={() =>
                                        goTo({
                                            view: 'table',
                                            type: type.value,
                                        })
                                    }
                                >
                                    {type.label}
                                </Button>
                            ))}
                        </div>
                    )}
                </div>

                {view === 'hierarchy' ? (
                    tree.length === 0 ? (
                        <EmptyState
                            icon={Globe}
                            title="No organisations yet"
                            description="Clubs affiliate to a province, and provinces to Zimbabwe Swimming."
                        />
                    ) : (
                        <OrgTree
                            nodes={tree}
                            canManage={can('organizations.manage')}
                        />
                    )
                ) : organizations === null ||
                  organizations.data.length === 0 ? (
                    <EmptyState
                        icon={Globe}
                        title="No organisations found"
                        description="Clubs affiliate to a province, and provinces to Zimbabwe Swimming."
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="w-full min-w-[820px] text-sm">
                                <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Organisation
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Type
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Affiliated to
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Swimmers
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Affiliation
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {organizations.data.map((organization) => (
                                        <tr
                                            key={organization.id}
                                            className="border-b border-border/60 bg-card last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <p className="font-medium">
                                                    {organization.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {[
                                                        organization.code,
                                                        organization.city,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="secondary">
                                                    {organization.type_label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {organization.parent ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {organization.swimmers_count}
                                            </td>
                                            <td className="px-4 py-3">
                                                {/*
                                                 * Only an affiliation that
                                                 * is not in order earns a
                                                 * badge. A green chip on
                                                 * every row hides the one
                                                 * that matters.
                                                 */}
                                                {organization.affiliation_status ===
                                                'affiliated' ? (
                                                    <span className="text-muted-foreground">
                                                        In order
                                                    </span>
                                                ) : (
                                                    <Badge
                                                        variant="outline"
                                                        className="text-brand-red capitalize"
                                                    >
                                                        {
                                                            organization.affiliation_status
                                                        }
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {can(
                                                    'organizations.manage',
                                                ) && (
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="ghost"
                                                    >
                                                        <Link
                                                            href={admin.organizations.edit(
                                                                organization.slug,
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
                        <Pagination meta={organizations} />
                    </>
                )}
            </div>
        </AppLayout>
    );
}
