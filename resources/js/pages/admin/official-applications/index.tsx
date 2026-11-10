import { Head, Link, router, useForm } from '@inertiajs/react';
import { ClipboardList, TriangleAlert } from 'lucide-react';
import { useState } from 'react';

import { EmptyState } from '@/components/admin/empty-state';
import { ExportButton } from '@/components/admin/export-button';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import exports from '@/routes/admin/exports';
import type { Option, Paginated } from '@/types';

type Application = {
    reference: string;
    name: string;
    email: string;
    phone: string;
    date_of_birth: string;
    age: number;
    category: string;
    category_label: string;
    minimum_age: number;
    club: string | null;
    club_name_given: string | null;
    club_label: string;
    club_on_register: boolean;
    province: string | null;
    membership_number: string | null;
    motivation: string | null;
    status: string;
    status_label: string;
    is_open: boolean;
    applied_on: string | null;
    reviewed_on: string | null;
    reviewer: string | null;
    review_notes: string | null;
    registration_number: string | null;
};

const statusTone: Record<string, string> = {
    submitted: 'border-brand-gold/50 text-foreground',
    under_review: 'border-brand-deep/40 text-brand-deep',
    approved: 'border-brand-green/40 text-brand-green',
    declined: 'border-brand-red/40 text-brand-red',
    withdrawn: 'text-muted-foreground',
};

/**
 * One application, opened out.
 *
 * Everything a reviewer needs to decide is on the card: an age checked
 * against the category's own minimum, the club they named and whether it is
 * on the register, and what they wrote. The two actions carry their
 * consequences in their own words, because approving opens an account.
 */
