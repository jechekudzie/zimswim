import { Head, Link } from '@inertiajs/react';
import { Award, GraduationCap, ShieldCheck } from 'lucide-react';

import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import academy from '@/routes/academy';
import certificates from '@/routes/certificates';

type CourseCard = {
    slug: string;
    title: string;
    summary: string | null;
    category: string | null;
    grade: string | null;
    lessons_count: number;
    exam_minutes: number;
    pass_mark: number;
    practical_sessions_required: number;
    minimum_age: number | null;
    fee: string;
    enrolled: boolean;
    status: string | null;
    progress: number;
};

export default function AcademyIndex({
    courses,
    certificates: held,
}: {
    courses: CourseCard[];
    certificates: {
        code: string;
        title: string;
        issued_on: string;
        expires_on: string | null;
        valid: boolean;
    }[];
}) {
    useFlashMessages();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Officials Academy', href: academy.index().url },
            ]}
        >
            <Head title="Officials Academy" />

            <div className="flex flex-col gap-8 p-4 sm:p-6">
                <PageHeader
                    title="Officials Academy"
                    description="Online theory you can take on a phone, a short exam, and poolside practicals signed off at a gala. Every qualified official started as a learner."
                />

                <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    {courses.map((course) => (
                        <article
                            key={course.slug}
                            className="flex flex-col rounded-xl border border-border bg-card p-6"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <GraduationCap className="size-6 text-primary" />
                                {course.enrolled && (
                                    <Badge variant="secondary">
                                        {course.status}
                                    </Badge>
                                )}
                            </div>

                            <h2 className="mt-4 font-semibold text-balance">
                                {course.title}
                            </h2>
                            {course.summary && (
                                <p className="mt-2 line-clamp-3 text-sm text-muted-foreground">
                                    {course.summary}
                                </p>
                            )}

                            <dl className="mt-5 grid grid-cols-2 gap-3 border-t border-border pt-4 text-xs">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Lessons
                                    </dt>
                                    <dd className="font-semibold tabular-nums">
                                        {course.lessons_count}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Exam
                                    </dt>
                                    <dd className="font-semibold">
                                        {course.exam_minutes} min ·{' '}
                                        {course.pass_mark}%
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Practicals
                                    </dt>
                                    <dd className="font-semibold tabular-nums">
                                        {course.practical_sessions_required}{' '}
                                        sessions
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Minimum age
                                    </dt>
                                    <dd className="font-semibold">
                                        {course.minimum_age ?? '—'}
                                    </dd>
                                </div>
                            </dl>

                            {course.enrolled && (
                                <div className="mt-4">
                                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full rounded-full bg-primary"
                                            style={{
                                                width: `${course.progress}%`,
                                            }}
                                        />
                                    </div>
                                    <p className="mt-1.5 text-xs text-muted-foreground">
                                        {course.progress}% of lessons complete
                                    </p>
                                </div>
                            )}

                            <Button asChild className="mt-6 w-full">
                                <Link href={academy.show(course.slug)}>
                                    {course.enrolled
                                        ? 'Continue'
                                        : 'View course'}
                                </Link>
                            </Button>
                        </article>
                    ))}
                </div>

                {held.length > 0 && (
                    <section>
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <Award className="size-5 text-primary" />
                            Your certificates
                        </h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {held.map((certificate) => (
                                <div
                                    key={certificate.code}
                                    className="rounded-xl border border-border bg-card p-5"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <h3 className="font-semibold">
                                            {certificate.title}
                                        </h3>
                                        <Badge
                                            variant={
                                                certificate.valid
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            {certificate.valid
                                                ? 'Valid'
                                                : 'Expired'}
                                        </Badge>
                                    </div>
                                    <p className="mt-2 font-mono text-xs text-muted-foreground">
                                        {certificate.code}
                                    </p>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Issued {certificate.issued_on}
                                        {certificate.expires_on
                                            ? ` · expires ${certificate.expires_on}`
                                            : ''}
                                    </p>
                                    <Button
                                        asChild
                                        variant="outline"
                                        size="sm"
                                        className="mt-4"
                                    >
                                        <Link
                                            href={certificates.verify(
                                                certificate.code,
                                            )}
                                        >
                                            <ShieldCheck className="size-3.5" />
                                            Verification page
                                        </Link>
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
