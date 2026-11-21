import { Form, Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';

import {
    CheckboxField,
    Field,
    RichTextField,
    SelectField,
    TextField,
} from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type SponsorForm = {
    id: number;
    slug: string;
    name: string;
    tier: string;
    website: string | null;
    description: string | null;
    position: number;
    is_active: boolean;
    logo_url: string | null;
};

export default function SponsorForm({
    sponsor,
    tiers,
}: {
    sponsor: SponsorForm | null;
    tiers: Option[];
}) {
    useFlashMessages();
    const editing = sponsor !== null;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Sponsors', href: admin.sponsors.index().url },
                {
                    title: editing ? sponsor.name : 'Add a sponsor',
                    href: admin.sponsors.index().url,
                },
            ]}
        >
            <Head title={editing ? sponsor.name : 'Add a sponsor'} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="-ml-3 w-fit"
                >
                    <Link href={admin.sponsors.index()}>
                        <ArrowLeft className="size-4" />
                        Back to sponsors
                    </Link>
                </Button>

                <PageHeader
                    title={editing ? sponsor.name : 'Add a sponsor'}
                    description="Who they are and how they should appear on the home page."
                />

                <Form
                    {...(editing
                        ? admin.sponsors.update.form(sponsor.slug)
                        : admin.sponsors.store.form())}
                    className="grid gap-6 lg:grid-cols-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="space-y-6 lg:col-span-2">
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <div className="space-y-5">
                                        <TextField
                                            label="Name"
                                            name="name"
                                            defaultValue={sponsor?.name}
                                            required
                                            error={errors.name}
                                        />
                                        <TextField
                                            label="Website"
                                            name="website"
                                            type="url"
                                            defaultValue={sponsor?.website}
                                            placeholder="https://example.co.zw"
                                            error={errors.website}
                                        />
                                        <RichTextField
                                            label="About them"
                                            name="description"
                                            defaultValue={sponsor?.description}
                                            hint="Not shown on the home page band. Kept for the federation's own record and for anywhere a sponsor is written about."
                                            error={errors.description}
                                        />
                                    </div>
                                </section>
                            </div>

                            <aside className="space-y-6">
                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">
                                        How it appears
                                    </h2>
                                    <div className="mt-5 space-y-5">
                                        <SelectField
                                            label="Standing"
                                            name="tier"
                                            options={tiers}
                                            defaultValue={
                                                sponsor?.tier ?? 'partner'
                                            }
                                            error={errors.tier}
                                        />
                                        <TextField
                                            label="Order"
                                            name="position"
                                            type="number"
                                            defaultValue={
                                                sponsor?.position ?? 0
                                            }
                                            hint="Lower numbers stand first within a standing."
                                            error={errors.position}
                                        />
                                        <CheckboxField
                                            label="Show on the home page"
                                            name="is_active"
                                            defaultChecked={
                                                sponsor?.is_active ?? true
                                            }
                                        />
                                    </div>
                                </section>

                                <section className="rounded-xl border border-border bg-card p-6">
                                    <h2 className="font-semibold">Logo</h2>
                                    <div className="mt-5 space-y-4">
                                        {sponsor?.logo_url && (
                                            <img
                                                src={sponsor.logo_url}
                                                alt=""
                                                className="h-16 w-full object-contain"
                                            />
                                        )}
                                        <Field
                                            label={
                                                sponsor?.logo_url
                                                    ? 'Replace the logo'
                                                    : 'Add a logo'
                                            }
                                            htmlFor="logo"
                                            hint="Shown at the foot of the home page. Without one the name is set in type instead."
                                            error={errors.logo}
                                        >
                                            <Input
                                                id="logo"
                                                name="logo"
                                                type="file"
                                                accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                            />
                                        </Field>
                                    </div>
                                </section>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {editing
                                        ? 'Save changes'
                                        : 'Add the sponsor'}
                                </Button>

                                {editing && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="w-full text-brand-red hover:text-brand-red"
                                        onClick={() => {
                                            if (
                                                window.confirm(
                                                    `Take ${sponsor.name} off the sponsor board?`,
                                                )
                                            ) {
                                                router.delete(
                                                    admin.sponsors.destroy(
                                                        sponsor.slug,
                                                    ).url,
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 className="size-4" />
                                        Remove this sponsor
                                    </Button>
                                )}
                            </aside>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
