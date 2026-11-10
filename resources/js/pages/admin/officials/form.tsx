import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { SelectField, TextField } from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type OfficialFormData = {
    id: number;
    registration_number: string;
    organization_id: number;
    province_organization_id: number | null;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    date_of_birth: string | null;
    status: string;
};

export default function OfficialForm({
    official,
    clubs,
    provinces,
}: {
    official: OfficialFormData | null;
    clubs: Option<number>[];
    provinces: Option<number>[];
}) {
    useFlashMessages();
    const editing = official !== null;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Officials', href: admin.officials.index().url },
                {
                    title: editing ? 'Edit' : 'Add official',
                    href: admin.officials.index().url,
                },
            ]}
        >
            <Head title={editing ? 'Edit official' : 'Add official'} />

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
                    title={
                        editing
                            ? `${official.first_name} ${official.last_name}`
                            : 'Add a technical official'
                    }
                    description="Officials must be registered members of a club and the province in their place of domicile."
                />

                <Form
                    {...(editing
                        ? admin.officials.update.form(official.id)
                        : admin.officials.store.form())}
                    className="max-w-3xl"
                >
                    {({ errors, processing }) => (
                        <section className="rounded-xl border border-border bg-card p-6">
                            <div className="grid gap-5 sm:grid-cols-2">
                                <TextField
                                    label="First name"
                                    name="first_name"
                                    defaultValue={official?.first_name}
                                    error={errors.first_name}
                                    required
                                />
                                <TextField
                                    label="Last name"
                                    name="last_name"
                                    defaultValue={official?.last_name}
                                    error={errors.last_name}
                                    required
                                />
                                <TextField
                                    label="Registration number"
                                    name="registration_number"
                                    defaultValue={official?.registration_number}
                                    error={errors.registration_number}
                                    required
                                />
                                <TextField
                                    label="Date of birth"
                                    name="date_of_birth"
                                    type="date"
                                    defaultValue={official?.date_of_birth}
                                    hint="Used to enforce the minimum age for each officiating category."
                                    error={errors.date_of_birth}
                                />
                                <TextField
                                    label="Email"
                                    name="email"
                                    type="email"
                                    defaultValue={official?.email}
                                    error={errors.email}
                                />
                                <TextField
                                    label="Phone"
                                    name="phone"
                                    defaultValue={official?.phone}
                                    error={errors.phone}
                                />
                                <SelectField
                                    label="Club of domicile"
                                    name="organization_id"
                                    options={clubs}
                                    defaultValue={
                                        official?.organization_id ??
                                        clubs[0]?.value
                                    }
                                    error={errors.organization_id}
                                />
                                <SelectField
                                    label="Province"
                                    name="province_organization_id"
                                    options={provinces}
                                    defaultValue={
                                        official?.province_organization_id
                                    }
                                    placeholder="Not set"
                                    error={errors.province_organization_id}
                                />
                                <SelectField
                                    label="Status"
                                    name="status"
                                    options={[
                                        { value: 'active', label: 'Active' },
                                        {
                                            value: 'inactive',
                                            label: 'Inactive',
                                        },
                                        {
                                            value: 'suspended',
                                            label: 'Suspended',
                                        },
                                    ]}
                                    defaultValue={official?.status ?? 'active'}
                                    error={errors.status}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-6"
                                disabled={processing}
                            >
                                {editing ? 'Save changes' : 'Add official'}
                            </Button>
                        </section>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
