import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Award, FileText, Pencil } from 'lucide-react';

import { SelectField, TextField } from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import certificateRoutes from '@/routes/certificates';
import type { Option } from '@/types';

type Accreditation = {
    id: number;
    category: string;
    category_label: string;
    grade: string;
    grade_label: string;
    status: string;
    status_label: string;
    learner_sessions_completed: number;
    learner_sessions_required: number;
    outstanding_sessions: number;
    ready_for_exam: boolean;
    clinic_attended_on: string | null;
    exam_passed_on: string | null;
    attained_on: string | null;
    expires_on: string | null;
    has_lapsed: boolean;
    ratified_at: string | null;
};

export default function OfficialShow({
    official,
    accreditations,
    certificates,
    sessions,
    availableCategories,
    grades,
    can,
}: {
    official: {
        id: number;
        registration_number: string;
        name: string;
        email: string | null;
        phone: string | null;
        club: string;
        province: string | null;
        status: string;
    };
    accreditations: Accreditation[];
    certificates: {
        code: string;
        title: string;
        grade: string | null;
        issued_on: string;
        expires_on: string | null;
        valid: boolean;
        expired: boolean;
    }[];
    sessions: {
        role: string;
        gala: string | null;
        worked_on: string;
        as_learner: boolean;
        signed_off: boolean;
    }[];
    availableCategories: {
        value: string;
        label: string;
        minimum_age: number;
    }[];
    grades: Option[];
    can: { manage: boolean; ratify: boolean };
}) {
    useFlashMessages();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Officials', href: admin.officials.index().url },
                {
                    title: official.name,
                    href: admin.officials.show(official.id).url,
                },
            ]}
        >
            <Head title={official.name} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.officials.index()}>
                        <ArrowLeft className="size-4" />
                        Back to register
                    </Link>
                </Button>

                <PageHeader
                    title={official.name}
                    description={`${official.registration_number} · ${official.club}${official.province ? ` · ${official.province}` : ''}`}
                    actions={
                        can.manage && (
                            <Button asChild variant="outline">
                                <Link href={admin.officials.edit(official.id)}>
                                    <Pencil className="size-4" />
                                    Edit
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">Accreditations</h2>

                            {accreditations.length === 0 ? (
                                <p className="mt-5 text-sm text-muted-foreground">
                                    No categories started yet. Every official
                                    starts as a timekeeper.
                                </p>
                            ) : (
                                <div className="mt-5 space-y-5">
                                    {accreditations.map((accreditation) => (
                                        <div
                                            key={accreditation.id}
                                            className="rounded-lg border border-border p-5"
                                        >
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <h3 className="font-medium">
                                                        {
                                                            accreditation.category_label
                                                        }
                                                    </h3>
                                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                                        {
                                                            accreditation.grade_label
                                                        }
                                                        {accreditation.attained_on
                                                            ? ` since ${accreditation.attained_on}`
                                                            : ''}
                                                    </p>
                                                </div>
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        accreditation.has_lapsed
                                                            ? 'border-brand-red/40 text-brand-red'
                                                            : accreditation.status ===
                                                                'active'
                                                              ? 'border-brand-green/40 text-brand-green'
                                                              : ''
                                                    }
                                                >
                                                    {accreditation.has_lapsed
                                                        ? 'Lapsed'
                                                        : accreditation.status_label}
                                                </Badge>
                                            </div>

                                            <div className="mt-4">
                                                <div className="flex items-center justify-between text-xs text-muted-foreground">
                                                    <span>
                                                        Learner sessions
                                                    </span>
                                                    <span className="tabular-nums">
                                                        {
                                                            accreditation.learner_sessions_completed
                                                        }
                                                        {' / '}
                                                        {
                                                            accreditation.learner_sessions_required
                                                        }
                                                    </span>
                                                </div>
                                                <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full rounded-full bg-primary"
                                                        style={{
                                                            width: `${Math.min(100, (accreditation.learner_sessions_completed / accreditation.learner_sessions_required) * 100)}%`,
                                                        }}
                                                    />
                                                </div>
                                            </div>

                                            {can.manage && (
                                                <Form
                                                    {...admin.officials.accreditations.update.form(
                                                        [
                                                            official.id,
                                                            accreditation.id,
                                                        ],
                                                    )}
                                                    className="mt-5 grid gap-3 border-t border-border pt-4 sm:grid-cols-4"
                                                >
                                                    {({
                                                        errors,
                                                        processing,
                                                    }) => (
                                                        <>
                                                            <TextField
                                                                label="Sessions"
                                                                name="learner_sessions_completed"
                                                                type="number"
                                                                defaultValue={
                                                                    accreditation.learner_sessions_completed
                                                                }
                                                                error={
                                                                    errors.learner_sessions_completed
                                                                }
                                                            />
                                                            <TextField
                                                                label="Clinic"
                                                                name="clinic_attended_on"
                                                                type="date"
                                                                defaultValue={
                                                                    accreditation.clinic_attended_on
                                                                }
                                                                error={
                                                                    errors.clinic_attended_on
                                                                }
                                                            />
                                                            <TextField
                                                                label="Exam passed"
                                                                name="exam_passed_on"
                                                                type="date"
                                                                defaultValue={
                                                                    accreditation.exam_passed_on
                                                                }
                                                                error={
                                                                    errors.exam_passed_on
                                                                }
                                                            />
                                                            <div className="flex items-end">
                                                                <Button
                                                                    type="submit"
                                                                    size="sm"
                                                                    variant="outline"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                    className="w-full"
                                                                >
                                                                    Record
                                                                </Button>
                                                            </div>
                                                        </>
                                                    )}
                                                </Form>
                                            )}

                                            {can.ratify && (
                                                <Form
                                                    {...admin.officials.accreditations.ratify.form(
                                                        [
                                                            official.id,
                                                            accreditation.id,
                                                        ],
                                                    )}
                                                    className="mt-4 flex flex-wrap items-end gap-3"
                                                >
                                                    {({
                                                        errors,
                                                        processing,
                                                    }) => (
                                                        <>
                                                            <div className="w-48">
                                                                <SelectField
                                                                    label="Ratify at grade"
                                                                    name="grade"
                                                                    options={
                                                                        grades
                                                                    }
                                                                    defaultValue={
                                                                        accreditation.grade
                                                                    }
                                                                />
                                                            </div>
                                                            <Button
                                                                type="submit"
                                                                size="sm"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                <Award className="size-4" />
                                                                Ratify
                                                            </Button>
                                                            <div className="w-full">
                                                                <InputError
                                                                    message={
                                                                        errors.grade
                                                                    }
                                                                />
                                                            </div>
                                                        </>
                                                    )}
                                                </Form>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </section>

                        {/*
                         * The certificates themselves, not just the register
                         * row. A convenor checking somebody on a pool deck
                         * needs to open the paper, which is also the only
                         * thing carrying the QR code a stranger can scan.
                         */}
                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">Certificates</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Issued once the exam is passed and every
                                poolside session is signed off.
                            </p>

                            {certificates.length === 0 ? (
                                <p className="mt-5 text-sm text-muted-foreground">
                                    None issued yet.
                                </p>
                            ) : (
                                <ul className="mt-5 space-y-3">
                                    {certificates.map((certificate) => (
                                        <li
                                            key={certificate.code}
                                            className="flex flex-wrap items-center justify-between gap-3 border-b border-border/60 pb-3 last:border-0"
                                        >
                                            <div className="min-w-0">
                                                <p className="flex flex-wrap items-center gap-2 font-medium">
                                                    {certificate.title}
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
                                                </p>
                                                <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                                    {certificate.code} · issued{' '}
                                                    {certificate.issued_on}
                                                    {certificate.expires_on &&
                                                        ` · valid until ${certificate.expires_on}`}
                                                </p>
                                            </div>

                                            <div className="flex shrink-0 gap-2">
                                                {/*
                                                 * Plain anchors: one returns
                                                 * a PDF the browser must be
                                                 * allowed to handle, the
                                                 * other is the public page
                                                 * the QR code points at.
                                                 */}
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <a
                                                        href={`/verify/${certificate.code}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >
                                                        View
                                                    </a>
                                                </Button>
                                                <Button asChild size="sm">
                                                    <a
                                                        href={
                                                            certificateRoutes.pdf(
                                                                certificate.code,
                                                            ).url
                                                        }
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >
                                                        <FileText className="size-4" />
                                                        Open PDF
                                                    </a>
                                                </Button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">Session logbook</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                To be recognised as having worked a meet, an
                                official must work at least 50% of its sessions
                                and sign the register.
                            </p>

                            {sessions.length === 0 ? (
                                <p className="mt-5 text-sm text-muted-foreground">
                                    No sessions recorded yet.
                                </p>
                            ) : (
                                <ul className="mt-5 space-y-3">
                                    {sessions.map((session, index) => (
                                        <li
                                            key={`${session.worked_on}-${index}`}
                                            className="flex flex-wrap items-center justify-between gap-2 border-b border-border/60 pb-3 text-sm last:border-0"
                                        >
                                            <div>
                                                <p className="font-medium capitalize">
                                                    {session.role.replace(
                                                        /_/g,
                                                        ' ',
                                                    )}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {session.gala ??
                                                        'Unattached'}{' '}
                                                    · {session.worked_on}
                                                </p>
                                            </div>
                                            <div className="flex gap-2">
                                                {session.as_learner && (
                                                    <Badge variant="secondary">
                                                        Learner
                                                    </Badge>
                                                )}
                                                <Badge
                                                    variant={
                                                        session.signed_off
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                >
                                                    {session.signed_off
                                                        ? 'Signed off'
                                                        : 'Awaiting sign-off'}
                                                </Badge>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>

                    <aside className="space-y-6">
                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">Contact</h2>
                            <dl className="mt-4 space-y-3 text-sm">
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Email
                                    </dt>
                                    <dd>{official.email ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Phone
                                    </dt>
                                    <dd>{official.phone ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Status
                                    </dt>
                                    <dd className="capitalize">
                                        {official.status}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        {can.manage && availableCategories.length > 0 && (
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">
                                    Start a new category
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Only one new category may normally be
                                    started per season. Prerequisites and the
                                    minimum age are checked on save.
                                </p>

                                <Form
                                    {...admin.officials.accreditations.store.form(
                                        official.id,
                                    )}
                                    className="mt-5 space-y-4"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <SelectField
                                                label="Category"
                                                name="category"
                                                options={availableCategories.map(
                                                    (category) => ({
                                                        value: category.value,
                                                        label: `${category.label} (min ${category.minimum_age})`,
                                                    }),
                                                )}
                                                error={errors.category}
                                            />
                                            <TextField
                                                label="Clinic attended"
                                                name="clinic_attended_on"
                                                type="date"
                                                error={
                                                    errors.clinic_attended_on
                                                }
                                            />
                                            <Button
                                                type="submit"
                                                className="w-full"
                                                disabled={processing}
                                            >
                                                Start category
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </section>
                        )}
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}
