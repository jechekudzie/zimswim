import { Head, Link } from '@inertiajs/react';
import {
    Award,
    BookOpen,
    CheckCircle2,
    ClipboardCheck,
    Download,
    FileText,
    GraduationCap,
    XCircle,
} from 'lucide-react';

import { EmptyState } from '@/components/admin/empty-state';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import academy from '@/routes/academy';
import certificates from '@/routes/certificates';

type Attempt = {
    number: number;
    score: number | null;
    correct: number;
    total: number;
    passed: boolean;
    status: string;
    sat_on: string;
};

type Journey = {
    course_slug: string;
    course_title: string;
    category: string | null;
    status: string;
    status_label: string;
    lessons_total: number;
    lessons_done: number;
    progress: number;
    pass_mark: number;
    attempts_used: number;
    attempts_allowed: number;
    attempts_remaining: number;
    can_sit_exam: boolean;
    exam_ready: boolean;
    theory_passed: boolean;
    practicals_required: number;
    practicals_done: number;
    practicals_outstanding: number;
    ready_for_certificate: boolean;
    attempts: Attempt[];
};

type Certificate = {
    code: string;
    title: string;
    grade: string | null;
    issued_on: string;
    expires_on: string | null;
    valid: boolean;
    expired: boolean;
};

/**
 * The one line that says what to do next.
 *
 * A progress bar tells somebody how far they have come; it does not tell
 * them what is in their way. This does, and it is the only sentence on the
 * card written as an instruction.
 */
function nextStep(stage: Journey): string {
    if (stage.ready_for_certificate) {
        return 'Everything is done. Your certificate is below.';
    }

    if (stage.lessons_done < stage.lessons_total) {
        const left = stage.lessons_total - stage.lessons_done;

        return `${left} ${left === 1 ? 'lesson' : 'lessons'} to work through before the exam opens.`;
    }

    if (!stage.theory_passed) {
        if (!stage.exam_ready) {
            return 'The question bank for this exam is still being set. Your poolside sessions can go ahead.';
        }

        if (stage.attempts_remaining < 1) {
            return 'No attempts remain. Speak to your technical officials convenor.';
        }

        return `Lessons done. Sit the exam — ${stage.pass_mark}% to pass, ${stage.attempts_remaining} ${stage.attempts_remaining === 1 ? 'attempt' : 'attempts'} left.`;
    }

    if (stage.practicals_outstanding > 0) {
        return `Exam passed. ${stage.practicals_outstanding} more poolside ${stage.practicals_outstanding === 1 ? 'session' : 'sessions'} to be signed off.`;
    }

    return 'Waiting on the technical committee to issue your certificate.';
}

function Track({
    label,
    done,
    total,
}: {
    label: string;
    done: number;
    total: number;
}) {
    const percent = total === 0 ? 0 : Math.round((done / total) * 100);

    return (
        <div>
            <div className="flex items-baseline justify-between gap-3 text-sm">
                <span className="text-muted-foreground">{label}</span>
                <span className="font-medium tabular-nums">
                    {done} of {total}
                </span>
            </div>
            <div
                className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted"
                role="img"
                aria-label={`${label}: ${done} of ${total}`}
            >
                <div
                    className="h-full rounded-full bg-primary"
                    style={{ width: `${percent}%` }}
                />
            </div>
        </div>
    );
}