function ApplicationCard({
    application,
    clubs,
    canReview,
}: {
    application: Application;
    clubs: Option<number>[];
    canReview: boolean;
}) {
    const [acting, setActing] = useState<'approve' | 'decline' | null>(null);

    const approval = useForm({
        organization_id: '' as string,
        review_notes: '',
    });

    const refusal = useForm({ review_notes: '' });

    function approve() {
        // An untouched select sends an empty string, which `integer` would
        // reject; the column is nullable, so it is sent as one.
        approval.transform((payload) => ({
            ...payload,
            organization_id: payload.organization_id || null,
        }));

        approval.post(
            admin.officialApplications.approve(application.reference).url,
            { preserveScroll: true },
        );
    }

    function decline() {
        refusal.post(
            admin.officialApplications.decline(application.reference).url,
            { preserveScroll: true },
        );
    }

    const tooYoung = application.age < application.minimum_age;
    const needsClub = !application.club_on_register;

    return (
        <article className="rounded-xl border border-border bg-card">
            <div className="flex flex-wrap items-start justify-between gap-4 border-b border-border/60 p-5">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="font-semibold">{application.name}</h2>
                        <Badge
                            variant="outline"
                            className={statusTone[application.status] ?? ''}
                        >
                            {application.status_label}
                        </Badge>
                        {application.registration_number && (
                            <Badge variant="secondary" className="font-mono">
                                {application.registration_number}
                            </Badge>
                        )}
                    </div>
                    <p className="mt-1 font-mono text-xs text-muted-foreground">
                        {application.reference} · applied{' '}
                        {application.applied_on}
                    </p>
                </div>

                <p className="text-right text-sm">
                    <span className="font-medium">
                        {application.category_label}
                    </span>
                    <span className="mt-0.5 block text-xs text-muted-foreground">
                        minimum age {application.minimum_age}
                    </span>
                </p>
            </div>

            <dl className="grid gap-x-8 gap-y-3 p-5 text-sm sm:grid-cols-2">
                <div className="flex gap-3">
                    <dt className="w-28 shrink-0 text-muted-foreground">Age</dt>
                    <dd
                        className={cn(
                            'font-medium tabular-nums',
                            tooYoung && 'text-brand-red',
                        )}
                    >
                        {application.age}
                        {tooYoung && (
                            <span className="ml-2 font-normal">
                                below the minimum
                            </span>
                        )}
                    </dd>
                </div>
                <div className="flex gap-3">
                    <dt className="w-28 shrink-0 text-muted-foreground">
                        Born
                    </dt>
                    <dd className="tabular-nums">
                        {application.date_of_birth}
                    </dd>
                </div>
                <div className="flex gap-3">
                    <dt className="w-28 shrink-0 text-muted-foreground">
                        Email
                    </dt>
                    <dd className="min-w-0 truncate">{application.email}</dd>
                </div>
                <div className="flex gap-3">
                    <dt className="w-28 shrink-0 text-muted-foreground">
                        Phone
                    </dt>
                    <dd>{application.phone}</dd>
                </div>
                <div className="flex gap-3">
                    <dt className="w-28 shrink-0 text-muted-foreground">
                        Club
                    </dt>
                    <dd>
                        {application.club_label}
                        {needsClub && (
                            <span className="mt-0.5 flex items-center gap-1.5 text-xs text-brand-red">
                                <TriangleAlert className="size-3" />
                                Not on the register — attach one to approve
                            </span>
                        )}
                    </dd>
                </div>
                <div className="flex gap-3">
                    <dt className="w-28 shrink-0 text-muted-foreground">
                        Province
                    </dt>
                    <dd>{application.province ?? '—'}</dd>
                </div>
                {application.membership_number && (
                    <div className="flex gap-3">
                        <dt className="w-28 shrink-0 text-muted-foreground">
                            ZimSwim no.
                        </dt>
                        <dd className="font-mono text-xs">
                            {application.membership_number}
                        </dd>
                    </div>
                )}
            </dl>

            {application.motivation && (
                <p className="border-t border-border/60 px-5 py-4 text-sm leading-relaxed text-muted-foreground">
                    “{application.motivation}”
                </p>
            )}

            {!application.is_open && application.review_notes && (
                <p className="border-t border-border/60 bg-muted/40 px-5 py-4 text-sm">
                    <span className="text-muted-foreground">
                        {application.reviewer ?? 'A reviewer'} on{' '}
                        {application.reviewed_on}:{' '}
                    </span>
                    {application.review_notes}
                </p>
            )}

            {application.is_open && canReview && (
                <div className="border-t border-border/60 p-5">
                    {acting === null && (
                        <div className="flex flex-wrap gap-3">
                            <Button
                                size="sm"
                                onClick={() => setActing('approve')}
                            >
                                Approve
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setActing('decline')}
                            >
                                Decline
                            </Button>
                        </div>
                    )}

                    {acting === 'approve' && (
                        <div className="grid max-w-lg gap-4">
                            <p className="text-sm text-muted-foreground">
                                This opens an account for {application.email},
                                puts them on the register as a learner, and
                                enrols them in the{' '}
                                {application.category_label.toLowerCase()}{' '}
                                course.
                            </p>

                            {needsClub && (
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`club-${application.reference}`}
                                    >
                                        Attach them to a club
                                    </Label>
                                    <select
                                        id={`club-${application.reference}`}
                                        value={approval.data.organization_id}
                                        onChange={(event) =>
                                            approval.setData(
                                                'organization_id',
                                                event.target.value,
                                            )
                                        }
                                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                                    >
                                        <option value="">Choose a club</option>
                                        {clubs.map((club) => (
                                            <option
                                                key={club.value}
                                                value={club.value}
                                            >
                                                {club.label}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-muted-foreground">
                                        They wrote “
                                        {application.club_name_given ??
                                            'nothing'}
                                        ”.
                                    </p>
                                    <InputError
                                        message={
                                            approval.errors.organization_id
                                        }
                                    />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`notes-${application.reference}`}
                                >
                                    Note for the file{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (optional)
                                    </span>
                                </Label>
                                <Input
                                    id={`notes-${application.reference}`}
                                    value={approval.data.review_notes}
                                    onChange={(event) =>
                                        approval.setData(
                                            'review_notes',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <Button
                                    size="sm"
                                    disabled={
                                        approval.processing ||
                                        (needsClub &&
                                            approval.data.organization_id ===
                                                '')
                                    }
                                    onClick={() => approve()}
                                >
                                    {approval.processing
                                        ? 'Approving…'
                                        : 'Confirm approval'}
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => setActing(null)}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    )}

                    {acting === 'decline' && (
                        <div className="grid max-w-lg gap-4">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`reason-${application.reference}`}
                                >
                                    Why not?
                                </Label>
                                <textarea
                                    id={`reason-${application.reference}`}
                                    rows={3}
                                    value={refusal.data.review_notes}
                                    onChange={(event) =>
                                        refusal.setData(
                                            'review_notes',
                                            event.target.value,
                                        )
                                    }
                                    className="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Sent to the applicant word for word, so
                                    write it to them.
                                </p>
                                <InputError
                                    message={refusal.errors.review_notes}
                                />
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    disabled={refusal.processing}
                                    onClick={() => decline()}
                                >
                                    {refusal.processing
                                        ? 'Sending…'
                                        : 'Decline and tell them'}
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => setActing(null)}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            )}
        </article>
    );
}

export default function OfficialApplicationsIndex({
    applications,
    filters,
    statuses,
    counts,
    clubs,
    can,
}: {
    applications: Paginated<Application>;
    filters: { status: string | null; search: string | null };
    statuses: Option[];
    counts: Record<string, number>;
    clubs: Option<number>[];
    can: { review: boolean };
}) {
    useFlashMessages();
    const [search, setSearch] = useState(filters.search ?? '');

    function apply(next: Record<string, string | null>) {
        router.get(
            admin.officialApplications.index().url,
            { search, status: filters.status, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const waiting = (counts.submitted ?? 0) + (counts.under_review ?? 0);

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Officials', href: admin.officials.index().url },
                {
                    title: 'Applications',
                    href: admin.officialApplications.index().url,
                },
            ]}
        >
            <Head title="Applications to officiate" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Applications to officiate"
                    description={
                        waiting === 0
                            ? 'Nobody is waiting on the committee.'
                            : `${waiting} ${waiting === 1 ? 'person is' : 'people are'} waiting on the committee. Approving one opens an account and enrols them.`
                    }
                    actions={
                        <ExportButton
                            url={exports.officialApplications().url}
                            params={{
                                search: filters.search,
                                status: filters.status,
                            }}
                        />
                    }
                />

                <div className="flex flex-wrap items-center gap-3">
                    <form
                        className="flex-1 sm:max-w-xs"
                        onSubmit={(event) => {
                            event.preventDefault();
                            apply({});
                        }}
                    >
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search name, email or reference"
                        />
                    </form>

                    <div className="flex flex-wrap gap-1.5">
                        <Button
                            size="sm"
                            variant={filters.status ? 'outline' : 'default'}
                            onClick={() => apply({ status: null })}
                        >
                            All
                        </Button>
                        {statuses.map((status) => (
                            <Button
                                key={status.value}
                                size="sm"
                                variant={
                                    filters.status === status.value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => apply({ status: status.value })}
                            >
                                {status.label}
                                {counts[status.value] !== undefined && (
                                    <span className="ml-1.5 tabular-nums opacity-70">
                                        {counts[status.value]}
                                    </span>
                                )}
                            </Button>
                        ))}
                    </div>
                </div>

                {applications.data.length === 0 ? (
                    <EmptyState
                        icon={ClipboardList}
                        title="No applications here"
                        description="Applications arrive from the pathway page on the public site."
                    />
                ) : (
                    <>
                        <div className="grid gap-4">
                            {applications.data.map((application) => (
                                <ApplicationCard
                                    key={application.reference}
                                    application={application}
                                    clubs={clubs}
                                    canReview={can.review}
                                />
                            ))}
                        </div>
                        <Pagination meta={applications} />
                    </>
                )}

                <p className="text-sm text-muted-foreground">
                    The pathway page applicants start from is{' '}
                    <Link
                        href="/officials"
                        className="underline underline-offset-4"
                    >
                        on the public site
                    </Link>
                    .
                </p>
            </div>
        </AppLayout>
    );
}
