import { Head, Link, router } from '@inertiajs/react';
import { Award, ArrowLeft } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';

export default function Enrolments({
    course,
    enrolments,
    can,
}: {
    course: { slug: string; title: string };
    enrolments: {
        id: number;
        name: string;
        email: string;
        status: string;
        status_label: string;
        progress: number;
        theory_passed_at: string | null;
        practical_done: number;
        practical_required: number;
        ready_for_certificate: boolean;
        attempts_used: number;
    }[];
    can: { certify: boolean };
}) {
    useFlashMessages();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Academy', href: admin.academy.index().url },
                {
                    title: course.title,
                    href: admin.academy.edit(course.slug).url,
                },
                { title: 'Enrolments', href: '#' },
            ]}
        >
            <Head title={`${course.title} enrolments`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.academy.index()}>
                        <ArrowLeft className="size-4" />
                        All courses
                    </Link>
                </Button>

                <PageHeader
                    title="Enrolments"
                    description={`${course.title} · theory and practical progress for everyone taking this course`}
                />

                {enrolments.length === 0 ? (
                    <EmptyState
                        icon={Award}
                        title="Nobody enrolled yet"
                        description="Share the Academy with your clubs and parents."
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="w-full min-w-[760px] text-sm">
                            <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Learner
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Lessons
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Exam
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Practicals
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {enrolments.map((enrolment) => (
                                    <tr
                                        key={enrolment.id}
                                        className="border-b border-border/60 bg-card last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            <p className="font-medium">
                                                {enrolment.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {enrolment.email}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 tabular-nums">
                                            {enrolment.progress}%
                                        </td>
                                        <td className="px-4 py-3">
                                            {enrolment.theory_passed_at ? (
                                                <span className="text-brand-green">
                                                    Passed{' '}
                                                    {enrolment.theory_passed_at}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    {enrolment.attempts_used}{' '}
                                                    attempts
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 tabular-nums">
                                            {enrolment.practical_done} /{' '}
                                            {enrolment.practical_required}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant="outline">
                                                {enrolment.status_label}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {can.certify &&
                                                enrolment.ready_for_certificate && (
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            router.post(
                                                                admin.academy.certificate.issue(
                                                                    [
                                                                        course.slug,
                                                                        enrolment.id,
                                                                    ],
                                                                ).url,
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        <Award className="size-3.5" />
                                                        Issue certificate
                                                    </Button>
                                                )}
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
