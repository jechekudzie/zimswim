<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OfficialApplicationStatus;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\OfficialApplication;
use App\Models\Organization;
use App\Models\TechnicalOfficial;
use App\Services\OfficialOnboarding;
use App\Support\Csv;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The queue of people asking to be let onto the officials pathway.
 *
 * Approving one is the only way an official account comes into existence
 * from the outside, so the screen puts the three things a reviewer has to
 * check — age against the category, the club, and what they wrote — in front
 * of them rather than behind a link.
 */
class OfficialApplicationController extends Controller
{
    public function __construct(
        private readonly OrganizationContext $context,
        private readonly OfficialOnboarding $onboarding,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TechnicalOfficial::class);

        return Inertia::render('admin/official-applications/index', [
            'applications' => $this->filtered($request)
                ->paginate(20)
                ->withQueryString()
                ->through($this->summarise(...)),
            'filters' => [
                'status' => $request->string('status')->toString() ?: null,
                'search' => $request->string('search')->toString() ?: null,
            ],
            'statuses' => OfficialApplicationStatus::options(),
            'counts' => $this->counts(),
            'clubs' => $this->clubs(),
            'can' => [
                'review' => $request->user()->can('create', TechnicalOfficial::class),
            ],
        ]);
    }

    /**
     * Admit the applicant, opening the account and the enrolment with it.
     */
    public function approve(Request $request, OfficialApplication $application): RedirectResponse
    {
        $this->authorize('create', TechnicalOfficial::class);

        $validated = $request->validate([
            // A reviewer can attach a club the applicant could not find, and
            // must where they named one that is not on the register.
            'organization_id' => ['nullable', 'integer', Rule::in($this->clubIds())],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (isset($validated['organization_id'])) {
            $club = Organization::query()->whereKey($validated['organization_id'])->sole();

            $application->update([
                'organization_id' => $club->getKey(),
                'province_organization_id' => $club->parent_id,
            ]);
        }

        $application = $this->onboarding->approve(
            $application->fresh(),
            $request->user(),
            $validated['review_notes'] ?? null,
        );

        return back()->with(
            'success',
            $application->fullName().' is on the register as '.$application->technicalOfficial?->registration_number.'. An invitation is on its way to '.$application->email.'.',
        );
    }

    /**
     * Turn the applicant down. The reason is required because it is the only
     * thing they are given, and it travels to them verbatim.
     */
    public function decline(Request $request, OfficialApplication $application): RedirectResponse
    {
        $this->authorize('create', TechnicalOfficial::class);

        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'review_notes.required' => 'Give a reason. It is sent to the applicant word for word.',
            'review_notes.min' => 'Give a reason somebody can act on.',
        ]);

        $this->onboarding->decline($application, $request->user(), $validated['review_notes']);

        return back()->with('success', 'The applicant has been told, and why.');
    }

    /**
     * The queue as a spreadsheet, narrowed exactly as the page is.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', TechnicalOfficial::class);

        return Csv::download('official-applications', [
            'Reference',
            'Name',
            'Email',
            'Phone',
            'Date of birth',
            'Category',
            'Club',
            'Province',
            'Status',
            'Applied',
            'Reviewed',
            'Notes',
        ], $this->filtered($request)->lazy()->map(fn (OfficialApplication $application): array => [
            $application->reference,
            $application->fullName(),
            $application->email,
            $application->phone,
            $application->date_of_birth->toDateString(),
            $application->category->label(),
            $application->clubLabel(),
            $application->province?->name,
            $application->status->label(),
            $application->created_at?->toDateString(),
            $application->reviewed_at?->toDateString(),
            $application->review_notes,
        ]));
    }

    /**
     * How many applications sit at each standing, so the queue shows its own
     * size before anybody filters it.
     *
     * @return array<string, int>
     */
    private function counts(): array
    {
        /** @var array<string, int> $counts */
        $counts = OfficialApplication::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function summarise(OfficialApplication $application): array
    {
        return [
            'reference' => $application->reference,
            'name' => $application->fullName(),
            'email' => $application->email,
            'phone' => $application->phone,
            'date_of_birth' => $application->date_of_birth->toDateString(),
            'age' => $application->ageAtApplication(),
            'category' => $application->category->value,
            'category_label' => $application->category->label(),
            'minimum_age' => $application->category->minimumAge(),
            'club' => $application->club?->name,
            'club_name_given' => $application->club_name_given,
            'club_label' => $application->clubLabel(),
            'club_on_register' => $application->organization_id !== null,
            'province' => $application->province?->name,
            'membership_number' => $application->membership_number,
            'motivation' => $application->motivation,
            'status' => $application->status->value,
            'status_label' => $application->status->label(),
            'is_open' => $application->status->isOpen(),
            'applied_on' => $application->created_at?->toDateString(),
            'reviewed_on' => $application->reviewed_at?->toDateString(),
            'reviewer' => $application->reviewer?->name,
            'review_notes' => $application->review_notes,
            'registration_number' => $application->technicalOfficial?->registration_number,
        ];
    }

    /**
     * Clubs in the reviewer's scope, for attaching an applicant whose club
     * was not on the register when they applied.
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function clubs(): array
    {
        return Organization::query()
            ->whereIn('id', $this->context->scopeIds())
            ->ofType(OrganizationType::Club)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Organization $club): array => [
                'value' => $club->getKey(),
                'label' => $club->name,
            ])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function clubIds(): array
    {
        return array_column($this->clubs(), 'value');
    }

    /**
     * @return Builder<OfficialApplication>
     */
    private function filtered(Request $request): Builder
    {
        return OfficialApplication::query()
            ->with(['club:id,name', 'province:id,name', 'reviewer:id,name', 'technicalOfficial:id,registration_number'])
            ->when(
                $request->string('status')->toString(),
                fn ($query, $status) => $query->where('status', $status),
            )
            ->when($request->string('search')->toString(), function ($query, $search): void {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $query->where(fn ($inner) => $inner
                    ->where('first_name', 'ilike', $like)
                    ->orWhere('last_name', 'ilike', $like)
                    ->orWhere('email', 'ilike', $like)
                    ->orWhere('reference', 'ilike', $like));
            })
            // The oldest open application is the one somebody has been
            // waiting on longest, so the queue reads in that order.
            ->orderByRaw("case when status in ('submitted', 'under_review') then 0 else 1 end")
            ->orderBy('created_at');
    }
}
