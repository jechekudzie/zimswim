import { Form, Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Plus, Trash2 } from 'lucide-react';

import {
    CheckboxField,
    SelectField,
    TextareaField,
    TextField,
} from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

export default function CourseForm({
    course,
    lessons,
    questions,
    categories,
    grades,
    questionTypes,
}: {
    course: {
        slug: string;
        title: string;
        official_category: string | null;
        awards_grade: string | null;
        summary: string | null;
        description: string | null;
        pass_mark: number;
        max_attempts: number;
        exam_question_count: number;
        exam_minutes: number;
        practical_sessions_required: number;
        minimum_age: number | null;
        fee: string;
        is_published: boolean;
        bank_ready: boolean;
        bank_size: number;
    };
    lessons: {
        id: number;
        slug: string;
        title: string;
        position: number;
        summary: string | null;
        video_url: string | null;
        is_published: boolean;
    }[];
    questions: {
        id: number;
        prompt: string;
        type: string;
        lesson: string | null;
        rule_reference: string | null;
        in_exam_bank: boolean;
        options: { id: number; label: string; is_correct: boolean }[];
    }[];
    categories: (Option & { minimum_age: number })[];
    grades: Option[];
    questionTypes: Option[];
}) {
    useFlashMessages();
    const options = [
        { is_correct: true },
        { is_correct: false },
        { is_correct: false },
        { is_correct: false },
    ];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Academy', href: admin.academy.index().url },
                { title: course.title, href: '#' },
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
                    <Link href={admin.academy.index()}>
                        <ArrowLeft className="size-4" />
                        All courses
                    </Link>
                </Button>

                <PageHeader
                    title={course.title}
                    description={`${lessons.length} lessons · ${course.bank_size} questions in the exam bank`}
                    actions={
                        !course.bank_ready && (
                            <Badge
                                variant="outline"
                                className="gap-1 border-brand-gold/60"
                            >
                                <AlertTriangle className="size-3" />
                                Bank must hold at least{' '}
                                {course.exam_question_count}
                            </Badge>
                        )
                    }
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">Lessons</h2>
                            <ol className="mt-4 space-y-2">
                                {lessons.map((lesson) => (
                                    <li
                                        key={lesson.id}
                                        className="flex items-center justify-between gap-3 rounded-lg border border-border px-4 py-3"
                                    >
                                        <div>
                                            <p className="text-sm font-medium">
                                                {lesson.position + 1}.{' '}
                                                {lesson.title}
                                            </p>
                                            {lesson.summary && (
                                                <p className="text-xs text-muted-foreground">
                                                    {lesson.summary}
                                                </p>
                                            )}
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                router.delete(
                                                    admin.academy.lessons.destroy(
                                                        [
                                                            course.slug,
                                                            lesson.slug,
                                                        ],
                                                    ).url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Trash2 className="size-4 text-brand-red" />
                                        </Button>
                                    </li>
                                ))}
                            </ol>

                            <Form
                                {...admin.academy.lessons.store.form(
                                    course.slug,
                                )}
                                className="mt-5 space-y-4 border-t border-border pt-5"
                                resetOnSuccess
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <TextField
                                            label="Lesson title"
                                            name="title"
                                            error={errors.title}
                                            required
                                        />
                                        <TextField
                                            label="Summary"
                                            name="summary"
                                            error={errors.summary}
                                        />
                                        <TextareaField
                                            label="Lesson text"
                                            name="content"
                                            rows={5}
                                            error={errors.content}
                                        />
                                        <TextField
                                            label="Video URL"
                                            name="video_url"
                                            type="url"
                                            error={errors.video_url}
                                        />
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <Plus className="size-4" />
                                            Add lesson
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>

                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">
                                Exam question bank
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Each attempt draws {course.exam_question_count}{' '}
                                at random, so the bank should be comfortably
                                larger.
                            </p>

                            <ul className="mt-4 space-y-2">
                                {questions.map((question) => (
                                    <li
                                        key={question.id}
                                        className="flex items-start justify-between gap-3 rounded-lg border border-border px-4 py-3"
                                    >
                                        <div>
                                            <p className="text-sm">
                                                {question.prompt}
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {question.options.length}{' '}
                                                options
                                                {question.rule_reference
                                                    ? ` · rule ${question.rule_reference}`
                                                    : ''}
                                                {question.in_exam_bank
                                                    ? ''
                                                    : ' · quiz only'}
                                            </p>
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                router.delete(
                                                    admin.academy.questions.destroy(
                                                        [
                                                            course.slug,
                                                            question.id,
                                                        ],
                                                    ).url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Trash2 className="size-4 text-brand-red" />
                                        </Button>
                                    </li>
                                ))}
                            </ul>

                            <Form
                                {...admin.academy.questions.store.form(
                                    course.slug,
                                )}
                                className="mt-5 space-y-4 border-t border-border pt-5"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <TextareaField
                                            label="Question"
                                            name="prompt"
                                            rows={2}
                                            error={errors.prompt}
                                        />
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <SelectField
                                                label="Type"
                                                name="type"
                                                options={questionTypes}
                                                defaultValue="single"
                                                error={errors.type}
                                            />
                                            <TextField
                                                label="Rule reference"
                                                name="rule_reference"
                                                placeholder="e.g. 7.4"
                                                error={errors.rule_reference}
                                            />
                                        </div>
                                        <TextareaField
                                            label="Explanation shown on review"
                                            name="explanation"
                                            rows={2}
                                            error={errors.explanation}
                                        />

                                        <div className="grid gap-2">
                                            <Label>
                                                Options (tick the correct ones)
                                            </Label>
                                            {options.map((option, index) => (
                                                <div
                                                    key={index}
                                                    className="flex items-center gap-3"
                                                >
                                                    <Checkbox
                                                        name={`options[${index}][is_correct]`}
                                                        value="1"
                                                        defaultChecked={
                                                            option.is_correct
                                                        }
                                                    />
                                                    <Input
                                                        name={`options[${index}][label]`}
                                                        placeholder={`Option ${index + 1}`}
                                                    />
                                                </div>
                                            ))}
                                            <InputError
                                                message={errors.options}
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <Plus className="size-4" />
                                            Add question
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>
                    </div>

                    <aside>
                        <Form
                            {...admin.academy.update.form(course.slug)}
                            className="space-y-5 rounded-xl border border-border bg-card p-6"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <h2 className="font-semibold">Settings</h2>
                                    <TextField
                                        label="Title"
                                        name="title"
                                        defaultValue={course.title}
                                        error={errors.title}
                                        required
                                    />
                                    <SelectField
                                        label="Officiating category"
                                        name="official_category"
                                        options={categories}
                                        defaultValue={course.official_category}
                                        placeholder="Not linked"
                                        error={errors.official_category}
                                    />
                                    <SelectField
                                        label="Awards grade"
                                        name="awards_grade"
                                        options={grades}
                                        defaultValue={course.awards_grade}
                                        placeholder="None"
                                        error={errors.awards_grade}
                                    />
                                    <TextareaField
                                        label="Summary"
                                        name="summary"
                                        rows={3}
                                        defaultValue={course.summary}
                                        error={errors.summary}
                                    />
                                    <TextareaField
                                        label="Description"
                                        name="description"
                                        rows={5}
                                        defaultValue={course.description}
                                        error={errors.description}
                                    />
                                    <div className="grid grid-cols-2 gap-4">
                                        <TextField
                                            label="Pass mark"
                                            name="pass_mark"
                                            type="number"
                                            defaultValue={course.pass_mark}
                                            error={errors.pass_mark}
                                            required
                                        />
                                        <TextField
                                            label="Attempts"
                                            name="max_attempts"
                                            type="number"
                                            defaultValue={course.max_attempts}
                                            error={errors.max_attempts}
                                            required
                                        />
                                        <TextField
                                            label="Questions"
                                            name="exam_question_count"
                                            type="number"
                                            defaultValue={
                                                course.exam_question_count
                                            }
                                            error={errors.exam_question_count}
                                            required
                                        />
                                        <TextField
                                            label="Minutes"
                                            name="exam_minutes"
                                            type="number"
                                            defaultValue={course.exam_minutes}
                                            error={errors.exam_minutes}
                                            required
                                        />
                                        <TextField
                                            label="Practicals"
                                            name="practical_sessions_required"
                                            type="number"
                                            defaultValue={
                                                course.practical_sessions_required
                                            }
                                            error={
                                                errors.practical_sessions_required
                                            }
                                            required
                                        />
                                        <TextField
                                            label="Minimum age"
                                            name="minimum_age"
                                            type="number"
                                            defaultValue={course.minimum_age}
                                            error={errors.minimum_age}
                                        />
                                    </div>
                                    <TextField
                                        label="Fee (USD)"
                                        name="fee"
                                        type="number"
                                        step="0.01"
                                        defaultValue={course.fee}
                                        error={errors.fee}
                                        required
                                    />
                                    <CheckboxField
                                        label="Published to the Academy"
                                        name="is_published"
                                        defaultChecked={course.is_published}
                                    />
                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={processing}
                                    >
                                        Save course
                                    </Button>
                                </>
                            )}
                        </Form>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}
