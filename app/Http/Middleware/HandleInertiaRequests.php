<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(private readonly OrganizationContext $context) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'permissions' => $user === null ? [] : $this->context->permissionsFor($user),
                'roles' => $user === null ? [] : $this->context->rolesFor($user),
            ],
            'organization' => $this->currentOrganization(),
            'federation' => $this->federation(),
            'switchableOrganizations' => $user === null
                ? []
                : $this->context->switchableFor($user)->map($this->summarise(...))->values()->all(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * How the public reaches the national body.
     *
     * Read from the national organisation's own row rather than from config,
     * so the federation can change the number on the organisations screen
     * without a deployment. A missing number is a null, not an empty string:
     * the WhatsApp button checks for it and stays off the page.
     *
     * @return array{name: string, email: string|null, phone: string|null, whatsapp: string|null}
     */
    private function federation(): array
    {
        $national = Organization::query()
            ->where('type', OrganizationType::National)
            ->first(['id', 'name', 'email', 'phone']);

        // A database that has not been seeded yet has no national body, and
        // the public site still has to render.
        if ($national === null) {
            return [
                'name' => (string) config('app.name'),
                'email' => null,
                'phone' => null,
                'whatsapp' => null,
            ];
        }

        return [
            'name' => $national->name,
            'email' => $national->email,
            'phone' => $national->phone,
            'whatsapp' => $national->whatsappNumber(),
        ];
    }

    /**
     * @return array{id: int, name: string, slug: string, type: string, type_label: string}|null
     */
    private function currentOrganization(): ?array
    {
        $organization = $this->context->current();

        return $organization === null ? null : $this->summarise($organization);
    }

    /**
     * @return array{id: int, name: string, slug: string, type: string, type_label: string}
     */
    private function summarise(Organization $organization): array
    {
        return [
            'id' => $organization->getKey(),
            'name' => $organization->name,
            'slug' => $organization->slug,
            'type' => $organization->type->value,
            'type_label' => $organization->type->label(),
        ];
    }
}
