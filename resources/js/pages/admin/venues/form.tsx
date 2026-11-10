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

type VenueForm = {
    id: number;
    slug: string;
    name: string;
    province_organization_id: number | null;
    owner_type: string;
    owner_name: string | null;
    contact_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    address: string | null;
    city: string | null;
    pool_length: number | null;
    lanes: number | null;
    depth_shallow: string | null;
    depth_deep: string | null;
    starting_blocks: boolean;
    backstroke_ledges: boolean;
    backstroke_flags: boolean;
    electronic_timing: boolean;
    scoreboard: boolean;
    warmup_pool: boolean;
    seating_capacity: number | null;
    changing_rooms: boolean;
    parking: boolean;
    power_backup: boolean;
    grade: string;
    last_inspected_on: string | null;
    condition_notes: string | null;
    notes: string | null;
    is_active: boolean;
};

export default function VenueForm({
    venue,
    provinces,
    grades,
}: {
    venue: VenueForm | null;
    provinces: Option<number>[];
    grades: Option[];
}) {
    useFlashMessages();
    const editing = venue !== null;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Venues', href: admin.venues.index().url },
                {
                    title: editing ? venue.name : 'Add venue',
                    href: admin.venues.index().url,
                },
            ]}
        >
            <Head title={editing ? venue.name : 'Add venue'} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.venues.index()}>
                        <ArrowLeft className="size-4" />
                        Back to venues
                    </Link>
                </Button>

                <PageHeader
                    title={editing ? venue.name : 'Add a venue'}
                    description="Record what the pool actually offers. The grade decides which galas may be held here."
                />

                <Form
                    {...(editing
                        ? admin.venues.update.form(venue.slug)
                        : admin.venues.store.form())}
                    className="grid gap-6 lg:grid-cols-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="space-y-6 lg:col-span-2">
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">
                                        Identity and location
                                    </h2>
                                    <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                        <TextField
                                            label="Venue name"
                                            name="name"
                                            defaultValue={venue?.name}
                                            error={errors.name}
                                            required
                                        />
                                        <SelectField
                                            label="Province"
                                            name="province_organization_id"
                                            options={provinces}
                                            defaultValue={
                                                venue?.province_organization_id
                                            }
                                            placeholder="Not set"
                                            error={
                                                errors.province_organization_id
                                            }
                                        />
                                        <SelectField
                                            label="Owner type"
                                            name="owner_type"
                                            options={[
                                                {
                                                    value: 'council',
                                                    label: 'City council',
                                                },
                                                {
                                                    value: 'school',
                                                    label: 'School',
                                                },
                                                {
                                                    value: 'club',
                                                    label: 'Club',
                                                },
                                                {
                                                    value: 'private',
                                                    label: 'Private',
                                                },
                                            ]}
                                            defaultValue={
                                                venue?.owner_type ?? 'council'
                                            }
                                            error={errors.owner_type}
                                        />
                                        <TextField
                                            label="Owner name"
                                            name="owner_name"
                                            defaultValue={venue?.owner_name}
                                            error={errors.owner_name}
                                        />
                                        <TextField
                                            label="City"
                                            name="city"
                                            defaultValue={venue?.city}
                                            error={errors.city}
                                        />
                                        <TextField
                                            label="Address"
                                            name="address"
                                            defaultValue={venue?.address}
                                            error={errors.address}
                                        />
                                    </div>
                                </section>

                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">The pool</h2>
                                    <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                        <SelectField
                                            label="Pool length"
                                            name="pool_length"
                                            options={[
                                                { value: '25', label: '25m' },
                                                { value: '50', label: '50m' },
                                            ]}
                                            defaultValue={
                                                venue?.pool_length
                                                    ? String(venue.pool_length)
                                                    : null
                                            }
                                            placeholder="Unknown"
                                            error={errors.pool_length}
                                        />
                                        <TextField
                                            label="Lanes"
                                            name="lanes"
                                            type="number"
                                            defaultValue={venue?.lanes}
                                            error={errors.lanes}
                                        />
                                        <TextField
                                            label="Shallow end depth (m)"
                                            name="depth_shallow"
                                            type="number"
                                            step="0.01"
                                            defaultValue={venue?.depth_shallow}
                                            error={errors.depth_shallow}
                                        />
                                        <TextField
                                            label="Deep end depth (m)"
                                            name="depth_deep"
                                            type="number"
                                            step="0.01"
                                            defaultValue={venue?.depth_deep}
                                            error={errors.depth_deep}
                                        />
                                        <TextField
                                            label="Seating capacity"
                                            name="seating_capacity"
                                            type="number"
                                            defaultValue={
                                                venue?.seating_capacity
                                            }
                                            error={errors.seating_capacity}
                                        />
                                        <TextField
                                            label="Last inspected"
                                            name="last_inspected_on"
                                            type="date"
                                            defaultValue={
                                                venue?.last_inspected_on
                                            }
                                            error={errors.last_inspected_on}
                                        />
                                    </div>

                                    <div className="mt-6 grid gap-3 border-t border-border pt-5 sm:grid-cols-2">
                                        <CheckboxField
                                            label="Starting blocks"
                                            name="starting_blocks"
                                            defaultChecked={
                                                venue?.starting_blocks
                                            }
                                        />
                                        <CheckboxField
                                            label="Backstroke ledges"
                                            name="backstroke_ledges"
                                            defaultChecked={
                                                venue?.backstroke_ledges
                                            }
                                        />
                                        <CheckboxField
                                            label="Backstroke flags"
                                            name="backstroke_flags"
                                            defaultChecked={
                                                venue?.backstroke_flags
                                            }
                                        />
                                        <CheckboxField
                                            label="Electronic timing"
                                            name="electronic_timing"
                                            defaultChecked={
                                                venue?.electronic_timing
                                            }
                                        />
                                        <CheckboxField
                                            label="Scoreboard"
                                            name="scoreboard"
                                            defaultChecked={venue?.scoreboard}
                                        />
                                        <CheckboxField
                                            label="Warm-up pool"
                                            name="warmup_pool"
                                            defaultChecked={venue?.warmup_pool}
                                        />
                                        <CheckboxField
                                            label="Changing rooms"
                                            name="changing_rooms"
                                            defaultChecked={
                                                venue?.changing_rooms
                                            }
                                        />
                                        <CheckboxField
                                            label="Parking"
                                            name="parking"
                                            defaultChecked={venue?.parking}
                                        />
                                        <CheckboxField
                                            label="Power backup"
                                            name="power_backup"
                                            defaultChecked={venue?.power_backup}
                                        />
                                    </div>
                                </section>
                            </div>

                            <aside className="space-y-6">
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">Grading</h2>
                                    <div className="mt-5 space-y-5">
                                        <SelectField
                                            label="Venue grade"
                                            name="grade"
                                            options={grades}
                                            defaultValue={
                                                venue?.grade ??
                                                'club_development'
                                            }
                                            hint="A gala can only be sanctioned into a pool graded for its level."
                                            error={errors.grade}
                                        />
                                        <TextField
                                            label="Condition note"
                                            name="condition_notes"
                                            defaultValue={
                                                venue?.condition_notes
                                            }
                                            hint="Shown as a warning on the public register."
                                            error={errors.condition_notes}
                                        />
                                        <TextareaField
                                            label="Notes"
                                            name="notes"
                                            defaultValue={venue?.notes}
                                            error={errors.notes}
                                        />
                                        <CheckboxField
                                            label="Venue is active"
                                            name="is_active"
                                            defaultChecked={
                                                venue?.is_active ?? true
                                            }
                                        />
                                    </div>
                                </section>

                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">Contact</h2>
                                    <div className="mt-5 space-y-5">
                                        <TextField
                                            label="Contact name"
                                            name="contact_name"
                                            defaultValue={venue?.contact_name}
                                            error={errors.contact_name}
                                        />
                                        <TextField
                                            label="Phone"
                                            name="contact_phone"
                                            defaultValue={venue?.contact_phone}
                                            error={errors.contact_phone}
                                        />
                                        <TextField
                                            label="Email"
                                            name="contact_email"
                                            type="email"
                                            defaultValue={venue?.contact_email}
                                            error={errors.contact_email}
                                        />
                                    </div>
                                </section>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {editing ? 'Save changes' : 'Add venue'}
                                </Button>
                            </aside>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
