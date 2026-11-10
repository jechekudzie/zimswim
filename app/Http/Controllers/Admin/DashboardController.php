<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GalaStatus;
use App\Enums\MemberStatus;
use App\Enums\OfficialCategory;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\Member;
use App\Models\OfficialAccreditation;
use App\Models\Organization;
use App\Models\Season;
use App\Models\SwimRecord;
use App\Models\TechnicalOfficial;
use App\Support\OrganizationContext;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The board dashboard, scoped to whatever organisation the user is acting in:
 * the whole country for the national body, one province for a provincial
 * administrator, one club for a club secretary.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly OrganizationContext $context) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $scopeIds = $this->context->scopeIds();
        $season = Season::current();
        $organization = $this->context->current();

        return Inertia::render('admin/dashboard', [
            'season' => $this->seasonSummary($season),
            'summary' => [
                'swimmers' => Member::query()->whereIn('organization_id', $scopeIds)->where('status', MemberStatus::Active)->count(),
                'pendingSwimmers' => Member::query()->whereIn('organization_id', $scopeIds)->where('status', MemberStatus::Pending)->count(),
                'clubs' => Organization::query()->whereIn('id', $scopeIds)->where('type', OrganizationType::Club)->count(),
                'provinces' => Organization::query()->whereIn('id', $scopeIds)->where('type', OrganizationType::Province)->count(),
                'officials' => TechnicalOfficial::query()->whereIn('organization_id', $scopeIds)->where('status', 'active')->count(),
                'upcomingGalas' => Gala::query()->forOrganizations($scopeIds)->upcoming()->count(),
            ],
            'swimmersByAgeGroup' => $this->swimmersByAgeGroup($scopeIds, $season),
            'swimmersByGender' => $this->swimmersByGender($scopeIds),
            'membersByStatus' => $this->membersByStatus($scopeIds),
            'galasByMonth' => $this->galasByMonth($scopeIds, $season),
            'officialsByCategory' => $this->officialsByCategory($scopeIds),
            'currentMonth' => now()->format('M'),
            'clubsByProvince' => $this->clubsByProvince($scopeIds),
            'awaitingSanction' => $user->can(Permissions::SANCTION_GALAS)
                ? Gala::query()
                    ->where('status', GalaStatus::Submitted)
                    ->with('host:id,name')
                    ->orderBy('starts_on')
                    ->limit(5)
                    ->get()
                    ->map(fn (Gala $gala): array => [
                        'name' => $gala->name,
                        'slug' => $gala->slug,
                        'host' => $gala->host->name,
                        'starts_on' => $gala->starts_on->toDateString(),
                        'level' => $gala->level->label(),
                    ])
                : [],
            'awaitingRatification' => $user->can(Permissions::RATIFY_RECORDS)
                ? SwimRecord::query()
                    ->awaitingRatification()
                    ->orderByDesc('set_on')
                    ->limit(5)
                    ->get()
                    ->map(fn (SwimRecord $record): array => [
                        'id' => $record->getKey(),
                        'event' => $record->eventTitle(),
                        'age_group' => $record->age_group,
                        'holder' => $record->holder_name,
                        'time' => $record->formattedTime(),
                        'set_on' => $record->set_on?->toDateString(),
                    ])
                : [],
            'upcoming' => Gala::query()
                ->forOrganizations($scopeIds)
                ->upcoming()
                ->with('venue:id,name,city')
                ->limit(5)
                ->get()
                ->map(fn (Gala $gala): array => [
                    'name' => $gala->name,
                    'slug' => $gala->slug,
                    'starts_on' => $gala->starts_on->toDateString(),
                    'status' => $gala->status->label(),
                    'level' => $gala->level->label(),
                    'venue' => $gala->venue?->name,
                ]),
            'organizationName' => $organization?->name,
        ]);
    }

    /**
     * The split between girls and boys on the register.
     *
     * Only the two the register records; a swimmer whose gender is not set
     * does not appear, because an unlabelled slice on a chart tells nobody
     * anything.
     *
     * @param  array<int, int>  $scopeIds
     * @return array<int, array{label: string, value: int}>
     */
    private function swimmersByGender(array $scopeIds): array
    {
        $counts = Member::query()
            ->whereIn('organization_id', $scopeIds)
            ->where('status', MemberStatus::Active)
            ->selectRaw('gender, count(*) as total')
            ->groupBy('gender')
            ->pluck('total', 'gender');

        return [
            ['label' => 'Girls', 'value' => (int) ($counts['female'] ?? 0)],
            ['label' => 'Boys', 'value' => (int) ($counts['male'] ?? 0)],
        ];
    }

    /**
     * The register by the state each record is in.
     *
     * The tone is what the colour comes from on the page: a lapsed
     * registration is a problem to work through, a suspended one is a
     * problem now, and those should not look the same.
     *
     * @param  array<int, int>  $scopeIds
     * @return array<int, array{label: string, value: int, tone: string}>
     */
    private function membersByStatus(array $scopeIds): array
    {
        $counts = Member::query()
            ->whereIn('organization_id', $scopeIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $tones = [
            MemberStatus::Active->value => 'good',
            MemberStatus::Pending->value => 'warn',
            MemberStatus::Lapsed->value => 'serious',
            MemberStatus::Suspended->value => 'critical',
            MemberStatus::Transferred->value => 'neutral',
        ];

        return array_map(fn (MemberStatus $status): array => [
            'label' => $status->label(),
            'value' => (int) ($counts[$status->value] ?? 0),
            'tone' => $tones[$status->value],
        ], MemberStatus::cases());
    }

    /**
     * Galas across the months of the season.
     *
     * Every month is present even when it is empty, because the gaps are the
     * point: a calendar with nothing in it from March to June is something
     * the board should see.
     *
     * @param  array<int, int>  $scopeIds
     * @return array<int, array{label: string, value: int}>
     */
    private function galasByMonth(array $scopeIds, ?Season $season): array
    {
        if ($season === null) {
            return [];
        }

        $counts = Gala::query()
            ->forOrganizations($scopeIds)
            ->whereBetween('starts_on', [$season->starts_on, $season->ends_on])
            ->get(['starts_on'])
            ->countBy(fn (Gala $gala): string => $gala->starts_on->format('Y-m'));

        $months = [];
        $cursor = $season->starts_on->copy()->startOfMonth();
        $end = $season->ends_on->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end) && count($months) < 18) {
            $months[] = [
                'label' => $cursor->format('M'),
                'value' => (int) ($counts[$cursor->format('Y-m')] ?? 0),
            ];

            $cursor = $cursor->addMonth();
        }

        return $months;
    }

    /**
     * Accredited officials by the role they are qualified for.
     *
     * Every category is returned, including the ones nobody holds, because a
     * missing role is the finding: a gala cannot be started without a
     * referee and a starter, so those two are flagged as required and a zero
     * against either is a blocked calendar rather than a gap in a chart. The
     * order is the certification ladder's own, from timekeeper up.
     *
     * @param  array<int, int>  $scopeIds
     * @return array<int, array{label: string, value: int, required: bool}>
     */
    private function officialsByCategory(array $scopeIds): array
    {
        $counts = OfficialAccreditation::query()
            ->where('status', 'active')
            ->whereHas('technicalOfficial', fn ($query) => $query->whereIn('organization_id', $scopeIds))
            ->get(['category'])
            ->countBy(fn (OfficialAccreditation $accreditation): string => $accreditation->category->value);

        $required = [OfficialCategory::Referee, OfficialCategory::Starter];

        return collect(OfficialCategory::cases())
            ->map(fn (OfficialCategory $category): array => [
                'label' => $category->label(),
                'value' => (int) ($counts[$category->value] ?? 0),
                'required' => in_array($category, $required, true),
            ])
            ->all();
    }

    /**
     * Clubs under each province in scope.
     *
     * Only worth drawing when more than one province is in view, which is
     * the national body's dashboard and nobody else's.
     *
     * @param  array<int, int>  $scopeIds
     * @return array<int, array{label: string, value: int}>
     */
    private function clubsByProvince(array $scopeIds): array
    {
        $provinces = Organization::query()
            ->whereIn('id', $scopeIds)
            ->where('type', OrganizationType::Province)
            ->withCount(['children as clubs_count' => fn ($query) => $query->where('type', OrganizationType::Club)])
            ->orderByDesc('clubs_count')
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($provinces->count() < 2) {
            return [];
        }

        return $provinces
            ->map(fn (Organization $province): array => [
                'label' => $province->name,
                'value' => (int) $province->clubs_count,
            ])
            ->all();
    }

    /**
     * Seasons carry dates, not timestamps, so they are rendered as plain dates.
     *
     * @return array{name: string, starts_on: string, ends_on: string, age_up_date: string}|null
     */
    private function seasonSummary(?Season $season): ?array
    {
        return $season === null ? null : [
            'name' => $season->name,
            'starts_on' => $season->starts_on->toDateString(),
            'ends_on' => $season->ends_on->toDateString(),
            'age_up_date' => $season->age_up_date->toDateString(),
        ];
    }

    /**
     * Swimmers grouped by the age they will be at the season's age-up date,
     * which is the only grouping the handbook recognises.
     *
     * @param  array<int, int>  $scopeIds
     * @return array<int, array{age_group: string, count: int}>
     */
    private function swimmersByAgeGroup(array $scopeIds, ?Season $season): array
    {
        if ($season === null || $scopeIds === []) {
            return [];
        }

        $members = Member::query()
            ->whereIn('organization_id', $scopeIds)
            ->where('status', MemberStatus::Active)
            ->get(['date_of_birth']);

        $bands = [
            '10 and under' => fn (int $age): bool => $age <= 10,
            '11 to 12' => fn (int $age): bool => $age >= 11 && $age <= 12,
            '13 to 14' => fn (int $age): bool => $age >= 13 && $age <= 14,
            '15 to 17' => fn (int $age): bool => $age >= 15 && $age <= 17,
            '18 and over' => fn (int $age): bool => $age >= 18,
        ];

        $counts = array_fill_keys(array_keys($bands), 0);

        foreach ($members as $member) {
            $age = (int) $member->date_of_birth->diffInYears($season->age_up_date);

            foreach ($bands as $label => $matches) {
                if ($matches($age)) {
                    $counts[$label]++;
                    break;
                }
            }
        }

        return array_map(
            fn (string $label): array => ['age_group' => $label, 'count' => $counts[$label]],
            array_keys($counts),
        );
    }
}
