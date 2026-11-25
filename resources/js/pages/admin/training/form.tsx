import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import {
    CheckboxField,
    RichTextField,
    SelectField,
    TextField,
} from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type TrainingFormData = {
    id: number;
    slug: string;
    organization_id: number;
    venue_id: number | null;
    title: string;
    kind: string;
    category: string | null;
    description: string | null;
    held_on: string;
    ends_on: string | null;
    starts_at: string | null;
    ends_at: string | null;
    location: string | null;
    is_online: boolean;
    capacity: number | null;
    registration_closes_at: string | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    is_published: boolean;
};

export default function TrainingForm({
    training,
    kinds,
    categories,
    venues,
    provinces,
    clubs,
}: {
    training: TrainingFormData | null;
    kinds: Option[];
    categories: Option[];
    venues: Option<number>[];
    provinces: Option<number>[];
    clubs: Option<number>[];
}) {
    useFlashMessages();
    const editing = training !== null;

    const hosts = [...provinces, ...clubs];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                {
                    title: 'Clinics and exams',
                    href: admin.training.index().url,
                },
                {
                    title: editing ? training.title : 'Schedule',
                    href: editing
                        ? admin.training.edit(training.slug).url
                        : admin.training.create().url,
                },
            ]}
        >
            <Head title={editing ? training.title : 'Schedule a session'} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title={editing ? training.title : 'Schedule a session'}
                    description="A published session appears on the public site. Leave the places empty unless you genuinely need to cap it — a clinic is there to be attended."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href={admin.training.index()}>
                                <ArrowLeft className="size-4" />
                                Back
                            </Link>
                        </Button>
                    }
                />

                <Form
                    {...(editing
                        ? admin.training.update.form(training.slug)
                        : admin.training.store.form())}
                    className="max-w-3xl"
                >
                    {({ errors, processing }) => (
                        <div className="flex flex-col gap-6">
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">The session</h2>
                                <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <TextField
                                            label="Title"
                                            name="title"
                                            defaultValue={training?.title}
                                            error={errors.title}
                                            hint="What somebody looking for it would call it, for example Timekeeper clinic — Harare."
                                        />
                                    </div>

                                    <SelectField
                                        label="Kind"
                                        name="kind"
                                        options={kinds}
                                        defaultValue={
                                            training?.kind ?? 'clinic'
                                        }
                                        error={errors.kind}
                                    />

                                    <SelectField
                                        label="Category"
                                        name="category"
                                        options={categories}
                                        placeholder="Covers several"
                                        defaultValue={training?.category}
                                        error={errors.category}
                                    />

                                    <div className="sm:col-span-2">
                                        <SelectField
                                            label="Run by"
                                            name="organization_id"
                                            options={hosts}
                                            defaultValue={
                                                training?.organization_id
                                            }
                                            error={errors.organization_id}
                                        />
                                    </div>

                                    <div className="sm:col-span-2">
                                        <RichTextField
                                            label="What it covers"
                                            name="description"
                                            defaultValue={training?.description}
                                            error={errors.description}
                                        />
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">
                                    When and where
                                </h2>
                                <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                    <TextField
                                        label="Date"
                                        name="held_on"
                                        type="date"
                                        defaultValue={training?.held_on}
                                        error={errors.held_on}
                                    />
                                    <TextField
                                        label="Last day, if it runs over more than one"
                                        name="ends_on"
                                        type="date"
                                        defaultValue={training?.ends_on}
                                        error={errors.ends_on}
                                    />
                                    <TextField
                                        label="Starts"
                                        name="starts_at"
                                        type="time"
                                        defaultValue={training?.starts_at?.slice(
                                            0,
                                            5,
                                        )}
                                        error={errors.starts_at}
                                    />
                                    <TextField
                                        label="Ends"
                                        name="ends_at"
                                        type="time"
                                        defaultValue={training?.ends_at?.slice(
                                            0,
                                            5,
                                        )}
                                        error={errors.ends_at}
                                    />
                                    <SelectField
                                        label="Pool, if it is at one"
                                        name="venue_id"
                                        options={venues}
                                        placeholder="Not at a registered pool"
                                        defaultValue={training?.venue_id}
                                        error={errors.venue_id}
                                    />
                                    <TextField
                                        label="Where else"
                                        name="location"
                                        defaultValue={training?.location}
                                        error={errors.location}
                                        hint="A school hall, a club house, or the town."
                                    />
                                    <div className="sm:col-span-2">
                                        <CheckboxField
                                            label="Held online"
                                            name="is_online"
                                            defaultChecked={
                                                training?.is_online ?? false
                                            }
                                        />
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">Registration</h2>
                                <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                    <TextField
                                        label="Places"
                                        name="capacity"
                                        type="number"
                                        defaultValue={
                                            training?.capacity?.toString() ??
                                            undefined
                                        }
                                        error={errors.capacity}
                                        hint="Leave empty for no limit."
                                    />
                                    <TextField
                                        label="Registration closes"
                                        name="registration_closes_at"
                                        type="date"
                                        defaultValue={
                                            training?.registration_closes_at
                                        }
                                        error={errors.registration_closes_at}
                                        hint="Leave empty to take names until the day."
                                    />
                                    <TextField
                                        label="Contact name"
                                        name="contact_name"
                                        defaultValue={training?.contact_name}
                                        error={errors.contact_name}
                                    />
                                    <TextField
                                        label="Contact email"
                                        name="contact_email"
                                        type="email"
                                        defaultValue={training?.contact_email}
                                        error={errors.contact_email}
                                    />
                                    <TextField
                                        label="Contact phone"
                                        name="contact_phone"
                                        defaultValue={training?.contact_phone}
                                        error={errors.contact_phone}
                                    />
                                </div>

                                <div className="mt-5">
                                    <CheckboxField
                                        label="Show on the public site"
                                        name="is_published"
                                        defaultChecked={
                                            training?.is_published ?? false
                                        }
                                    />
                                </div>
                            </section>

                            <Button type="submit" disabled={processing}>
                                {editing
                                    ? 'Save changes'
                                    : 'Put it on the calendar'}
                            </Button>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
