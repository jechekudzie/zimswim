import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    BookOpen,
    ClipboardList,
    GraduationCap,
    Timer,
} from 'lucide-react';

import { FlagRule } from '@/components/flag-rule';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Section } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import officialsRoutes from '@/routes/officials';
import type { Option } from '@/types';

type Course = {
    title: string;
    summary: string | null;
    category_label: string | null;
    grade: string | null;
    lessons_count: number;
    exam_minutes: number;
    pass_mark: number;
    practical_sessions_required: number;
    exam_ready: boolean;
};

type Category = {
    value: string;
    label: string;
    minimum_age: number;
    learner_sessions: number;
    has_exam: boolean;
    prerequisites: string[];
};

const steps = [
    {
        icon: Timer,
        title: 'Start here: timekeeper',
        body: 'Get involved at galas as a timekeeper. It is a great way to learn, be part of the action and understand how competitions work.',
    },
    {
        icon: GraduationCap,
        title: 'Attend a clinic',
        body: 'Join an officiating clinic to learn the basics, meet experienced officials and find out about the different roles.',
    },
    {
        icon: ClipboardList,
        title: 'Complete learner sessions',
        body: 'Work the required pool-deck sessions as a learner, with practical experience and guidance from qualified officials.',
    },
    {
        icon: Award,
        title: 'Write the exam and qualify',
        body: 'Sit the officiating exam to put your knowledge into practice, then take up your role in safe, fair and successful competitions.',
    },
];