export default function OfficiatingPortal({
    official,
    journey,
    accreditations,
    sessions,
    certificates: held,
}: {
    official: {
        registration_number: string;
        name: string;
        club: string | null;
        province: string | null;
        status: string;
    } | null;
    journey: Journey[];
    accreditations: {
        category: string;
        grade: string;
        status: string;
        status_label: string;
        attained_on: string | null;
        expires_on: string | null;
        lapses_soon: boolean;
    }[];
    sessions: {
        gala: string;
        role: string;
        worked_on: string;
        as_learner: boolean;
        signed_off: boolean;
    }[];
    certificates: Certificate[];
}) {
    useFlashMessages();

    return (
        <AppLayout breadcrumbs={[{ title: 'Officiating', href: '/portal' }]}>
            <Head title="Officiating" />

            <div className="flex flex-col gap-8 p-4 sm:p-6">
                <PageHeader
                    title="Your officiating"
                    description={
                        official
                            ? `${official.registration_number} · ${official.club ?? 'No club recorded'}`
                            : 'You are not on the officials register yet.'
                    }
                />

                {journey.length === 0 ? (
                    <EmptyState
                        icon={GraduationCap}
                        title="No course yet"
                        description="A course is opened for you when your application to officiate is approved."
                    />
                ) : (
                    <section>
                        <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                            Where you are
                        </h2>

                        <div className="mt-4 grid gap-5 xl:grid-cols-2">
                            {journey.map((stage) => (
                                <article
                                    key={stage.course_slug}
                                    className="rounded-xl border border-border bg-card"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3 border-b border-border/60 p-5">
                                        <div className="min-w-0">
                                            <h3 className="font-semibold">
                                                {stage.course_title}
                                            </h3>
                                            {stage.category && (
                                                <p className="mt-0.5 text-sm text-muted-foreground">
                                                    {stage.category}
                                                </p>
                                            )}
                                        </div>
                                        <Badge
                                            variant="outline"
                                            className={cn(
                                                stage.ready_for_certificate &&
                                                    'border-brand-green/40 text-brand-green',
                                            )}
                                        >
                                            {stage.status_label}
                                        </Badge>
                                    </div>

                                    <div className="grid gap-4 p-5 sm:grid-cols-2">
                                        <Track
                                            label="Lessons"
                                            done={stage.lessons_done}
                                            total={stage.lessons_total}
                                        />
                                        <Track
                                            label="Poolside sessions"
                                            done={stage.practicals_done}
                                            total={stage.practicals_required}
                                        />
                                    </div>

                                    {/*
                                     * Every attempt, passed or failed. A
                                     * failed exam is part of somebody's
                                     * record and seeing it is how they know
                                     * what is left.
                                     */}
                                    <div className="border-t border-border/60 px-5 py-4">
                                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                            Exam attempts
                                        </p>

                                        {stage.attempts.length === 0 ? (
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                Not sat yet. {stage.pass_mark}%
                                                to pass,{' '}
                                                {stage.attempts_allowed}{' '}
                                                attempts allowed.
                                            </p>
                                        ) : (
                                            <ul className="mt-3 space-y-2">
                                                {stage.attempts.map(
                                                    (attempt) => (
                                                        <li
                                                            key={attempt.number}
                                                            className="flex items-center gap-3 text-sm"
                                                        >
                                                            {attempt.passed ? (
                                                                <CheckCircle2 className="size-4 shrink-0 text-brand-green" />
                                                            ) : (
                                                                <XCircle
                                                                    className={cn(
                                                                        'size-4 shrink-0',
                                                                        attempt.status ===
                                                                            'in_progress'
                                                                            ? 'text-muted-foreground'
                                                                            : 'text-brand-red',
                                                                    )}
                                                                />
                                                            )}
                                                            <span className="text-muted-foreground">
                                                                Attempt{' '}
                                                                {attempt.number}
                                                            </span>
                                                            <span className="font-medium tabular-nums">
                                                                {attempt.score ===
                                                                null
                                                                    ? 'In progress'
                                                                    : `${attempt.score}%`}
                                                            </span>
                                                            {attempt.score !==
                                                                null && (
                                                                <span className="text-xs text-muted-foreground tabular-nums">
                                                                    {
                                                                        attempt.correct
                                                                    }
                                                                    /
                                                                    {
                                                                        attempt.total
                                                                    }{' '}
                                                                    ·{' '}
                                                                    {
                                                                        attempt.sat_on
                                                                    }
                                                                </span>
                                                            )}
                                                            <span
                                                                className={cn(
                                                                    'ml-auto text-xs font-medium',
                                                                    attempt.passed
                                                                        ? 'text-brand-green'
                                                                        : 'text-muted-foreground',
                                                                )}
                                                            >
                                                                {attempt.passed
                                                                    ? 'Passed'
                                                                    : attempt.status ===
                                                                        'in_progress'
                                                                      ? 'Open'
                                                                      : 'Not passed'}
                                                            </span>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        )}
                                    </div>

                                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border/60 bg-muted/30 px-5 py-4">
                                        <p className="text-sm text-muted-foreground">
                                            {nextStep(stage)}
                                        </p>

                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                href={academy.show(
                                                    stage.course_slug,
                                                )}
                                            >
                                                <BookOpen className="size-4" />
                                                Open course
                                            </Link>
                                        </Button>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </section>
                )}

                <section>
                    <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Your certificates
                    </h2>

                    {held.length === 0 ? (
                        <p className="mt-3 text-sm text-muted-foreground">
                            A certificate is issued once the exam is passed and
                            every poolside session is signed off.
                        </p>
                    ) : (
                        <div className="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {held.map((certificate) => (
                                <article
                                    key={certificate.code}
                                    className="flex flex-col rounded-xl border border-border bg-card p-5"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <Award
                                            className={cn(
                                                'size-5',
                                                certificate.valid
                                                    ? 'text-brand-green'
                                                    : 'text-muted-foreground',
                                            )}
                                        />
                                        {!certificate.valid && (
                                            <Badge
                                                variant="outline"
                                                className="border-brand-red/40 text-brand-red"
                                            >
                                                {certificate.expired
                                                    ? 'Expired'
                                                    : 'Not valid'}
                                            </Badge>
                                        )}
                                    </div>

                                    <h3 className="mt-4 font-semibold text-balance">
                                        {certificate.title}
                                    </h3>
                                    <p className="mt-1 font-mono text-xs text-muted-foreground">
                                        {certificate.code}
                                    </p>
                                    <p className="mt-3 text-sm text-muted-foreground">
                                        Issued {certificate.issued_on}
                                        {certificate.expires_on &&
                                            ` · valid until ${certificate.expires_on}`}
                                    </p>

                                    <Button
                                        asChild
                                        size="sm"
                                        className="mt-5 w-full"
                                    >
                                        {/*
                                         * A plain anchor, not an Inertia
                                         * link: the response is a PDF, and
                                         * it opens in the browser's own
                                         * viewer, which already carries
                                         * save and print.
                                         */}
                                        <a
                                            href={
                                                certificates.pdf(
                                                    certificate.code,
                                                ).url
                                            }
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <FileText className="size-4" />
                                            View certificate
                                        </a>
                                    </Button>

                                    <a
                                        href={`${certificates.pdf(certificate.code).url}?download=1`}
                                        className="mt-2 inline-flex items-center justify-center gap-1.5 text-xs text-muted-foreground transition-colors hover:text-foreground"
                                    >
                                        <Download className="size-3.5" />
                                        Save a copy
                                    </a>
                                </article>
                            ))}
                        </div>
                    )}
                </section>

                {accreditations.length > 0 && (
                    <section>
                        <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                            Accreditations held
                        </h2>

                        <div className="mt-4 overflow-x-auto rounded-xl border border-border">
                            <table className="w-full min-w-[560px] text-sm">
                                <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Category
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Grade
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Attained
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Lapses
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Standing
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {accreditations.map((accreditation) => (
                                        <tr
                                            key={`${accreditation.category}-${accreditation.grade}`}
                                            className="border-b border-border/60 bg-card last:border-0"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {accreditation.category}
                                            </td>
                                            <td className="px-4 py-3">
                                                {accreditation.grade}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground tabular-nums">
                                                {accreditation.attained_on ??
                                                    '—'}
                                            </td>
                                            <td
                                                className={cn(
                                                    'px-4 py-3 tabular-nums',
                                                    accreditation.lapses_soon
                                                        ? 'font-medium text-brand-red'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                {accreditation.expires_on ??
                                                    '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="outline">
                                                    {accreditation.status_label}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                )}

                {sessions.length > 0 && (
                    <section>
                        <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                            Recent poolside work
                        </h2>

                        <ul className="mt-4 divide-y divide-border overflow-hidden rounded-xl border border-border">
                            {sessions.map((session, index) => (
                                <li
                                    key={`${session.gala}-${session.worked_on}-${index}`}
                                    className="flex flex-wrap items-center gap-x-4 gap-y-1 bg-card px-4 py-3 text-sm"
                                >
                                    <ClipboardCheck className="size-4 shrink-0 text-muted-foreground" />
                                    <span className="font-medium">
                                        {session.gala}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {session.role}
                                        {session.as_learner && ' · as learner'}
                                    </span>
                                    <span className="text-muted-foreground tabular-nums">
                                        {session.worked_on}
                                    </span>
                                    <span
                                        className={cn(
                                            'ml-auto text-xs font-medium',
                                            session.signed_off
                                                ? 'text-brand-green'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {session.signed_off
                                            ? 'Signed off'
                                            : 'Awaiting signature'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
