import { Form, Head } from '@inertiajs/react';

import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';

type Role = {
    id: number;
    name: string;
    label: string;
    description: string | null;
    permissions: string[];
    users_count: number;
};

type PermissionGroup = {
    group: string;
    permissions: { name: string; label: string }[];
};

export default function RolesIndex({
    roles,
    permissionGroups,
}: {
    roles: Role[];
    permissionGroups: PermissionGroup[];
}) {
    useFlashMessages();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Roles', href: admin.roles.index().url },
            ]}
        >
            <Head title="Roles" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Roles and permissions"
                    description="These are the access levels the platform ships with. What each one may do is edited here; where it applies is decided by the organisation the role is granted in."
                />

                <div className="space-y-6">
                    {roles.map((role) => (
                        <section
                            key={role.id}
                            className="rounded-xl border border-border bg-card p-6"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 className="font-semibold">
                                        {role.label}
                                    </h2>
                                    {role.description && (
                                        <p className="mt-1 max-w-3xl text-sm text-muted-foreground">
                                            {role.description}
                                        </p>
                                    )}
                                </div>
                                <Badge variant="secondary">
                                    {role.users_count}{' '}
                                    {role.users_count === 1 ? 'user' : 'users'}
                                </Badge>
                            </div>

                            <Form
                                {...admin.roles.update.form(role.id)}
                                className="mt-6"
                            >
                                {({ processing }) => (
                                    <>
                                        <input
                                            type="hidden"
                                            name="permissions[]"
                                            value=""
                                        />
                                        <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                                            {permissionGroups.map((group) => (
                                                <div key={group.group}>
                                                    <h3 className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                                        {group.group}
                                                    </h3>
                                                    <div className="mt-3 space-y-2.5">
                                                        {group.permissions.map(
                                                            (permission) => (
                                                                <label
                                                                    key={
                                                                        permission.name
                                                                    }
                                                                    className="flex items-start gap-2.5"
                                                                >
                                                                    <Checkbox
                                                                        name="permissions[]"
                                                                        value={
                                                                            permission.name
                                                                        }
                                                                        defaultChecked={role.permissions.includes(
                                                                            permission.name,
                                                                        )}
                                                                    />
                                                                    <span className="text-sm">
                                                                        {
                                                                            permission.label
                                                                        }
                                                                    </span>
                                                                </label>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>

                                        <Button
                                            type="submit"
                                            size="sm"
                                            className="mt-6"
                                            disabled={processing}
                                        >
                                            Save {role.label} permissions
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