export default function Officials({
    categories,
    grades,
    registeredCount,
    courses,
}: {
    categories: Category[];
    grades: Option[];
    registeredCount: number;
    courses: Course[];
}) {
    return (
        <PublicLayout
            heroImage={photos.pages.officials}
            figures={[
                { label: 'Official categories', value: categories.length },
                { label: 'Grades to work through', value: grades.length },
            ]}
            title="New to officiating?"
            description="You do not need to know everything on day one. Every qualified official started as a learner, and there is a place for you on the pool deck."
            heroActions={
                <Button asChild size="lg">
                    <Link href={officialsRoutes.apply()}>
                        Apply to officiate
                        <ArrowRight className="size-4" />
                    </Link>
                </Button>
            }
        >
            <Head title="Officials Academy" />

            <Section>
                <div>
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {steps.map((step, index) => (
                            <div
                                key={step.title}
                                className="relative rounded-xl border border-border bg-card p-6"
                            >
                                <span className="absolute top-6 right-6 text-4xl font-bold text-muted/60 tabular-nums">
                                    {index + 1}
                                </span>
                                <step.icon className="size-6 text-primary" />
                                <h2 className="mt-4 font-semibold text-balance">
                                    {step.title}
                                </h2>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {step.body}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </Section>

            {courses.length > 0 && (
                <Section tone="surface">
                    <div>
                        <FlagRule />
                        <h2 className="mt-4 text-2xl font-bold tracking-tight">
                            Courses in the Academy
                        </h2>
                        <p className="mt-2 max-w-3xl text-muted-foreground">
                            Each course is lessons you work through in your own
                            time, a written exam, and sessions signed off at the
                            poolside. Your place on one is opened for you the
                            day your application is approved.
                        </p>

                        <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            {courses.map((course) => (
                                <article
                                    key={course.title}
                                    className="flex flex-col rounded-xl border border-border bg-card p-6"
                                >
                                    <BookOpen className="size-5 text-primary" />
                                    <h3 className="mt-4 font-semibold text-balance">
                                        {course.title}
                                    </h3>
                                    {course.summary && (
                                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                            {course.summary}
                                        </p>
                                    )}

                                    <dl className="mt-5 space-y-1.5 text-xs text-muted-foreground">
                                        <div className="flex justify-between gap-3">
                                            <dt>Lessons</dt>
                                            <dd className="font-medium text-foreground tabular-nums">
                                                {course.lessons_count}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between gap-3">
                                            <dt>Exam</dt>
                                            <dd className="font-medium text-foreground tabular-nums">
                                                {course.exam_minutes} min ·{' '}
                                                {course.pass_mark}% to pass
                                            </dd>
                                        </div>
                                        <div className="flex justify-between gap-3">
                                            <dt>Poolside sessions</dt>
                                            <dd className="font-medium text-foreground tabular-nums">
                                                {
                                                    course.practical_sessions_required
                                                }
                                            </dd>
                                        </div>
                                    </dl>

                                    {!course.exam_ready && (
                                        <p className="mt-4 text-xs text-muted-foreground">
                                            The exam for this one is still being
                                            set. Lessons and poolside sessions
                                            can start now.
                                        </p>
                                    )}
                                </article>
                            ))}
                        </div>
                    </div>
                </Section>
            )}

            <Section>
                <div>
                    <FlagRule />
                    <h2 className="mt-4 text-2xl font-bold tracking-tight">
                        Officiating categories
                    </h2>
                    <p className="mt-2 max-w-3xl text-muted-foreground">
                        The requirements for achieving accreditation are the
                        same in all provinces and achievement is recognised
                        nationally. Only one new officiating category or
                        examination may normally be undertaken per season.
                    </p>

                    <div className="mt-8 overflow-x-auto rounded-xl border border-border">
                        <table className="w-full min-w-[720px] text-sm">
                            <thead className="border-b border-border bg-brand-surface/60 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-5 py-3 font-medium">
                                        Category
                                    </th>
                                    <th className="px-5 py-3 font-medium">
                                        Minimum age
                                    </th>
                                    <th className="px-5 py-3 font-medium">
                                        Learner sessions
                                    </th>
                                    <th className="px-5 py-3 font-medium">
                                        Must already hold
                                    </th>
                                    <th className="px-5 py-3 font-medium">
                                        Exam
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {categories.map((category) => (
                                    <tr
                                        key={category.value}
                                        className="border-b border-border/60 bg-card last:border-0"
                                    >
                                        <td className="px-5 py-3 font-medium">
                                            {category.label}
                                        </td>
                                        <td className="px-5 py-3 tabular-nums">
                                            {category.minimum_age} years
                                        </td>
                                        <td className="px-5 py-3 tabular-nums">
                                            {category.learner_sessions}
                                        </td>
                                        <td className="px-5 py-3 text-muted-foreground">
                                            {category.prerequisites.length === 0
                                                ? 'Nothing — start here'
                                                : category.prerequisites.join(
                                                      ', ',
                                                  )}
                                        </td>
                                        <td className="px-5 py-3">
                                            {category.has_exam ? (
                                                <Badge variant="secondary">
                                                    Required
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    None
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </Section>

            <Section>
                <div className="grid gap-8 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <FlagRule />
                        <h2 className="mt-4 text-2xl font-bold tracking-tight">
                            Grades and keeping them current
                        </h2>
                        <p className="mt-3 text-muted-foreground">
                            Accreditation is awarded at a grade, and the grade
                            decides the level of meet you can work. It lapses if
                            you do not officiate at your highest level for two
                            years.
                        </p>
                        <div className="mt-6 flex flex-wrap gap-3">
                            {grades.map((grade) => (
                                <Badge
                                    key={grade.value}
                                    variant="outline"
                                    className="px-3 py-1 text-sm"
                                >
                                    {grade.label}
                                </Badge>
                            ))}
                        </div>
                        <p className="mt-6 text-sm text-muted-foreground">
                            To be recognised as having worked at a meet you must
                            have worked at least 50% of its sessions and signed
                            the official register for that session.
                        </p>
                    </div>

                    <div className="rounded-xl bg-brand-deep p-8 text-white">
                        <p className="text-4xl font-bold tabular-nums">
                            {registeredCount}
                        </p>
                        <p className="mt-1 text-white/70">
                            officials on the national register
                        </p>
                        <p className="mt-6 text-sm leading-relaxed text-white/70">
                            All technical officials must be registered members
                            of a registered swimming club and the province in
                            their place of domicile. Speak to your club
                            secretary to get started.
                        </p>
                    </div>
                </div>
            </Section>
        </PublicLayout>
    );
}
