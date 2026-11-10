<?php

namespace App\Policies\Concerns;

use App\Support\OrganizationContext;

/**
 * Policies combine two checks: the user must hold the permission in the
 * organisation they are acting in, and the record must belong to that
 * organisation or one beneath it.
 */
trait ScopesToOrganization
{
    protected function context(): OrganizationContext
    {
        return app(OrganizationContext::class);
    }

    /**
     * @return array<int, int>
     */
    protected function scopeIds(): array
    {
        return $this->context()->scopeIds();
    }

    protected function inScope(?int $organizationId): bool
    {
        return $organizationId !== null && in_array($organizationId, $this->scopeIds(), true);
    }
}
