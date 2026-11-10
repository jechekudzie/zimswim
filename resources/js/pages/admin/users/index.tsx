import { Form, Head, router } from '@inertiajs/react';
import { Users, X } from 'lucide-react';

import { SelectField, TextField } from '@/components/admin/form-field';
import { EmptyState } from '@/components/admin/empty-state';
import { ExportButton } from '@/components/admin/export-button';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import { users as exportUsers } from '@/routes/admin/exports';
import type { Option, Paginated } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    last_login_at: string | null;
    organizations: { id: number; name: string }[];
    roles: {
        role: string;
        label: string;
        organization: string;
        organization_id: number;
    }[];
};

export default function UsersIndex({
    users,
    filters,
    roles,
    organizations,
    can,
}: {
    users: Paginated<UserRow>;
    filters: { search: string | null };
    roles: (Option & { description: string })[];
    organizations: Option<number>[];
    can: { manage: boolean };
}) {
    useFlashMessages();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Users', href: admin.users.index().url },
            ]}
        >
            <Head title="Users" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Users and access"
                    description="A role is granted inside one organisation. The organisation decides how far the role reaches."
                    actions={
                        <ExportButton
                            url={exportUsers().url}
                            params={{ search: filters.search }}
                        />
                    }
                />

                <div className="grid gap-6 xl:grid-cols-3">
                    <div className="xl:col-span-2">
                        {users.data.length === 0 ? (
                            <EmptyState
                                icon={Users}
                                title="No users yet"
                                description="Add the board, provincial and club contacts so they can log in."
                            />
                        ) : (
                            <>
                                <div className="overflow-x-auto rounded-xl border border-border">
                                    <table className="w-full min-w-[720px] text-sm">
                                        <thead className="border-b border-border bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                            <tr>
                                                <th className="px-4 py-3 font-medium">
                                                    User
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Roles
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Last login
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {users.data.map((user) => (
                                                <tr
                                                    key={user.id}
                                                    className="border-b border-border/60 bg-card last:border-0"
                                                >
                                                    <td className="px-4 py-3">
                                                        <p className="font-medium">
                                                            {user.name}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {user.email}
                                                        </p>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {user.roles.length ===
                                                        0 ? (
                                                            <span className="text-xs text-muted-foreground">
                                                                No role here
                                                            </span>
                                                        ) : (
                                                            <div className="flex flex-wrap gap-1.5">
                                                                {user.roles.map(
                                                                    (role) => (
                                                                        <Badge
                                                                            key={`${role.role}-${role.organization_id}`}
                                                                            variant="secondary"
                                                                            className="gap-1"
                                                                        >
                                                                            {
                                                                                role.label
                                                                            }
                                                                            <span className="text-muted-foreground">
                                                                                ·{' '}
                                                                                {
                                                                                    role.organization
                                                                                }
                                                                            </span>
                                                                            {can.manage && (
                                                                                <button
                                                                                    type="button"
                                                                                    aria-label={`Revoke ${role.label}`}
                                                                                    onClick={() =>
                                                                                        router.delete(
                                                                                            admin.users.roles.revoke(
                                                                                                user.id,
                                                                                            )
                                                                                                .url,
                                                                                            {
                                                                                                data: {
                                                                                                    role: role.role,
                                                                                                    organization_id:
                                                                                                        role.organization_id,
                                                                                                },
                                                                                                preserveScroll: true,
                                                                                            },
                                                                                        )
                                                                                    }
                                                                                >
                                                                                    <X className="size-3" />
                                                                                </button>
                                                                            )}
                                                                        </Badge>
                                                                    ),
                                                                )}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-muted-foreground tabular-nums">
                                                        {user.last_login_at ??
                                                            'Never'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <Pagination meta={users} />
                            </>
                        )}
                    </div>

                    {can.manage && (
                        <section className="h-fit rounded-xl border border-border bg-card p-6">
                            <h2 className="font-semibold">Add a user</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                They set their own password through the
                                forgotten password link.
                            </p>

                            <Form
                                {...admin.users.store.form()}
                                className="mt-5 space-y-4"
                                resetOnSuccess
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <TextField
                                            label="Full name"
                                            name="name"
                                            error={errors.name}
                                            required
                                        />
                                        <TextField
                                            label="Email"
                                            name="email"
                                            type="email"
                                            error={errors.email}
                                            required
                                        />
                                        <TextField
                                            label="Phone"
                                            name="phone"
                                            error={errors.phone}
                                        />
                                        <SelectField
                                            label="Organisation"
                                            name="organization_id"
                                            options={organizations}
                                            defaultValue={
                                                organizations[0]?.value
                                            }
                                            error={errors.organization_id}
                                        />
                                        <SelectField
                                            label="Role"
                                            name="role"
                                            options={roles}
                                            defaultValue={roles[0]?.value}
                                            error={errors.role}
                                        />
                                        <Button
                                            type="submit"
                                            className="w-full"
                                            disabled={processing}
                                        >
                                            Add user
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
