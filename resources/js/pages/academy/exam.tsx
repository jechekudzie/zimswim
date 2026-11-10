import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle, Timer } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import academy from '@/routes/academy';

type Question = {
    id: number;
    position: number;
    prompt: string;
    type: string;
    options: { id: number; label: string }[];
};

export default function Exam({
    course,
    attempt,
    questions,
}: {
    course: { slug: string; title: string; pass_mark: number };
    attempt: {
        id: number;
        number: number;
        seconds_remaining: number;
        total: number;
    };
    questions: Question[];
}) {
    const [remaining, setRemaining] = useState(attempt.seconds_remaining);
    const { data, setData, post, processing } = useForm<{
        answers: Record<number, number[]>;
    }>({ answers: {} });

    useEffect(() => {
        const timer = setInterval(
            () => setRemaining((value) => Math.max(0, value - 1)),
            1000,
        );

        return () => clearInterval(timer);
    }, []);

    const submit = () =>
        post(academy.exam.submit([course.slug, attempt.id]).url);

    useEffect(() => {
        if (remaining === 0 && attempt.seconds_remaining > 0) {
            submit();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [remaining]);

    const choose = (questionId: number, optionId: number, multiple: boolean) =>
        setData('answers', {
            ...data.answers,
            [questionId]: multiple
                ? (data.answers[questionId] ?? []).includes(optionId)
                    ? (data.answers[questionId] ?? []).filter(
                          (id) => id !== optionId,
                      )
                    : [...(data.answers[questionId] ?? []), optionId]
                : [optionId],
        });

    const answered = Object.values(data.answers).filter(
        (value) => value.length > 0,
    ).length;

    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Officials Academy', href: academy.index().url },
                { title: course.title, href: academy.show(course.slug).url },
                { title: 'Exam', href: '#' },
            ]}
        >
            <Head title={`${course.title} exam`} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <div className="sticky top-0 z-10 -mx-4 flex flex-wrap items-center justify-between gap-3 border-b border-border bg-background/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6">
                    <div>
                        <h1 className="font-semibold">{course.title} exam</h1>
                        <p className="text-xs text-muted-foreground">
                            Attempt {attempt.number} · {answered} of{' '}
                            {attempt.total} answered · pass mark{' '}
                            {course.pass_mark}%
                        </p>
                    </div>
                    <span
                        className={`flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-semibold tabular-nums ${
                            remaining < 120
                                ? 'bg-brand-red/15 text-brand-red'
                                : 'bg-muted'
                        }`}
                    >
                        <Timer className="size-4" />
                        {minutes}:{String(seconds).padStart(2, '0')}
                    </span>
                </div>

                {remaining < 120 && (
                    <p className="flex items-center gap-2 rounded-lg border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm">
                        <AlertTriangle className="size-4 shrink-0" />
                        Less than two minutes remain. The exam submits itself
                        when the clock runs out.
                    </p>
                )}

                <ol className="space-y-6">
                    {questions.map((question) => {
                        const multiple = question.type === 'multiple';
                        const selected = data.answers[question.id] ?? [];

                        return (
                            <li
                                key={question.id}
                                className="rounded-xl border border-border bg-card p-6"
                            >
                                <p className="font-medium">
                                    {question.position}. {question.prompt}
                                </p>
                                {multiple && (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Select every correct answer.
                                    </p>
                                )}
                                <div className="mt-4 space-y-2">
                                    {question.options.map((option) => (
                                        <label
                                            key={option.id}
                                            className={`flex cursor-pointer items-center gap-3 rounded-md border px-3 py-2.5 text-sm transition-colors ${
                                                selected.includes(option.id)
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-border hover:bg-accent/40'
                                            }`}
                                        >
                                            {multiple ? (
                                                <Checkbox
                                                    checked={selected.includes(
                                                        option.id,
                                                    )}
                                                    onCheckedChange={() =>
                                                        choose(
                                                            question.id,
                                                            option.id,
                                                            true,
                                                        )
                                                    }
                                                />
                                            ) : (
                                                <input
                                                    type="radio"
                                                    name={`q-${question.id}`}
                                                    checked={selected.includes(
                                                        option.id,
                                                    )}
                                                    onChange={() =>
                                                        choose(
                                                            question.id,
                                                            option.id,
                                                            false,
                                                        )
                                                    }
                                                    className="accent-primary"
                                                />
                                            )}
                                            {option.label}
                                        </label>
                                    ))}
                                </div>
                            </li>
                        );
                    })}
                </ol>

                <Button
                    size="lg"
                    onClick={submit}
                    disabled={processing}
                    className="w-full"
                >
                    Submit exam
                </Button>
            </div>
        </AppLayout>
    );
}
