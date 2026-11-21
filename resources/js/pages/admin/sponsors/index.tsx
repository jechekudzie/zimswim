import { Head, Link } from '@inertiajs/react';
import { Handshake, Pencil, Plus } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type SponsorRow = {
    id: number;
    slug: string;
    name: string;
    tier: string;
    website: string | null;
    position: number;
    is_active: boolean;
    logo_url: string | null;
};

export default function SponsorsIndex({
    sponsors,
    tiers,
}: {
    sponsors: SponsorRow[];
    tiers: Option[];
}) {
    useFlashMessages();

    const label = (tier: string) =>
        tiers.find((option) => option.value === tier)?.label ?? tier;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Sponsors', href: admin.sponsors.index().url },
            ]}
        >
            <Head title="Sponsors" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Sponsors"
                    description="The board at the foot of the home page, in the order it appears there."
                    actions={
                        <Button asChild>
                            <Link href={admin.sponsors.create()}>
                                <Plus className="size-4" />
                                Add a sponsor
                            </Link>
                        </Button>
                    }
                />

                {sponsors.length === 0 ? (
                    <EmptyState
                        icon={Handshake}
                        title="No sponsors yet"
                        description="Add the people backing Zimbabwean swimming and they will appear at the foot of the home page."
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="w-full min-w-[640px] text-sm">
                            <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Sponsor
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Standing
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Order
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        On the page
                                    </th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {sponsors.map((sponsor) => (
                                    <tr
                                        key={sponsor.id}
                                        className="border-b border-border/60 bg-card last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                {sponsor.logo_url ? (
                                                    <img
                                                        src={sponsor.logo_url}
                                                        alt=""
                                                        className="h-8 w-20 object-contain"
                                                    />
                                                ) : (
                                                    <span className="flex h-8 w-20 items-center justify-center rounded border border-dashed border-border text-[10px] text-muted-foreground">
                                                        No logo
                                                    </span>
                                                )}
                                                <div>
                                                    <p className="font-medium">
                                                        {sponsor.name}
                                                    </p>
                                                    {sponsor.website && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {sponsor.website}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    sponsor.tier === 'principal'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {label(sponsor.tier)}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">
                                            {sponsor.position}
                                        </td>
                                        <td className="px-4 py-3">
                                            {sponsor.is_active ? (
                                                <Badge variant="secondary">
                                                    Showing
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline">
                                                    Hidden
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="ghost"
                                            >
                                                <Link
                                                    href={admin.sponsors.edit(
                                                        sponsor.slug,
                                                    )}
                                                >
                                                    <Pencil className="size-4" />
                                                    Edit
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
