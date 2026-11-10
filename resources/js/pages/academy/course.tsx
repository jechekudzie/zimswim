import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Check, CircleDot, FileCheck2, Lock } from 'lucide-react';

import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import academy from '@/routes/academy';

type Enrolment = {
    status: string;
    status_label: string;
    progress: number;
    theory_passed: boolean;
    can_sit_exam: boolean;
    attempts_remaining: number;
    practical_done: number;
    practical_outstanding: number;
    ready_for_certificate: boolean;
    attempts: {
        number: number;
        score: number | null;
        passed: boolean;
        submitted_at: string | null;
    }[];
};

export default function AcademyCourse({
    course,
    lessons,
    enrolment,
}: {
    course: {
        slug: string;
        title: string;
        summary: string | null;
        description: string | null;
        category: string | null;
        grade: string | null;
        pass_mark: number;
        exam_minutes: number;
        exam_question_count: number;
        max_attempts: number;
        practical_sessions_required: number;
        minimum_age: number | null;
        fee: string;
    };
    lessons: {
        slug: string;
        title: string;
        summary: string | null;
        runtime: string | null;
        completed: boolean;
    }[];
    enrolment: Enrolment | null;
}) {
    useFlashMessages();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Officials Academy', href: academy.index().url },
                { title: course.title, href: academy.show(course.slug).url },
            ]}
        >
            <Head title={course.title} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={academy.index()}>
                        <ArrowLeft className="size-4" />
                        All courses
                    </Link>
                </Button>

                <PageHeader
                    title={course.title}
                    description={course.summary ?? undefined}
                    actions={
                        enrolment === null ? (
                            <Button
                                onClick={() =>
                                    router.post(
                                        academy.enrol(course.slug).url,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Enrol on this course
                            </Button>
                        ) : (
                            <Badge variant="secondary">
                                {enrolment.status_label}
                            </Badge>
                        )
                    }
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {course.description && (
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">
                                    About this course
                                </h2>
                                <div className="mt-3 space-y-4 text-sm leading-relaxed text-muted-foreground">
                                    {course.description
                                        .split('\n\n')
                                        .map((paragraph) => (
                                            <p key={paragraph.slice(0, 32)}>
                                                {paragraph}
                                            </p>
                                        ))}
                                </div>
                            </section>
                        )}

                        <section className="overflow-hidden rounded-xl border border-border bg-card">
                            <h2 className="border-b border-border px-6 py-4 font-semibold">
                                Lessons
                            </h2>
                            <ol>
                                {lessons.map((lesson, index) => (
                                    <li
                                        key={lesson.slug}
                                        className="border-b border-border/60 last:border-0"
                                    >
                                        <Link
                                            href={academy.lesson([
                                                course.slug,
                                                lesson.slug,
                                            ])}
                                            className="flex items-start gap-4 px-6 py-4 transition-colors hover:bg-accent/40"
                                        >
                                            {lesson.completed ? (
                                                <Check className="mt-0.5 size-5 shrink-0 text-brand-green" />
                                            ) : (
                                                <CircleDot className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                                            )}
                                            <div className="min-w-0">
                                                <p className="font-medium">
                                                    {index + 1}. {lesson.title}
                                                </p>
                                                {lesson.summary && (
                                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                                        {lesson.summary}
                                                    </p>
                                                )}
                                            </div>
                                            {lesson.runtime && (
                                                <span className="ml-auto shrink-0 text-xs text-muted-foreground tabular-nums">
                                                    {lesson.runtime}
                                                </span>
                                            )}
                                        </Link>
                                    </li>
                                ))}
                            </ol>
                        </section>
                    </div>

                    <aside className="space-y-6">
                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">The exam</h2>
                            <dl className="mt-4 space-y-3 text-sm">
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted-foreground">
                                        Questions
                                    </dt>
                                    <dd className="font-medium tabular-nums">
                                        {course.exam_question_count}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted-foreground">
                                        Time limit
                                    </dt>
                                    <dd className="font-medium">
                                        {course.exam_minutes} minutes
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted-foreground">
                                        Pass mark
                                    </dt>
                                    <dd className="font-medium">
                                        {course.pass_mark}%
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted-foreground">
                                        Attempts
                                    </dt>
                                    <dd className="font-medium tabular-nums">
                                        {course.max_attempts}
                                    </dd>
                                </div>
                            </dl>

                            <p className="mt-4 text-xs text-muted-foreground">
                                Questions are drawn at random from a bank, so no
                                two exams are the same.
                            </p>

                            {enrolment === null ? (
                                <p className="mt-5 flex items-center gap-2 text-sm text-muted-foreground">
                                    <Lock className="size-4" />
                                    Enrol to unlock the exam.
                                </p>
                            ) : enrolment.theory_passed ? (
                                <p className="mt-5 flex items-center gap-2 text-sm font-medium text-brand-green">
                                    <FileCheck2 className="size-4" />
                                    Theory passed
                                </p>
                            ) : (
                                <Button
                                    className="mt-5 w-full"
                                    disabled={!enrolment.can_sit_exam}
                                    onClick={() =>
                                        router.post(
                                            academy.exam.start(course.slug).url,
                                        )
                                    }
                                >
                                    Start the exam
                                </Button>
                            )}

                            {enrolment !== null &&
                                !enrolment.theory_passed &&
                                !enrolment.can_sit_exam && (
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Complete every lesson first.{' '}
                                        {enrolment.attempts_remaining} attempts
                                        remain.
                                    </p>
                                )}
                        </section>

                        {enrolment !== null && (
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">
                                    Poolside practicals
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Signed off by the referee at a gala on the
                                    official calendar.
                                </p>
                                <p className="mt-4 text-3xl font-bold tabular-nums">
                                    {enrolment.practical_done}
                                    <span className="text-base font-normal text-muted-foreground">
                                        {' / '}
                                        {course.practical_sessions_required}
                                    </span>
                                </p>
                                {enrolment.ready_for_certificate && (
                                    <p className="mt-3 text-sm font-medium text-brand-green">
                                        Ready for certification.
                                    </p>
                                )}
                            </section>
                        )}

                        {enrolment !== null &&
                            enrolment.attempts.length > 0 && (
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">
                                        Past attempts
                                    </h2>
                                    <ul className="mt-4 space-y-2 text-sm">
                                        {enrolment.attempts.map((attempt) => (
                                            <li
                                                key={attempt.number}
                                                className="flex items-center justify-between gap-3"
                                            >
                                                <span>
                                                    Attempt {attempt.number}
                                                </span>
                                                <Badge
                                                    variant={
                                                        attempt.passed
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                >
                                                    {attempt.score}%
                                                </Badge>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )}
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}
