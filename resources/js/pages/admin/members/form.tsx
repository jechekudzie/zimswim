import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

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

type MemberForm = {
    id: number;
    membership_number: string;
    organization_id: number;
    province_organization_id: number | null;
    first_name: string;
    last_name: string;
    date_of_birth: string;
    gender: string;
    status: string;
    school: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    medical_notes: string | null;
    photo_consent: boolean;
    consent_given: boolean;
    consent_given_by: string | null;
    registrations: {
        season: string;
        status: string;
        fully_capitated: boolean;
    }[];
};

function Field({
    label,
    htmlFor,
    children,
    hint,
}: {
    label: string;
    htmlFor: string;
    children: React.ReactNode;
    hint?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
        </div>
    );
}

export default function MemberForm({
    member,
    clubs,
    provinces,
    statuses,
    suggestedNumber,
}: {
    member: MemberForm | null;
    clubs: Option<number>[];
    provinces: Option<number>[];
    statuses: Option[];
    suggestedNumber: string | null;
}) {
    useFlashMessages();
    const editing = member !== null;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Swimmers', href: admin.members.index().url },
                {
                    title: editing ? 'Edit' : 'Register',
                    href: admin.members.index().url,
                },
            ]}
        >
            <Head title={editing ? 'Edit swimmer' : 'Register swimmer'} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.members.index()}>
                        <ArrowLeft className="size-4" />
                        Back to swimmers
                    </Link>
                </Button>

                <PageHeader
                    title={
                        editing
                            ? `${member.first_name} ${member.last_name}`
                            : 'Register a swimmer'
                    }
                    description="A swimmer under 18 cannot be registered without a recorded parent or guardian consent."
                />

                <Form
                    {...(editing
                        ? admin.members.update.form(member.id)
                        : admin.members.store.form())}
                    className="grid gap-8 lg:grid-cols-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="space-y-6 lg:col-span-2">
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">
                                        Swimmer details
                                    </h2>
                                    <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                        <Field
                                            label="First name"
                                            htmlFor="first_name"
                                        >
                                            <Input
                                                id="first_name"
                                                name="first_name"
                                                defaultValue={
                                                    member?.first_name ?? ''
                                                }
                                                required
                                            />
                                            <InputError
                                                message={errors.first_name}
                                            />
                                        </Field>

                                        <Field
                                            label="Last name"
                                            htmlFor="last_name"
                                        >
                                            <Input
                                                id="last_name"
                                                name="last_name"
                                                defaultValue={
                                                    member?.last_name ?? ''
                                                }
                                                required
                                            />
                                            <InputError
                                                message={errors.last_name}
                                            />
                                        </Field>

                                        <Field
                                            label="Date of birth"
                                            htmlFor="date_of_birth"
                                            hint="Age group is calculated from this and the gala's age-up date."
                                        >
                                            <Input
                                                id="date_of_birth"
                                                name="date_of_birth"
                                                type="date"
                                                defaultValue={
                                                    member?.date_of_birth ?? ''
                                                }
                                                required
                                            />
                                            <InputError
                                                message={errors.date_of_birth}
                                            />
                                        </Field>

                                        <Field label="Gender" htmlFor="gender">
                                            <select
                                                id="gender"
                                                name="gender"
                                                defaultValue={
                                                    member?.gender ?? 'female'
                                                }
                                                className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                            >
                                                <option value="female">
                                                    Female
                                                </option>
                                                <option value="male">
                                                    Male
                                                </option>
                                            </select>
                                            <InputError
                                                message={errors.gender}
                                            />
                                        </Field>

                                        <Field
                                            label="ZimSwim number"
                                            htmlFor="membership_number"
                                        >
                                            <Input
                                                id="membership_number"
                                                name="membership_number"
                                                defaultValue={
                                                    member?.membership_number ??
                                                    suggestedNumber ??
                                                    ''
                                                }
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors.membership_number
                                                }
                                            />
                                        </Field>

                                        <Field label="School" htmlFor="school">
                                            <Input
                                                id="school"
                                                name="school"
                                                defaultValue={
                                                    member?.school ?? ''
                                                }
                                            />
                                        </Field>
                                    </div>
                                </section>

                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">
                                        Consent and safeguarding
                                    </h2>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Required under the Cyber and Data
                                        Protection Act for anyone under 18.
                                    </p>

                                    <div className="mt-5 space-y-4">
                                        <label className="flex items-start gap-3">
                                            <Checkbox
                                                name="consent_given"
                                                value="1"
                                                defaultChecked={
                                                    member?.consent_given ??
                                                    false
                                                }
                                            />
                                            <span className="text-sm">
                                                A parent or guardian has
                                                consented to this swimmer's
                                                registration and the processing
                                                of their data.
                                            </span>
                                        </label>
                                        <InputError
                                            message={errors.consent_given}
                                        />

                                        <Field
                                            label="Consent given by"
                                            htmlFor="consent_given_by"
                                        >
                                            <Input
                                                id="consent_given_by"
                                                name="consent_given_by"
                                                defaultValue={
                                                    member?.consent_given_by ??
                                                    ''
                                                }
                                                placeholder="Parent or guardian name"
                                            />
                                            <InputError
                                                message={
                                                    errors.consent_given_by
                                                }
                                            />
                                        </Field>

                                        <label className="flex items-start gap-3">
                                            <Checkbox
                                                name="photo_consent"
                                                value="1"
                                                defaultChecked={
                                                    member?.photo_consent ??
                                                    false
                                                }
                                            />
                                            <span className="text-sm">
                                                Photographs of this swimmer may
                                                appear on public pages.
                                            </span>
                                        </label>
                                    </div>

                                    <div className="mt-6 grid gap-5 sm:grid-cols-2">
                                        <Field
                                            label="Emergency contact"
                                            htmlFor="emergency_contact_name"
                                        >
                                            <Input
                                                id="emergency_contact_name"
                                                name="emergency_contact_name"
                                                defaultValue={
                                                    member?.emergency_contact_name ??
                                                    ''
                                                }
                                            />
                                        </Field>
                                        <Field
                                            label="Emergency phone"
                                            htmlFor="emergency_contact_phone"
                                        >
                                            <Input
                                                id="emergency_contact_phone"
                                                name="emergency_contact_phone"
                                                defaultValue={
                                                    member?.emergency_contact_phone ??
                                                    ''
                                                }
                                            />
                                        </Field>
                                    </div>
                                </section>
                            </div>

                            <aside className="space-y-6">
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">
                                        Affiliation
                                    </h2>
                                    <div className="mt-5 space-y-5">
                                        <Field
                                            label="Club"
                                            htmlFor="organization_id"
                                        >
                                            <select
                                                id="organization_id"
                                                name="organization_id"
                                                defaultValue={
                                                    member?.organization_id ??
                                                    clubs[0]?.value
                                                }
                                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                            >
                                                {clubs.map((club) => (
                                                    <option
                                                        key={club.value}
                                                        value={club.value}
                                                    >
                                                        {club.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.organization_id}
                                            />
                                        </Field>

                                        <Field
                                            label="Sporting province"
                                            htmlFor="province_organization_id"
                                            hint="A swimmer eligible for two provinces must choose one for the season."
                                        >
                                            <select
                                                id="province_organization_id"
                                                name="province_organization_id"
                                                defaultValue={
                                                    member?.province_organization_id ??
                                                    ''
                                                }
                                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                            >
                                                <option value="">
                                                    Not set
                                                </option>
                                                {provinces.map((province) => (
                                                    <option
                                                        key={province.value}
                                                        value={province.value}
                                                    >
                                                        {province.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </Field>

                                        <Field label="Status" htmlFor="status">
                                            <select
                                                id="status"
                                                name="status"
                                                defaultValue={
                                                    member?.status ?? 'pending'
                                                }
                                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                            >
                                                {statuses.map((status) => (
                                                    <option
                                                        key={status.value}
                                                        value={status.value}
                                                    >
                                                        {status.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </Field>
                                    </div>
                                </section>

                                {editing && member.registrations.length > 0 && (
                                    <section className="rounded-xl border border-border bg-card p-6">
                                        <h2 className="font-semibold">
                                            Season registrations
                                        </h2>
                                        <ul className="mt-4 space-y-3">
                                            {member.registrations.map(
                                                (registration) => (
                                                    <li
                                                        key={
                                                            registration.season
                                                        }
                                                        className="flex items-center justify-between gap-2 text-sm"
                                                    >
                                                        <span>
                                                            {
                                                                registration.season
                                                            }
                                                        </span>
                                                        <Badge
                                                            variant={
                                                                registration.fully_capitated
                                                                    ? 'default'
                                                                    : 'outline'
                                                            }
                                                        >
                                                            {registration.fully_capitated
                                                                ? 'Capitated'
                                                                : 'Fees due'}
                                                        </Badge>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </section>
                                )}

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {editing
                                        ? 'Save changes'
                                        : 'Register swimmer'}
                                </Button>
                            </aside>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
