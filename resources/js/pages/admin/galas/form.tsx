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

type GalaFormData = {
    id: number;
    slug: string;
    name: string;
    season_id: number;
    host_organization_id: number;
    venue_id: number | null;
    level: string;
    course: string;
    starts_on: string;
    ends_on: string;
    age_up_date: string | null;
    entries_close_at: string | null;
    entry_fee: string;
    late_entry_fee: string;
    eligible_ages: string | null;
    description: string | null;
    entries_email: string | null;
    qualifying_times_apply: boolean;
};

export default function GalaForm({
    gala,
    hosts,
    venues,
    seasons,
    levels,
    courses,
}: {
    gala: GalaFormData | null;
    hosts: Option<number>[];
    venues: (Option<number> & { grade: string })[];
    seasons: Option<number>[];
    levels: Option[];
    courses: Option[];
}) {
    useFlashMessages();
    const editing = gala !== null;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Galas', href: admin.galas.index().url },
                {
                    title: editing ? gala.name : 'Request a gala',
                    href: admin.galas.index().url,
                },
            ]}
        >
            <Head title={editing ? gala.name : 'Request a gala'} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.galas.index()}>
                        <ArrowLeft className="size-4" />
                        Back to galas
                    </Link>
                </Button>

                <PageHeader
                    title={editing ? gala.name : 'Request a gala'}
                    description="A new request goes to the board for sanction. The system warns of clashes and checks the venue grade before it can be approved."
                />

                <Form
                    {...(editing
                        ? admin.galas.update.form(gala.slug)
                        : admin.galas.store.form())}
                    className="grid gap-6 lg:grid-cols-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="space-y-6 lg:col-span-2">
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">The gala</h2>
                                    <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                        <div className="sm:col-span-2">
                                            <TextField
                                                label="Name"
                                                name="name"
                                                defaultValue={gala?.name}
                                                error={errors.name}
                                                required
                                            />
                                        </div>
                                        <SelectField
                                            label="Season"
                                            name="season_id"
                                            options={seasons}
                                            defaultValue={
                                                gala?.season_id ??
                                                seasons[0]?.value
                                            }
                                            error={errors.season_id}
                                        />
                                        <SelectField
                                            label="Host"
                                            name="host_organization_id"
                                            options={hosts}
                                            defaultValue={
                                                gala?.host_organization_id ??
                                                hosts[0]?.value
                                            }
                                            error={errors.host_organization_id}
                                        />
                                        <SelectField
                                            label="Level"
                                            name="level"
                                            options={levels}
                                            defaultValue={gala?.level ?? 'club'}
                                            error={errors.level}
                                        />
                                        <SelectField
                                            label="Course"
                                            name="course"
                                            options={courses}
                                            defaultValue={gala?.course ?? 'SCM'}
                                            error={errors.course}
                                        />
                                        <SelectField
                                            label="Venue"
                                            name="venue_id"
                                            options={venues.map((venue) => ({
                                                value: venue.value,
                                                label: `${venue.label} — ${venue.grade}`,
                                            }))}
                                            defaultValue={gala?.venue_id}
                                            placeholder="Not booked yet"
                                            hint="The venue must be graded for the gala level before it can be sanctioned."
                                            error={errors.venue_id}
                                        />
                                        <TextField
                                            label="Eligible ages"
                                            name="eligible_ages"
                                            defaultValue={gala?.eligible_ages}
                                            placeholder="e.g. 8 years and above"
                                            error={errors.eligible_ages}
                                        />
                                    </div>
                                </section>

                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">
                                        Dates and entries
                                    </h2>
                                    <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                        <TextField
                                            label="Starts on"
                                            name="starts_on"
                                            type="date"
                                            defaultValue={gala?.starts_on}
                                            error={errors.starts_on}
                                            required
                                        />
                                        <TextField
                                            label="Ends on"
                                            name="ends_on"
                                            type="date"
                                            defaultValue={gala?.ends_on}
                                            error={errors.ends_on}
                                            required
                                        />
                                        <TextField
                                            label="Age-up date"
                                            name="age_up_date"
                                            type="date"
                                            defaultValue={gala?.age_up_date}
                                            hint="Leave blank to use the season's age-up date."
                                            error={errors.age_up_date}
                                        />
                                        <TextField
                                            label="Entries close"
                                            name="entries_close_at"
                                            type="date"
                                            defaultValue={
                                                gala?.entries_close_at
                                            }
                                            error={errors.entries_close_at}
                                        />
                                        <TextField
                                            label="Entry fee (USD)"
                                            name="entry_fee"
                                            type="number"
                                            step="0.01"
                                            defaultValue={gala?.entry_fee ?? 20}
                                            error={errors.entry_fee}
                                            required
                                        />
                                        <TextField
                                            label="Late entry fee (USD)"
                                            name="late_entry_fee"
                                            type="number"
                                            step="0.01"
                                            defaultValue={
                                                gala?.late_entry_fee ?? 0
                                            }
                                            error={errors.late_entry_fee}
                                        />
                                        <TextField
                                            label="Entries email"
                                            name="entries_email"
                                            type="email"
                                            defaultValue={gala?.entries_email}
                                            error={errors.entries_email}
                                        />
                                        <div className="self-end pb-2">
                                            <CheckboxField
                                                label="Qualifying times apply"
                                                name="qualifying_times_apply"
                                                defaultChecked={
                                                    gala?.qualifying_times_apply
                                                }
                                            />
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <aside className="space-y-6">
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">
                                        Key points
                                    </h2>
                                    <div className="mt-5">
                                        <TextareaField
                                            label="Description"
                                            name="description"
                                            rows={10}
                                            defaultValue={gala?.description}
                                            hint="Rules, age groups and anything entrants should know."
                                            error={errors.description}
                                        />
                                    </div>
                                </section>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {editing
                                        ? 'Save changes'
                                        : 'Submit for sanction'}
                                </Button>
                            </aside>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
