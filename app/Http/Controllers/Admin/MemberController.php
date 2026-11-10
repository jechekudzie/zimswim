<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MemberStatus;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberRequest;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Season;
use App\Support\Csv;
use App\Support\OrganizationContext;
use App\Support\OrganizationOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberController extends Controller
{
    public function __construct(
        private readonly OrganizationContext $context,
        private readonly OrganizationOptions $options,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Member::class);

        $season = Season::current();

        $members = $this->filtered($request)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Member $member): array => [
                'id' => $member->getKey(),
                'membership_number' => $member->membership_number,
                'name' => $member->fullName(),
                'date_of_birth' => $member->date_of_birth->toDateString(),
                'age' => $season === null ? null : $member->competitionAge($season),
                'gender' => $member->gender,
                'status' => $member->status->value,
                'status_label' => $member->status->label(),
                'club' => $member->club->name,
                'province' => $member->sportingProvince?->name,
                'has_consent' => $member->hasRequiredConsent(),
            ]);

        return Inertia::render('admin/members/index', [
            'members' => $members,
            'filters' => [
                'search' => $request->string('search')->toString() ?: null,
                'status' => $request->string('status')->toString() ?: null,
                'province' => $request->string('province')->toString() ?: null,
                'club' => $request->string('club')->toString() ?: null,
            ],
            ...$this->options->all(),
            'statuses' => MemberStatus::options(),
            'season' => $season === null ? null : [
                'name' => $season->name,
                'age_up_date' => $season->age_up_date->toDateString(),
            ],
        ]);
    }

    /**
     * The register as it currently reads, as a spreadsheet.
     *
     * The export runs the listing's own query, so whatever the search box
     * and the status filter have narrowed the page down to is exactly what
     * leaves the building - and the organisation scope goes with it, so a
     * club secretary cannot export the country.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Member::class);

        $season = Season::current();

        return Csv::download('swimmers', [
            'Membership number',
            'Last name',
            'First name',
            'Date of birth',
            'Age at age-up',
            'Gender',
            'Status',
            'Club',
            'Province',
            'Consent on file',
        ], $this->filtered($request)->lazy()->map(fn (Member $member): array => [
            $member->membership_number,
            $member->last_name,
            $member->first_name,
            $member->date_of_birth->toDateString(),
            $season === null ? null : $member->competitionAge($season),
            $member->gender,
            $member->status->label(),
            $member->club->name,
            $member->sportingProvince?->name,
            $member->hasRequiredConsent(),
        ]));
    }

    /**
     * The listing's query, shared by the page and its export so the two can
     * never drift apart.
     *
     * @return Builder<Member>
     */
    private function filtered(Request $request): Builder
    {
        return Member::query()
            ->forOrganizations($this->context->scopeIds())
            ->search($request->string('search')->toString())
            ->when(
                $request->string('status')->toString(),
                fn ($query, $status) => $query->where('status', $status),
            )
            ->when(
                $request->string('province')->toString(),
                fn ($query, $province) => $query->where('province_organization_id', $province),
            )
            ->when(
                $request->string('club')->toString(),
                fn ($query, $club) => $query->where('organization_id', $club),
            )
            ->with(['club:id,name', 'sportingProvince:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public function create(): Response
    {
        $this->authorize('create', Member::class);

        return Inertia::render('admin/members/form', [
            'member' => null,
            'clubs' => $this->clubs(),
            'provinces' => $this->provinces(),
            'statuses' => MemberStatus::options(),
            'suggestedNumber' => $this->nextMembershipNumber(),
        ]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $this->authorize('create', Member::class);

        $member = Member::create($this->attributes($request));

        return to_route('admin.members.edit', $member)
            ->with('success', $member->fullName().' has been registered.');
    }

    public function edit(Member $member): Response
    {
        $this->authorize('update', $member);

        $member->load(['registrations.season']);

        return Inertia::render('admin/members/form', [
            'member' => [
                'id' => $member->getKey(),
                'membership_number' => $member->membership_number,
                'organization_id' => $member->organization_id,
                'province_organization_id' => $member->province_organization_id,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'date_of_birth' => $member->date_of_birth->toDateString(),
                'gender' => $member->gender,
                'status' => $member->status->value,
                'school' => $member->school,
                'emergency_contact_name' => $member->emergency_contact_name,
                'emergency_contact_phone' => $member->emergency_contact_phone,
                'medical_notes' => $member->medical_notes,
                'photo_consent' => $member->photo_consent,
                'consent_given' => $member->consent_given_at !== null,
                'consent_given_by' => $member->consent_given_by,
                'registrations' => $member->registrations->map(fn ($registration): array => [
                    'season' => $registration->season->name,
                    'status' => $registration->status,
                    'fully_capitated' => $registration->isFullyCapitated(),
                ]),
            ],
            'clubs' => $this->clubs(),
            'provinces' => $this->provinces(),
            'statuses' => MemberStatus::options(),
            'suggestedNumber' => null,
        ]);
    }

    public function update(MemberRequest $request, Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $member->update($this->attributes($request));

        return back()->with('success', $member->fullName().' has been updated.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $member->delete();

        return to_route('admin.members.index')
            ->with('success', $member->fullName().' has been removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(MemberRequest $request): array
    {
        $validated = $request->validated();
        $consentGiven = (bool) ($validated['consent_given'] ?? false);

        unset($validated['consent_given']);

        return [
            ...$validated,
            'consent_given_at' => $consentGiven ? now() : null,
            'joined_on' => $request->member()?->joined_on?->toDateString() ?? now()->toDateString(),
        ];
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function clubs(): array
    {
        return Organization::query()
            ->whereIn('id', $this->context->scopeIds())
            ->where('type', OrganizationType::Club)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Organization $club): array => ['value' => $club->getKey(), 'label' => $club->name])
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function provinces(): array
    {
        return Organization::query()
            ->ofType(OrganizationType::Province)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Organization $province): array => ['value' => $province->getKey(), 'label' => $province->name])
            ->all();
    }

    private function nextMembershipNumber(): string
    {
        $highest = Member::query()
            ->withTrashed()
            ->where('membership_number', 'like', 'ZSW-%')
            ->orderByDesc('membership_number')
            ->value('membership_number');

        $next = $highest === null ? 1001 : ((int) substr($highest, 4)) + 1;

        return sprintf('ZSW-%05d', $next);
    }
}
