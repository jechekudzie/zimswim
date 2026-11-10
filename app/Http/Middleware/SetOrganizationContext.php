<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the organisation the signed-in user is acting within and hands it to
 * both the context object and the permission package's team scope.
 */
class SetOrganizationContext
{
    public function __construct(private readonly OrganizationContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $organization = $this->resolve($request, $user->current_organization_id)
            ?? $user->activeOrganization();

        if ($organization !== null) {
            $this->context->set($organization);
            setPermissionsTeamId($organization->getKey());
        }

        return $next($request);
    }

    private function resolve(Request $request, ?int $organizationId): ?Organization
    {
        $sessionId = $request->session()->get('organization_id', $organizationId);

        if ($sessionId === null) {
            return null;
        }

        $organization = Organization::query()->find((int) $sessionId);

        if ($organization === null || ! $request->user()->reachableOrganizations()->contains('id', $organization->getKey())) {
            $request->session()->forget('organization_id');

            return null;
        }

        return $organization;
    }
}
