import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download, Pencil, Users } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';

type Registration = {
    name: string;
    email: string;
    phone: string | null;
    club_name: string | null;
    notes: string | null;
    registration_number: string | null;
    registered_at: string;
};

export default function TrainingShow({
    training,
    registrations,
    can,
}: {
    training: {
        id: number;
        slug: string;
        title: string;
        kind: string;
        category: string | null;
        host: string;
        held_on: string;
        venue: string | null;
        capacity: number | null;
        is_published: boolean;
    };
    registrations: Registration[];
    can: { manage: boolean };
}) {
    useFlashMessages();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                {
                    title: 'Clinics and exams',
                    href: admin.training.index().url,
                },
                {
                    title: training.title,
                    href: admin.training.show(training.slug).url,
                },
            ]}
        >
            <Head title={training.title} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title={training.title}
                    description={[
                        training.kind,
                        training.category,
                        training.host,
                        training.held_on,
                        training.venue,
                    ]
                        .filter(Boolean)
                        .join(' · ')}
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link href={admin.training.index()}>
                                    <ArrowLeft className="size-4" />
                                    Back
                                </Link>
                            </Button>
                            {registrations.length > 0 && (
                                <Button variant="outline" asChild>
                                    <a
                                        href={
                                            admin.training.export(training.slug)
                                                .url
                                        }
                                    >
                                        <Download className="size-4" />
                                        Attendance list
                                    </a>
                                </Button>
                            )}
                            {can.manage && (
                                <>
                                    <Button asChild>
                                        <Link
                                            href={admin.training.edit(
                                                training.slug,
                                            )}
                                        >
                                            <Pencil className="size-4" />
                                            Edit
                                        </Link>
                                    </Button>
                                    <Form
                                        {...admin.training.destroy.form(
                                            training.slug,
                                        )}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                disabled={processing}
                                            >
                                                Remove
                                            </Button>
                                        )}
                                    </Form>
                                </>
                            )}
                        </>
                    }
                />

                <div className="flex flex-wrap items-center gap-3">
                    <Badge
                        variant={
                            training.is_published ? 'default' : 'secondary'
                        }
                    >
                        {training.is_published
                            ? 'On the public site'
                            : 'Draft — not shown publicly'}
                    </Badge>
                    <span className="text-sm text-muted-foreground">
                        {registrations.length} registered
                        {training.capacity !== null &&
                            ` of ${training.capacity} places`}
                    </span>
                </div>

                {registrations.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title="Nobody has registered yet"
                        description={
                            training.is_published
                                ? 'The session is on the public site. Names appear here as people put them down.'
                                : 'Publish the session and it appears on the public site, where people can register.'
                        }
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="w-full min-w-[760px] text-sm">
                            <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Contact
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Club
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        On the register
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Registered
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {registrations.map((registration) => (
                                    <tr
                                        key={registration.email}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3">
                                            <p className="font-medium">
                                                {registration.name}
                                            </p>
                                            {registration.notes && (
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    {registration.notes}
                                                </p>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            <p>{registration.email}</p>
                                            {registration.phone && (
                                                <p className="text-xs">
                                                    {registration.phone}
                                                </p>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {registration.club_name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {registration.registration_number ? (
                                                <span className="font-mono text-xs">
                                                    {
                                                        registration.registration_number
                                                    }
                                                </span>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">
                                                    New to officiating
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {registration.registered_at}
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
