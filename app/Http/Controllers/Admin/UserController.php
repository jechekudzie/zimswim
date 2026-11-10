<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Support\Csv;
use App\Support\OrganizationContext;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function __construct(private readonly OrganizationContext $context) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $scopeIds = $this->context->scopeIds();

        $users = $this->filtered($request, $scopeIds)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->getKey(),
                'name' => $user->displayName(),
                'email' => $user->email,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at?->toDateString(),
                'organizations' => $user->organizations->map(fn (Organization $organization): array => [
                    'id' => $organization->getKey(),
                    'name' => $organization->name,
                ]),
                'roles' => $this->rolesForUser($user, $scopeIds),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => ['search' => $request->string('search')->toString() ?: null],
            'roles' => $this->roleOptions(),
            'organizations' => $this->organizationOptions(),
            'can' => ['manage' => $request->user()->can('create', User::class)],
        ]);
    }

    /**
     * The account list as a spreadsheet, narrowed exactly as the page is.
     *
     * Roles are reported only for the organisations the exporter can
     * already see, which is the same scope the page is drawn against.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $scopeIds = $this->context->scopeIds();

        return Csv::download('users', [
            'Name',
            'Email',
            'Active',
            'Last signed in',
            'Organisations',
            'Roles',
        ], $this->filtered($request, $scopeIds)->lazy()->map(fn (User $user): array => [
            $user->displayName(),
            $user->email,
            $user->is_active,
            $user->last_login_at?->toDateString(),
            $user->organizations->pluck('name')->implode('; '),
            collect($this->rolesForUser($user, $scopeIds))
                ->map(fn (array $role): string => sprintf('%s at %s', $role['label'], $role['organization']))
                ->implode('; '),
        ]));
    }

    /**
     * @param  array<int, int>  $scopeIds
     * @return Builder<User>
     */
    private function filtered(Request $request, array $scopeIds): Builder
    {
        return User::query()
            ->whereHas('organizations', fn ($query) => $query->whereIn('organizations.id', $scopeIds))
            ->when($request->string('search')->toString(), function ($query, $search): void {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $query->where(fn ($inner) => $inner->where('name', 'ilike', $like)->orWhere('email', 'ilike', $like));
            })
            ->with('organizations:id,name,type')
            ->orderBy('name');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'organization_id' => ['required', 'integer', Rule::in($this->context->scopeIds())],
            'role' => ['required', 'string', Rule::in(array_keys(Roles::definitions()))],
        ]);

        $organization = Organization::query()->findOrFail((int) $validated['organization_id']);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make(Str::password(16)),
                'current_organization_id' => $validated['organization_id'],
                'is_active' => true,
            ]);

            $user->organizations()->attach($validated['organization_id'], ['is_default' => true]);

            setPermissionsTeamId($validated['organization_id']);
            $user->assignRole($validated['role']);

            return $user;
        });

        // The account carries a password nobody knows, so the invitation is
        // the only thing that makes it usable. It is sent after the
        // transaction commits so a rolled-back create cannot post one.
        $user->notify(new AccountCreated(
            $organization->name,
            Roles::label($validated['role']),
        ));

        return back()->with(
            'success',
            $user->name.' has been added as '.Roles::label($validated['role']).'. An invitation to set a password is on its way to '.$user->email.'.',
        );
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_active' => ['boolean'],
        ]);

        $user->update($validated);

        return back()->with('success', $user->name.' has been updated.');
    }

    /**
     * Grants a role to a user inside one organisation. The organisation decides
     * the reach of the role, so both are set together.
     */
    public function assignRole(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manageRoles', User::class);

        $validated = $request->validate([
            'organization_id' => ['required', 'integer', Rule::in($this->context->scopeIds())],
            'role' => ['required', 'string', Rule::in(array_keys(Roles::definitions()))],
        ]);

        $user->organizations()->syncWithoutDetaching([$validated['organization_id']]);

        setPermissionsTeamId($validated['organization_id']);
        $user->unsetRelation('roles');

        if (! $user->hasRole($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        return back()->with('success', Roles::label($validated['role']).' granted to '.$user->displayName().'.');
    }

    public function revokeRole(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manageRoles', User::class);

        $validated = $request->validate([
            'organization_id' => ['required', 'integer', Rule::in($this->context->scopeIds())],
            'role' => ['required', 'string'],
        ]);

        setPermissionsTeamId($validated['organization_id']);
        $user->unsetRelation('roles');
        $user->removeRole($validated['role']);

        return back()->with('success', 'Role removed.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return back()->with('success', $user->displayName().' has been deactivated.');
    }

    /**
     * @param  array<int, int>  $scopeIds
     * @return array<int, array{role: string, label: string, organization: string, organization_id: int}>
     */
    private function rolesForUser(User $user, array $scopeIds): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('organizations', 'organizations.id', '=', 'model_has_roles.organization_id')
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('model_has_roles.model_id', $user->getKey())
            ->whereIn('model_has_roles.organization_id', $scopeIds)
            ->get(['roles.name as role', 'organizations.name as organization', 'organizations.id as organization_id'])
            ->map(fn ($row): array => [
                'role' => $row->role,
                'label' => Roles::label($row->role),
                'organization' => $row->organization,
                'organization_id' => (int) $row->organization_id,
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, description: string}>
     */
    private function roleOptions(): array
    {
        return array_map(
            fn (string $name): array => [
                'value' => $name,
                'label' => Roles::definitions()[$name]['label'],
                'description' => Roles::definitions()[$name]['description'],
            ],
            array_keys(Roles::definitions()),
        );
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function organizationOptions(): array
    {
        return Organization::query()
            ->whereIn('id', $this->context->scopeIds())
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn (Organization $organization): array => [
                'value' => $organization->getKey(),
                'label' => $organization->name.' ('.$organization->type->label().')',
            ])
            ->all();
    }
}
