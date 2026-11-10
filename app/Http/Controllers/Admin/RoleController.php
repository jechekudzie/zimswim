<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles are configurable by the system administrator; what each access level
 * may do is edited here rather than in code.
 */
class RoleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('manageRoles', User::class);

        setPermissionsTeamId(null);

        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->getKey(),
                'name' => $role->name,
                'label' => Roles::label($role->name),
                'description' => Roles::definitions()[$role->name]['description'] ?? null,
                'permissions' => $role->permissions->pluck('name')->all(),
                'users_count' => $role->users()->count(),
            ]);

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
            'permissionGroups' => collect(Permissions::grouped())
                ->map(fn (array $permissions, string $group): array => [
                    'group' => $group,
                    'permissions' => collect($permissions)
                        ->map(fn (string $label, string $name): array => ['name' => $name, 'label' => $label])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('manageRoles', User::class);

        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', Permissions::all())],
        ]);

        setPermissionsTeamId(null);
        $role->syncPermissions($validated['permissions']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', Roles::label($role->name).' permissions updated.');
    }
}
