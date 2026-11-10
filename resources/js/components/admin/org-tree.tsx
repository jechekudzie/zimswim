import { Link } from '@inertiajs/react';
import { Building2, ChevronRight, Globe, Landmark, Waves } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

export type OrgNode = {
    id: number;
    name: string;
    slug: string;
    code: string | null;
    type: string;
    type_label: string;
    city: string | null;
    affiliation_status: string;
    affiliation_label: string;
    is_active: boolean;
    swimmers: number;
    total_swimmers: number;
    children: OrgNode[];
};

/**
 * A colour and a mark per tier, fixed to the tier rather than to depth, so a
 * province looks the same whether the reader is the national board seeing it
 * nested or a provincial administrator seeing it at the root.
 */
const tiers: Record<
    string,
    { rail: string; icon: typeof Globe; chip: string }
> = {
    national: {
        rail: 'bg-brand-green',
        icon: Landmark,
        chip: 'bg-brand-green/10 text-brand-green',
    },
    province: {
        rail: 'bg-brand-deep',
        icon: Globe,
        chip: 'bg-brand-deep/10 text-brand-deep',
    },
    club: {
        rail: 'bg-viz-5',
        icon: Waves,
        chip: 'bg-viz-5/15 text-foreground',
    },
};

function Row({
    node,
    depth,
    canManage,
}: {
    node: OrgNode;
    depth: number;
    canManage: boolean;
}) {
    const hasChildren = node.children.length > 0;
    // Open the national body and any province that actually carries clubs,
    // so the structure is visible on arrival rather than behind eleven
    // clicks; an empty province has nothing to open.
    const [open, setOpen] = useState(depth === 0 || hasChildren);
    const tier = tiers[node.type] ?? tiers.province;
    const Icon = tier?.icon ?? Building2;
    const childNoun = node.type === 'national' ? 'province' : 'club';

    return (
        <li>
            <div
                className={cn(
                    'group relative flex flex-wrap items-center gap-x-4 gap-y-2 rounded-lg border border-border bg-card py-3 pr-3 pl-5 transition-colors hover:border-brand-deep/35',
                    depth === 0 && 'border-border/80 shadow-xs',
                )}
            >
                <span
                    aria-hidden="true"
                    className={cn(
                        'absolute inset-y-0 left-0 w-1 rounded-l-lg',
                        tier?.rail,
                    )}
                />

                {hasChildren ? (
                    <button
                        type="button"
                        onClick={() => setOpen(!open)}
                        aria-expanded={open}
                        aria-label={`${open ? 'Collapse' : 'Expand'} ${node.name}`}
                        className="-ml-1 shrink-0 rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <ChevronRight
                            className={cn(
                                'size-4 transition-transform duration-150 motion-reduce:transition-none',
                                open && 'rotate-90',
                            )}
                        />
                    </button>
                ) : (
                    <span aria-hidden="true" className="w-5 shrink-0" />
                )}

                <Icon className="size-4 shrink-0 text-muted-foreground" />

                <div className="min-w-0 flex-1">
                    <p className="font-medium">{node.name}</p>
                    <p className="text-xs text-muted-foreground">
                        {[node.code, node.city].filter(Boolean).join(' · ') ||
                            'No code recorded'}
                    </p>
                </div>

                <span
                    className={cn(
                        'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium',
                        tier?.chip,
                    )}
                >
                    {node.type_label}
                </span>

                {hasChildren ? (
                    <span className="shrink-0 text-sm text-muted-foreground tabular-nums">
                        {node.children.length} {childNoun}
                        {node.children.length === 1 ? '' : 's'}
                    </span>
                ) : (
                    node.type === 'province' && (
                        <span className="shrink-0 text-sm text-muted-foreground">
                            No clubs yet
                        </span>
                    )
                )}

                <span className="shrink-0 text-sm tabular-nums">
                    <span className="font-semibold">{node.total_swimmers}</span>
                    <span className="ml-1.5 text-muted-foreground">
                        swimmers
                    </span>
                </span>

                {/*
                 * The affiliation badge only appears when something is
                 * wrong with it. A row of green "Affiliated" chips down
                 * every line says nothing and hides the one that matters.
                 */}
                {node.affiliation_status !== 'affiliated' && (
                    <Badge
                        variant="outline"
                        className="shrink-0 text-brand-red"
                    >
                        {node.affiliation_label}
                    </Badge>
                )}
                {!node.is_active && (
                    <Badge variant="outline" className="shrink-0">
                        Inactive
                    </Badge>
                )}

                {canManage && (
                    <Link
                        href={admin.organizations.edit(node.slug)}
                        className="shrink-0 text-sm font-medium text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-primary focus-visible:opacity-100"
                    >
                        Edit
                    </Link>
                )}
            </div>

            {hasChildren && open && (
                <ul className="mt-2 ml-5 space-y-2 border-l border-border pl-5">
                    {node.children.map((child) => (
                        <Row
                            key={child.id}
                            node={child}
                            depth={depth + 1}
                            canManage={canManage}
                        />
                    ))}
                </ul>
            )}
        </li>
    );
}

export function OrgTree({
    nodes,
    canManage,
}: {
    nodes: OrgNode[];
    canManage: boolean;
}) {
    return (
        <ul className="space-y-3">
            {nodes.map((node) => (
                <Row
                    key={node.id}
                    node={node}
                    depth={0}
                    canManage={canManage}
                />
            ))}
        </ul>
    );
}
