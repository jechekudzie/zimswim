import { Head, Link } from '@inertiajs/react';
import { Check, X } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import academy from '@/routes/academy';

export default function ExamResult({
    course,
    attempt,
    review,
}: {
    course: { slug: string; title: string };
    attempt: {
        number: number;
        score: number | null;
        passed: boolean;
        correct: number;
        total: number;
        pass_mark: number;
        status: string;
    };
    review: {
        prompt: string;
        is_correct: boolean;
        explanation: string | null;
        rule_reference: string | null;
    }[];
}) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Officials Academy', href: academy.index().url },
                { title: course.title, href: academy.show(course.slug).url },
                { title: 'Exam result', href: '#' },
            ]}
        >
            <Head title={`${course.title} exam result`} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <section
                    className={`rounded-2xl border p-8 text-center ${
                        attempt.passed
                            ? 'border-brand-green/40 bg-brand-green/10'
                            : 'border-border bg-card'
                    }`}
                >
                    <p className="text-xs font-semibold tracking-[0.16em] text-muted-foreground uppercase">
                        Attempt {attempt.number}
                        {attempt.status === 'expired' ? ' · time expired' : ''}
                    </p>
                    <p className="mt-4 text-6xl font-bold tabular-nums">
                        {attempt.score}%
                    </p>
                    <p className="mt-2 text-muted-foreground">
                        {attempt.correct} of {attempt.total} correct · pass mark{' '}
                        {attempt.pass_mark}%
                    </p>
                    <Badge
                        className="mt-5"
                        variant={attempt.passed ? 'default' : 'outline'}
                    >
                        {attempt.passed ? 'Passed' : 'Not passed'}
                    </Badge>

                    {attempt.passed && (
                        <p className="mx-auto mt-5 max-w-md text-sm text-muted-foreground">
                            Theory done. Next, work your poolside practical
                            sessions at a gala and have them signed off by the
                            referee.
                        </p>
                    )}
                </section>

                <section className="rounded-xl border border-border bg-card p-6">
                    <h2 className="font-semibold">Review</h2>
                    <ul className="mt-4 space-y-4">
                        {review.map((item, index) => (
                            <li
                                key={index}
                                className="flex items-start gap-3 border-b border-border/60 pb-4 text-sm last:border-0 last:pb-0"
                            >
                                {item.is_correct ? (
                                    <Check className="mt-0.5 size-4 shrink-0 text-brand-green" />
                                ) : (
                                    <X className="mt-0.5 size-4 shrink-0 text-brand-red" />
                                )}
                                <div>
                                    <p>{item.prompt}</p>
                                    {!item.is_correct && item.explanation && (
                                        <p className="mt-1 text-muted-foreground">
                                            {item.explanation}
                                        </p>
                                    )}
                                    {item.rule_reference && (
                                        <p className="mt-1 font-mono text-xs text-muted-foreground">
                                            Rule {item.rule_reference}
                                        </p>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>

                <Button asChild variant="outline">
                    <Link href={academy.show(course.slug)}>
                        Back to the course
                    </Link>
                </Button>
            </div>
        </AppLayout>
    );
}
