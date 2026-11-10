import { Form, Head, Link } from '@inertiajs/react';
import { AlertTriangle, GraduationCap, Plus } from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { SelectField, TextField } from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type CourseRow = {
    id: number;
    slug: string;
    title: string;
    category: string | null;
    grade: string | null;
    is_published: boolean;
    pass_mark: number;
    exam_question_count: number;
    lessons_count: number;
    questions_count: number;
    enrolments_count: number;
    bank_ready: boolean;
};

export default function AcademyAdminIndex({
    courses,
    categories,
    grades,
}: {
    courses: CourseRow[];
    categories: (Option & { minimum_age: number })[];
    grades: Option[];
}) {
    useFlashMessages();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Academy', href: admin.academy.index().url },
            ]}
        >
            <Head title="Officials Academy" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Officials Academy"
                    description="Courses, lessons and the exam question bank. A course cannot run an exam until its bank holds more questions than the exam asks."
                />

                <div className="grid gap-6 xl:grid-cols-3">
                    <div className="space-y-3 xl:col-span-2">
                        {courses.length === 0 ? (
                            <EmptyState
                                icon={GraduationCap}
                                title="No courses yet"
                                description="Start with Timekeeper, the entry point for every official."
                            />
                        ) : (
                            courses.map((course) => (
                                <div
                                    key={course.id}
                                    className="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-border bg-card p-5"
                                >
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className="font-semibold">
                                                {course.title}
                                            </h2>
                                            <Badge
                                                variant={
                                                    course.is_published
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {course.is_published
                                                    ? 'Published'
                                                    : 'Draft'}
                                            </Badge>
                                            {!course.bank_ready && (
                                                <Badge
                                                    variant="outline"
                                                    className="gap-1 border-brand-gold/60"
                                                >
                                                    <AlertTriangle className="size-3" />
                                                    Bank too small
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {course.category ?? 'No category'}
                                            {course.grade
                                                ? ` · awards ${course.grade}`
                                                : ''}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {course.lessons_count} lessons ·{' '}
                                            {course.questions_count} questions ·{' '}
                                            {course.enrolments_count} enrolled ·
                                            exam draws{' '}
                                            {course.exam_question_count} at{' '}
                                            {course.pass_mark}%
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                href={admin.academy.enrolments(
                                                    course.slug,
                                                )}
                                            >
                                                Enrolments
                                            </Link>
                                        </Button>
                                        <Button asChild size="sm">
                                            <Link
                                                href={admin.academy.edit(
                                                    course.slug,
                                                )}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    <section className="h-fit rounded-xl border border-border bg-card p-6">
                        <h2 className="font-semibold">New course</h2>
                        <Form
                            {...admin.academy.store.form()}
                            className="mt-5 space-y-4"
                            resetOnSuccess
                        >
                            {({ errors, processing }) => (
                                <>
                                    <TextField
                                        label="Title"
                                        name="title"
                                        error={errors.title}
                                        required
                                    />
                                    <SelectField
                                        label="Officiating category"
                                        name="official_category"
                                        options={categories}
                                        placeholder="Not linked"
                                        error={errors.official_category}
                                    />
                                    <SelectField
                                        label="Awards grade"
                                        name="awards_grade"
                                        options={grades}
                                        defaultValue="provincial"
                                        error={errors.awards_grade}
                                    />
                                    <TextField
                                        label="Pass mark (%)"
                                        name="pass_mark"
                                        type="number"
                                        defaultValue={80}
                                        error={errors.pass_mark}
                                        required
                                    />
                                    <TextField
                                        label="Questions per exam"
                                        name="exam_question_count"
                                        type="number"
                                        defaultValue={20}
                                        error={errors.exam_question_count}
                                        required
                                    />
                                    <TextField
                                        label="Exam minutes"
                                        name="exam_minutes"
                                        type="number"
                                        defaultValue={30}
                                        error={errors.exam_minutes}
                                        required
                                    />
                                    <TextField
                                        label="Attempts allowed"
                                        name="max_attempts"
                                        type="number"
                                        defaultValue={3}
                                        error={errors.max_attempts}
                                        required
                                    />
                                    <TextField
                                        label="Practical sessions"
                                        name="practical_sessions_required"
                                        type="number"
                                        defaultValue={6}
                                        error={
                                            errors.practical_sessions_required
                                        }
                                        required
                                    />
                                    <TextField
                                        label="Fee (USD)"
                                        name="fee"
                                        type="number"
                                        step="0.01"
                                        defaultValue={0}
                                        error={errors.fee}
                                        required
                                    />
                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={processing}
                                    >
                                        <Plus className="size-4" />
                                        Create course
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
