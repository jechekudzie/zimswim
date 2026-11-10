import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import {
    CheckboxField,
    SelectField,
    TextareaField,
    TextField,
} from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type OrganizationFormData = {
    id: number;
    slug: string;
    name: string;
    short_name: string | null;
    code: string | null;
    type: string;
    parent_id: number | null;
    description: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    address: string | null;
    city: string | null;
    affiliation_status: string;
    is_active: boolean;
};

export default function OrganizationForm({
    organization,
    parents,
    types,
}: {
    organization: OrganizationFormData | null;
    parents: Option<number>[];
    types: Option[];
}) {
    useFlashMessages();
    const editing = organization !== null;

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Organisations',
                    href: admin.organizations.index().url,
                },
                {
                    title: editing ? organization.name : 'Add',
                    href: admin.organizations.index().url,
                },
            ]}
        >
            <Head title={editing ? organization.name : 'Add an organisation'} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.organizations.index()}>
                        <ArrowLeft className="size-4" />
                        Back to organisations
                    </Link>
                </Button>

                <PageHeader
                    title={editing ? organization.name : 'Add an organisation'}
                    description="Clubs affiliate to a province, and provinces to Zimbabwe Swimming. The tree decides who can see what."
                />

                <Form
                    {...(editing
                        ? admin.organizations.update.form(organization.slug)
                        : admin.organizations.store.form())}
                    className="max-w-3xl"
                >
                    {({ errors, processing }) => (
                        <div className="space-y-6">
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">Identity</h2>
                                <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                    <TextField
                                        label="Name"
                                        name="name"
                                        defaultValue={organization?.name}
                                        error={errors.name}
                                        required
                                    />
                                    <TextField
                                        label="Short name"
                                        name="short_name"
                                        defaultValue={organization?.short_name}
                                        error={errors.short_name}
                                    />
                                    <TextField
                                        label="Code"
                                        name="code"
                                        defaultValue={organization?.code}
                                        hint="Short identifier, e.g. HRE or BYO."
                                        error={errors.code}
                                    />
                                    <SelectField
                                        label="Type"
                                        name="type"
                                        options={types}
                                        defaultValue={
                                            organization?.type ?? 'club'
                                        }
                                        error={errors.type}
                                    />
                                    <SelectField
                                        label="Affiliated to"
                                        name="parent_id"
                                        options={parents}
                                        defaultValue={organization?.parent_id}
                                        placeholder="None (top of the tree)"
                                        error={errors.parent_id}
                                    />
                                    <SelectField
                                        label="Affiliation status"
                                        name="affiliation_status"
                                        options={[
                                            {
                                                value: 'pending',
                                                label: 'Pending',
                                            },
                                            {
                                                value: 'affiliated',
                                                label: 'Affiliated',
                                            },
                                            {
                                                value: 'lapsed',
                                                label: 'Lapsed',
                                            },
                                            {
                                                value: 'suspended',
                                                label: 'Suspended',
                                            },
                                        ]}
                                        defaultValue={
                                            organization?.affiliation_status ??
                                            'pending'
                                        }
                                        error={errors.affiliation_status}
                                    />
                                </div>

                                <div className="mt-5">
                                    <TextareaField
                                        label="Description"
                                        name="description"
                                        defaultValue={organization?.description}
                                        error={errors.description}
                                    />
                                </div>

                                <div className="mt-5">
                                    <CheckboxField
                                        label="Organisation is active"
                                        name="is_active"
                                        defaultChecked={
                                            organization?.is_active ?? true
                                        }
                                    />
                                </div>
                            </section>

                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">Contact</h2>
                                <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                    <TextField
                                        label="Email"
                                        name="email"
                                        type="email"
                                        defaultValue={organization?.email}
                                        error={errors.email}
                                    />
                                    <TextField
                                        label="Phone"
                                        name="phone"
                                        defaultValue={organization?.phone}
                                        error={errors.phone}
                                    />
                                    <TextField
                                        label="Website"
                                        name="website"
                                        type="url"
                                        defaultValue={organization?.website}
                                        error={errors.website}
                                    />
                                    <TextField
                                        label="City"
                                        name="city"
                                        defaultValue={organization?.city}
                                        error={errors.city}
                                    />
                                    <div className="sm:col-span-2">
                                        <TextField
                                            label="Address"
                                            name="address"
                                            defaultValue={organization?.address}
                                            error={errors.address}
                                        />
                                    </div>
                                </div>
                            </section>

                            <Button type="submit" disabled={processing}>
                                {editing
                                    ? 'Save changes'
                                    : 'Create organisation'}
                            </Button>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
