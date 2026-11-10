<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
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
                /*
                 * Whether this person is on the officials register, which is
                 * not a permission: an official's portal is theirs because
                 * of who they are, not because of what they may do.
                 */
                'is_official' => $user !== null
                    && TechnicalOfficial::where('user_id', $user->getKey())->exists(),
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
            'notifications' => $user === null ? null : $this->notifications($user),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * What the bell in the backend header shows.
     *
     * The unread tally is read on every request, which is why the
     * notifications table carries an index for exactly that question. The
     * list is capped: a bell is a prompt to go and deal with something, not
     * an archive, and a person with four hundred unread notices does not
     * want them all parsed into every page load.
     *
     * @return array{unread: int, items: array<int, array<string, mixed>>}
     */
    private function notifications(User $user): array
    {
        $items = $user->notifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => [
                'id' => $notification->getKey(),
                'kind' => $notification->data['kind'] ?? 'notice',
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? null,
                'url' => $notification->data['url'] ?? null,
                'read' => $notification->read_at !== null,
                'sent_at' => $notification->created_at?->diffForHumans(),
            ])
            ->all();

        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => $items,
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
