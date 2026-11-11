import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
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
    description: string | null;
    category: string | null;
    category_label: string | null;
    grade: string | null;
    lessons: { title: string; minutes: number }[];
    study_minutes: number;
    exam_minutes: number;
    pass_mark: number;
    max_attempts: number;
    practical_sessions_required: number;
    minimum_age: number | null;
    prerequisites: string[];
    is_entry: boolean;
    exam_ready: boolean;
};

/**
 * Hours and minutes, written the way a prospectus writes contact time.
 */
function readableTime(minutes: number): string {
    if (minutes < 60) {
        return `${minutes} min`;
    }

    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    return rest === 0 ? `${hours} hr` : `${hours} hr ${rest} min`;
}

/**
 * One course, set out as a syllabus entry.
 *
 * The modules are named rather than counted, because the question somebody
 * has before giving up a season of Saturdays is what they will actually be
 * taught, and "4 lessons" does not answer it.
 */
function Syllabus({ course }: { course: Course }) {
    const assessment = [
        {
            term: 'Taught',
            detail: `${course.lessons.length} ${course.lessons.length === 1 ? 'module' : 'modules'}, about ${readableTime(course.study_minutes)} of study in your own time`,
        },
        {
            term: 'Examined',
            detail: course.exam_ready
                ? `Written paper, ${course.exam_minutes} minutes, ${course.pass_mark}% to pass, ${course.max_attempts} attempts`
                : 'Written paper — the question bank for this one is still being set',
        },
        {
            term: 'Practised',
            detail: `${course.practical_sessions_required} sessions poolside beside a qualified official, each signed off`,
        },
        {
            term: 'Awarded',
            detail: course.grade
                ? `${course.category_label} at ${course.grade} grade, recognised in every province`
                : `${course.category_label}, recognised in every province`,
        },
    ];

    return (
        <article className="overflow-hidden rounded-2xl border border-border bg-card">
            <header className="border-b border-border bg-brand-surface/40 px-6 py-5 sm:px-8">
                <div className="flex flex-wrap items-start justify-between gap-x-6 gap-y-2">
                    <div className="min-w-0">
                        <h3 className="text-xl font-bold tracking-tight text-balance">
                            {course.title}
                        </h3>
                        {course.summary && (
                            <p className="mt-1.5 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                                {course.summary}
                            </p>
                        )}
                    </div>

                    <p className="shrink-0 text-sm text-muted-foreground">
                        {course.is_entry ? (
                            <span className="font-medium text-brand-green">
                                Open to beginners
                            </span>
                        ) : (
                            <>Requires {course.prerequisites.join(' and ')}</>
                        )}
                        {course.minimum_age !== null && (
                            <span className="mt-0.5 block">
                                From age {course.minimum_age}
                            </span>
                        )}
                    </p>
                </div>
            </header>

            <div className="grid gap-8 px-6 py-6 sm:px-8 lg:grid-cols-[1.1fr_1fr]">
                <div>
                    <h4 className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                        Modules
                    </h4>

                    {course.lessons.length === 0 ? (
                        <p className="mt-3 text-sm text-muted-foreground">
                            The syllabus for this course is being written. Its
                            poolside sessions can be worked in the meantime.
                        </p>
                    ) : (
                        <ol className="mt-3 divide-y divide-border/60">
                            {course.lessons.map((lesson, index) => (
                                <li
                                    key={lesson.title}
                                    className="flex items-baseline gap-3 py-2.5 text-sm"
                                >
                                    <span className="w-5 shrink-0 text-right font-mono text-xs text-muted-foreground tabular-nums">
                                        {index + 1}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        {lesson.title}
                                    </span>
                                    {lesson.minutes > 0 && (
                                        <span className="shrink-0 text-xs text-muted-foreground tabular-nums">
                                            {lesson.minutes} min
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ol>
                    )}
                </div>

                <div>
                    <h4 className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                        How it works
                    </h4>

                    <dl className="mt-3 divide-y divide-border/60">
                        {assessment.map((row) => (
                            <div key={row.term} className="py-2.5">
                                <dt className="text-sm font-medium">
                                    {row.term}
                                </dt>
                                <dd className="mt-0.5 text-sm leading-relaxed text-muted-foreground">
                                    {row.detail}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </div>

            <footer className="flex flex-wrap items-center gap-x-5 gap-y-3 border-t border-border bg-muted/30 px-6 py-4 sm:px-8">
                {course.is_entry && course.category ? (
                    <>
                        <Button asChild>
                            <Link
                                href={`${officialsRoutes.apply().url}?category=${course.category}`}
                            >
                                Enrol on this course
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                        <p className="text-xs text-muted-foreground">
                            Your place opens the day the technical committee
                            approves your application.
                        </p>
                    </>
                ) : (
                    <>
                        <Button asChild variant="outline">
                            <Link href={officialsRoutes.apply()}>
                                Start the pathway
                            </Link>
                        </Button>
                        <p className="text-xs text-muted-foreground">
                            Earned from{' '}
                            {course.prerequisites.join(' and ') ||
                                'an earlier category'}
                            , so begin there and this one follows.
                        </p>
                    </>
                )}
            </footer>
        </article>
    );
}

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
                            The prospectus
                        </h2>
                        <p className="mt-2 max-w-3xl text-muted-foreground">
                            Every course is taught, examined and practised in
                            the same way, and the requirements are the same in
                            all provinces. Study in your own time, sit the paper
                            when you are ready, then work your sessions at galas
                            your club is already attending.
                        </p>

                        <div className="mt-8 flex flex-col gap-6">
                            {courses.map((course) => (
                                <Syllabus key={course.title} course={course} />
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
