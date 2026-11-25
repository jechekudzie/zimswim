import { Head, Link } from '@inertiajs/react';
import { GraduationCap, Plus } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Paginated } from '@/types';

type SessionRow = {
    id: number;
    slug: string;
    title: string;
    kind: string;
    category: string | null;
    host: string;
    held_on: string;
    venue: string | null;
    registrations: number;
    capacity: number | null;
    is_published: boolean;
    has_finished: boolean;
};

export default function TrainingIndex({
    sessions,
}: {
    sessions: Paginated<SessionRow>;
}) {
    useFlashMessages();
    const { can } = usePermissions();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                {
                    title: 'Clinics and exams',
                    href: admin.training.index().url,
                },
            ]}
        >
            <Head title="Clinics and exams" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Clinics and exams"
                    description="What is on the officials' calendar. A published session appears on the public site and anybody may put their name down for it."
                    actions={
                        can('officials.manage') && (
                            <Button asChild>
                                <Link href={admin.training.create()}>
                                    <Plus className="size-4" />
                                    Schedule a session
                                </Link>
                            </Button>
                        )
                    }
                />

                {sessions.data.length === 0 ? (
                    <EmptyState
                        icon={GraduationCap}
                        title="Nothing on the calendar"
                        description="Schedule a clinic and it appears on the public site, where anybody wanting to officiate can find it and register."
                    />
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="w-full min-w-[780px] text-sm">
                                <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Session
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Held
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Where
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Registered
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {sessions.data.map((session) => (
                                        <tr
                                            key={session.id}
                                            className="hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={admin.training.show(
                                                        session.slug,
                                                    )}
                                                    className="font-medium hover:text-primary"
                                                >
                                                    {session.title}
                                                </Link>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    {[
                                                        session.kind,
                                                        session.category,
                                                        session.host,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3">
                                                {session.held_on}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {session.venue ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {session.registrations}
                                                {session.capacity !== null && (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        / {session.capacity}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        session.is_published
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {session.is_published
                                                        ? session.has_finished
                                                            ? 'Held'
                                                            : 'Published'
                                                        : 'Draft'}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <Pagination meta={sessions} />
                    </>
                )}
            </div>
        </AppLayout>
    );
}
