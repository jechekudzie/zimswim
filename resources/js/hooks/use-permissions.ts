import { usePage } from '@inertiajs/react';

import type { OrganizationSummary } from '@/types';

type SharedAuth = {
    permissions?: string[];
    roles?: string[];
};

/**
 * Permissions come from the roles the signed-in user holds at the organisation
 * they are working in and at every organisation above it, so the navigation
 * shows exactly what the backend will allow.
 */
export function usePermissions() {
    const page = usePage<{
        auth: SharedAuth;
        organization: OrganizationSummary | null;
        switchableOrganizations: OrganizationSummary[];
    }>();

    const permissions = page.props.auth?.permissions ?? [];
    const roles = page.props.auth?.roles ?? [];

    return {
        permissions,
        roles,
        organization: page.props.organization ?? null,
        organizations: page.props.switchableOrganizations ?? [],
        can: (permission: string) => permissions.includes(permission),
        canAny: (...wanted: string[]) =>
            wanted.some((permission) => permissions.includes(permission)),
        hasRole: (role: string) => roles.includes(role),
    };
}
