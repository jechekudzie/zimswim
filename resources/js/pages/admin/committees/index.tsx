import { Form, Head, router } from '@inertiajs/react';
import { Plus, Trash2, Users } from 'lucide-react';
import { useState } from 'react';

import { EmptyState } from '@/components/admin/empty-state';
import {
    CheckboxField,
    RichTextField,
    SelectField,
    TextField,
} from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type Member = {
    id: number;
    person_name: string;
    role: string;
    email: string | null;
    phone: string | null;
    position: number;
    is_published: boolean;
};

type Committee = {
    id: number;
    name: string;
    kind: string;
    description: string | null;
    position: number;
    members: Member[];
};

export default function CommitteesIndex({
    organization,
    committees,
    kinds,
}: {
    organization: string | null;
    committees: Committee[];
    kinds: Option[];
}) {
    useFlashMessages();

    const [adding, setAdding] = useState(false);
    const [editing, setEditing] = useState<number | null>(null);

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Committees', href: admin.committees.index().url },
            ]}
        >
            <Head title="Committees" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Committees"
                    description={
                        organization
                            ? `Office bearers for ${organization}, as they appear on the About page. A committee belongs to the body that elected it, so this is ${organization}'s own list.`
                            : 'Office bearers as they appear on the About page.'
                    }
                    actions={
                        <Button onClick={() => setAdding(!adding)}>
                            <Plus className="size-4" />
                            Add a committee
                        </Button>
                    }
                />

                {adding && (
                    <section className="rounded-xl border border-border bg-card p-6">
                        <h2 className="font-semibold">A new committee</h2>
                        <Form
                            {...admin.committees.store.form()}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setAdding(false)}
                            className="mt-5 grid gap-5 lg:grid-cols-2"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <TextField
                                        label="Name"
                                        name="name"
                                        required
                                        error={errors.name}
                                    />
                                    <SelectField
                                        label="Kind"
                                        name="kind"
                                        options={kinds}
                                        defaultValue="committee"
                                        error={errors.kind}
                                    />
                                    <div className="lg:col-span-2">
                                        <RichTextField
                                            label="What it does"
                                            name="description"
                                            error={errors.description}
                                        />
                                    </div>
                                    <TextField
                                        label="Order"
                                        name="position"
                                        type="number"
                                        defaultValue={0}
                                        hint="Lower numbers come first on the About page."
                                        error={errors.position}
                                    />
                                    <div className="flex items-end gap-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Add the committee
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() => setAdding(false)}
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </section>
                )}

                {committees.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title="No committees yet"
                        description="Add the executive and any standing committees, and the people on them will appear on the About page."
                    />
                ) : (
                    committees.map((committee) => (
                        <section
                            key={committee.id}
                            className="rounded-xl border border-border bg-card"
                        >
                            <header className="flex flex-wrap items-center justify-between gap-3 border-b border-border/60 px-6 py-4">
                                <div>
                                    <h2 className="flex items-center gap-2 font-semibold">
                                        {committee.name}
                                        <Badge
                                            variant={
                                                committee.kind === 'executive'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {committee.kind === 'executive'
                                                ? 'Executive'
                                                : 'Standing committee'}
                                        </Badge>
                                    </h2>
                                    <p className="text-xs text-muted-foreground">
                                        {committee.members.length} member
                                        {committee.members.length === 1
                                            ? ''
                                            : 's'}
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            setEditing(
                                                editing === committee.id
                                                    ? null
                                                    : committee.id,
                                            )
                                        }
                                    >
                                        {editing === committee.id
                                            ? 'Done'
                                            : 'Edit details'}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        className="text-brand-red hover:text-brand-red"
                                        onClick={() => {
                                            if (
                                                window.confirm(
                                                    `Remove ${committee.name} and everybody on it?`,
                                                )
                                            ) {
                                                router.delete(
                                                    admin.committees.destroy(
                                                        committee.id,
                                                    ).url,
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </header>

                            {editing === committee.id && (
                                <Form
                                    {...admin.committees.update.form(
                                        committee.id,
                                    )}
                                    options={{ preserveScroll: true }}
                                    className="grid gap-5 border-b border-border/60 bg-muted/30 p-6 lg:grid-cols-2"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <TextField
                                                label="Name"
                                                name="name"
                                                defaultValue={committee.name}
                                                required
                                                error={errors.name}
                                            />
                                            <SelectField
                                                label="Kind"
                                                name="kind"
                                                options={kinds}
                                                defaultValue={committee.kind}
                                                error={errors.kind}
                                            />
                                            <div className="lg:col-span-2">
                                                <RichTextField
                                                    label="What it does"
                                                    name="description"
                                                    defaultValue={
                                                        committee.description
                                                    }
                                                    error={errors.description}
                                                />
                                            </div>
                                            <TextField
                                                label="Order"
                                                name="position"
                                                type="number"
                                                defaultValue={
                                                    committee.position
                                                }
                                                error={errors.position}
                                            />
                                            <div className="flex items-end">
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    Save changes
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            )}

                            <div className="p-6">
                                {committee.members.length > 0 && (
                                    <div className="overflow-x-auto rounded-lg border border-border">
                                        <table className="w-full min-w-[640px] text-sm">
                                            <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                                <tr>
                                                    <th className="px-4 py-2.5 font-medium">
                                                        Name
                                                    </th>
                                                    <th className="px-4 py-2.5 font-medium">
                                                        Office
                                                    </th>
                                                    <th className="px-4 py-2.5 font-medium">
                                                        Contact
                                                    </th>
                                                    <th className="px-4 py-2.5 font-medium">
                                                        Shown
                                                    </th>
                                                    <th className="px-4 py-2.5" />
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {committee.members.map(
                                                    (member) => (
                                                        <tr
                                                            key={member.id}
                                                            className="border-b border-border/60 last:border-0"
                                                        >
                                                            <td className="px-4 py-2.5 font-medium">
                                                                {
                                                                    member.person_name
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2.5 text-muted-foreground">
                                                                {member.role}
                                                            </td>
                                                            <td className="px-4 py-2.5 text-xs text-muted-foreground">
                                                                {member.email ??
                                                                    '—'}
                                                                {member.phone && (
                                                                    <span className="block">
                                                                        {
                                                                            member.phone
                                                                        }
                                                                    </span>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-2.5">
                                                                {member.is_published ? (
                                                                    <Badge variant="secondary">
                                                                        On the
                                                                        site
                                                                    </Badge>
                                                                ) : (
                                                                    <Badge variant="outline">
                                                                        Hidden
                                                                    </Badge>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-2.5 text-right">
                                                                <Button
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    className="text-brand-red hover:text-brand-red"
                                                                    onClick={() => {
                                                                        if (
                                                                            window.confirm(
                                                                                `Remove ${member.person_name} from ${committee.name}?`,
                                                                            )
                                                                        ) {
                                                                            router.delete(
                                                                                admin.committees.members.destroy(
                                                                                    [
                                                                                        committee.id,
                                                                                        member.id,
                                                                                    ],
                                                                                )
                                                                                    .url,
                                                                                {
                                                                                    preserveScroll: true,
                                                                                },
                                                                            );
                                                                        }
                                                                    }}
                                                                >
                                                                    <Trash2 className="size-4" />
                                                                </Button>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                )}

                                <Form
                                    {...admin.committees.members.store.form(
                                        committee.id,
                                    )}
                                    options={{ preserveScroll: true }}
                                    resetOnSuccess
                                    className="mt-5 grid items-end gap-4 border-t border-border pt-5 sm:grid-cols-2 lg:grid-cols-5"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`name-${committee.id}`}
                                                >
                                                    Name
                                                </Label>
                                                <Input
                                                    id={`name-${committee.id}`}
                                                    name="person_name"
                                                    required
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`role-${committee.id}`}
                                                >
                                                    Office
                                                </Label>
                                                <Input
                                                    id={`role-${committee.id}`}
                                                    name="role"
                                                    placeholder="Chairperson"
                                                    required
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`email-${committee.id}`}
                                                >
                                                    Email
                                                </Label>
                                                <Input
                                                    id={`email-${committee.id}`}
                                                    name="email"
                                                    type="email"
                                                />
                                            </div>
                                            <CheckboxField
                                                label="Show on the About page"
                                                name="is_published"
                                                defaultChecked
                                            />
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                disabled={processing}
                                            >
                                                <Plus className="size-4" />
                                                Add
                                            </Button>
                                            {(errors.person_name ||
                                                errors.role ||
                                                errors.email) && (
                                                <p className="text-xs text-destructive lg:col-span-5">
                                                    {errors.person_name ??
                                                        errors.role ??
                                                        errors.email}
                                                </p>
                                            )}
                                        </>
                                    )}
                                </Form>
                            </div>
                        </section>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
