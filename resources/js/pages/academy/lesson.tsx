import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, PlayCircle } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import academy from '@/routes/academy';

type Question = {
    id: number;
    prompt: string;
    type: string;
    explanation: string | null;
    options: { id: number; label: string; is_correct: boolean }[];
};

export default function AcademyLesson({
    course,
    lesson,
    next,
    quiz,
    enrolled,
}: {
    course: { slug: string; title: string };
    lesson: {
        slug: string;
        title: string;
        summary: string | null;
        content: string | null;
        video_url: string | null;
        runtime: string | null;
        position: number;
        total: number;
        completed: boolean;
    };
    next: { slug: string; title: string } | null;
    quiz: Question[];
    enrolled: boolean;
}) {
    useFlashMessages();
    const [answers, setAnswers] = useState<Record<number, number>>({});
    const [checked, setChecked] = useState(false);

    const correctCount = quiz.filter((question) =>
        question.options.some(
            (option) => option.is_correct && answers[question.id] === option.id,
        ),
    ).length;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Officials Academy', href: academy.index().url },
                { title: course.title, href: academy.show(course.slug).url },
                { title: lesson.title, href: '#' },
            ]}
        >
            <Head title={lesson.title} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={academy.show(course.slug)}>
                        <ArrowLeft className="size-4" />
                        {course.title}
                    </Link>
                </Button>

                <div>
                    <p className="text-xs font-semibold tracking-[0.14em] text-primary uppercase">
                        Lesson {lesson.position} of {lesson.total}
                    </p>
                    <h1 className="mt-2 text-3xl font-bold tracking-tight text-balance">
                        {lesson.title}
                    </h1>
                    {lesson.summary && (
                        <p className="mt-2 text-muted-foreground">
                            {lesson.summary}
                        </p>
                    )}
                </div>

                <div className="flex aspect-video items-center justify-center rounded-xl border border-border bg-brand-deep text-white/70">
                    <div className="text-center">
                        <PlayCircle className="mx-auto size-12" />
                        <p className="mt-3 text-sm">
                            {lesson.video_url
                                ? 'Video lesson'
                                : 'Video lesson to be uploaded'}
                        </p>
                        {lesson.runtime && (
                            <p className="text-xs text-white/50">
                                {lesson.runtime}
                            </p>
                        )}
                    </div>
                </div>

                {lesson.content && (
                    <article className="space-y-4 leading-relaxed">
                        {lesson.content.split('\n\n').map((paragraph) => (
                            <p key={paragraph.slice(0, 32)}>{paragraph}</p>
                        ))}
                    </article>
                )}

                {quiz.length > 0 && (
                    <section className="rounded-xl border border-border bg-card p-6">
                        <h2 className="font-semibold">Check your knowledge</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Not marked, and it does not affect your exam.
                        </p>

                        <ol className="mt-6 space-y-6">
                            {quiz.map((question, index) => (
                                <li key={question.id}>
                                    <p className="font-medium">
                                        {index + 1}. {question.prompt}
                                    </p>
                                    <div className="mt-3 space-y-2">
                                        {question.options.map((option) => {
                                            const selected =
                                                answers[question.id] ===
                                                option.id;
                                            const reveal =
                                                checked && option.is_correct;
                                            const wrong =
                                                checked &&
                                                selected &&
                                                !option.is_correct;

                                            return (
                                                <label
                                                    key={option.id}
                                                    className={`flex cursor-pointer items-center gap-3 rounded-md border px-3 py-2 text-sm transition-colors ${
                                                        reveal
                                                            ? 'border-brand-green/50 bg-brand-green/10'
                                                            : wrong
                                                              ? 'border-brand-red/50 bg-brand-red/10'
                                                              : selected
                                                                ? 'border-primary'
                                                                : 'border-border'
                                                    }`}
                                                >
                                                    <input
                                                        type="radio"
                                                        name={`q-${question.id}`}
                                                        checked={selected}
                                                        onChange={() =>
                                                            setAnswers(
                                                                (current) => ({
                                                                    ...current,
                                                                    [question.id]:
                                                                        option.id,
                                                                }),
                                                            )
                                                        }
                                                        className="accent-primary"
                                                    />
                                                    {option.label}
                                                </label>
                                            );
                                        })}
                                    </div>
                                    {checked && question.explanation && (
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {question.explanation}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ol>

                        <div className="mt-6 flex items-center gap-3">
                            <Button
                                variant="outline"
                                onClick={() => setChecked(true)}
                            >
                                Check answers
                            </Button>
                            {checked && (
                                <Badge variant="secondary">
                                    {correctCount} of {quiz.length} correct
                                </Badge>
                            )}
                        </div>
                    </section>
                )}

                <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-6">
                    {enrolled && !lesson.completed ? (
                        <Button
                            onClick={() =>
                                router.post(
                                    academy.lesson.complete([
                                        course.slug,
                                        lesson.slug,
                                    ]).url,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <Check className="size-4" />
                            Mark as complete
                        </Button>
                    ) : (
                        <Badge
                            variant={lesson.completed ? 'default' : 'outline'}
                        >
                            {lesson.completed
                                ? 'Completed'
                                : 'Enrol to track progress'}
                        </Badge>
                    )}

                    {next && (
                        <Button asChild variant="outline">
                            <Link
                                href={academy.lesson([course.slug, next.slug])}
                            >
                                Next: {next.title}
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
