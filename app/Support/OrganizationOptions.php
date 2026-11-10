<?php

namespace App\Support;

use App\Enums\OrganizationType;
use App\Models\Organization;

/**
 * The province and club lists the admin listings filter by.
 *
 * Built once here rather than in each controller so every screen offers the
 * same bodies in the same order, and so a club secretary is never shown a
 * province filter covering organisations they cannot see.
 */
class OrganizationOptions
{
    public function __construct(private readonly OrganizationContext $context) {}

    /**
     * Provinces inside the signed-in user's scope.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function provinces(): array
    {
        return Organization::query()
            ->whereIn('id', $this->context->scopeIds())
            ->ofType(OrganizationType::Province)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Organization $province): array => [
                'value' => $province->getKey(),
                'label' => $province->name,
            ])
            ->all();
    }

    /**
     * Clubs inside scope, each carrying the province it belongs to so the
     * club list can be narrowed in the browser when a province is picked,
     * rather than costing a round trip for a list of nineteen.
     *
     * @return array<int, array{value: int, label: string, province_id: int|null}>
     */
    public function clubs(): array
    {
        return Organization::query()
            ->whereIn('id', $this->context->scopeIds())
            ->ofType(OrganizationType::Club)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->map(fn (Organization $club): array => [
                'value' => $club->getKey(),
                'label' => $club->name,
                'province_id' => $club->parent_id,
            ])
            ->all();
    }

    /**
     * Both lists, for a listing that filters by either.
     *
     * @return array{provinces: array<int, array{value: int, label: string}>, clubs: array<int, array{value: int, label: string, province_id: int|null}>}
     */
    public function all(): array
    {
        return ['provinces' => $this->provinces(), 'clubs' => $this->clubs()];
    }
}
