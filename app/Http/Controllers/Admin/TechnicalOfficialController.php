<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccreditationStatus;
use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\OfficialAccreditation;
use App\Models\Organization;
use App\Models\TechnicalOfficial;
use App\Support\Csv;
use App\Support\OrganizationContext;
use App\Support\OrganizationOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TechnicalOfficialController extends Controller
{
    public function __construct(
        private readonly OrganizationContext $context,
        private readonly OrganizationOptions $options,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TechnicalOfficial::class);

        $officials = $this->filtered($request)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (TechnicalOfficial $official): array => [
                'id' => $official->getKey(),
                'registration_number' => $official->registration_number,
                'name' => $official->fullName(),
                'club' => $official->club->name,
                'province' => $official->province?->name,
                'status' => $official->status,
                'accreditations' => $official->accreditations->map(fn (OfficialAccreditation $accreditation): array => [
                    'category' => $accreditation->category->label(),
                    'grade' => $accreditation->grade->label(),
                    'status' => $accreditation->status->value,
                    'expires_on' => $accreditation->expires_on?->toDateString(),
                    'has_lapsed' => $accreditation->hasLapsed(),
                ]),
            ]);

        return Inertia::render('admin/officials/index', [
            'officials' => $officials,
            'filters' => [
                'search' => $request->string('search')->toString() ?: null,
                'category' => $request->string('category')->toString() ?: null,
                'province' => $request->string('province')->toString() ?: null,
                'club' => $request->string('club')->toString() ?: null,
            ],
            ...$this->options->all(),
            'categories' => OfficialCategory::options(),
        ]);
    }

    public function show(TechnicalOfficial $official): Response
    {
        $this->authorize('view', $official);

        $official->load(['club', 'province', 'accreditations', 'sessions.gala', 'certificates.course']);

        return Inertia::render('admin/officials/show', [
            'official' => [
                'id' => $official->getKey(),
                'registration_number' => $official->registration_number,
                'name' => $official->fullName(),
                'email' => $official->email,
                'phone' => $official->phone,
                'club' => $official->club->name,
                'province' => $official->province?->name,
                'status' => $official->status,
            ],
            'accreditations' => $official->accreditations->map(fn (OfficialAccreditation $accreditation): array => [
                'id' => $accreditation->getKey(),
                'category' => $accreditation->category->value,
                'category_label' => $accreditation->category->label(),
                'grade' => $accreditation->grade->value,
                'grade_label' => $accreditation->grade->label(),
                'status' => $accreditation->status->value,
                'status_label' => $accreditation->status->label(),
                'learner_sessions_completed' => $accreditation->learner_sessions_completed,
                'learner_sessions_required' => $accreditation->category->learnerSessionsRequired(),
                'outstanding_sessions' => $accreditation->outstandingLearnerSessions(),
                'ready_for_exam' => $accreditation->isReadyForExam(),
                'clinic_attended_on' => $accreditation->clinic_attended_on?->toDateString(),
                'exam_passed_on' => $accreditation->exam_passed_on?->toDateString(),
                'attained_on' => $accreditation->attained_on?->toDateString(),
                'expires_on' => $accreditation->expires_on?->toDateString(),
                'has_lapsed' => $accreditation->hasLapsed(),
                'ratified_at' => $accreditation->ratified_at?->toDateString(),
            ]),
            'sessions' => $official->sessions->map(fn ($session): array => [
                'role' => $session->role,
                'gala' => $session->gala?->name,
                'worked_on' => $session->worked_on->toDateString(),
                'as_learner' => $session->as_learner,
                'signed_off' => $session->isSignedOff(),
            ]),
            /*
             * What they have actually been awarded, as opposed to what the
             * register says they hold. A convenor standing at a pool deck
             * needs to be able to pull the paper up, not just read a row.
             */
            'certificates' => $official->certificates
                ->sortByDesc('issued_on')
                ->map(fn (Certificate $certificate): array => [
                    'code' => $certificate->verification_code,
                    'title' => $certificate->title,
                    'grade' => $certificate->grade,
                    'issued_on' => $certificate->issued_on->toDateString(),
                    'expires_on' => $certificate->expires_on?->toDateString(),
                    'valid' => $certificate->isValid(),
                    'expired' => $certificate->hasExpired(),
                ])
                ->values(),
            'availableCategories' => array_map(
                fn (OfficialCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                    'minimum_age' => $category->minimumAge(),
                ],
                $official->availableCategories(),
            ),
            'grades' => OfficialGrade::options(),
            'can' => [
                'manage' => $this->canManage($official),
                'ratify' => request()->user()->can('ratify', $official),
            ],
        ]);
    }

    /**
     * The officials list as a spreadsheet, narrowed exactly as the page is.
     *
     * Accreditations are collapsed into one cell per official rather than
     * split across fixed columns, because the set each person holds differs
     * and empty columns would read as a lapse rather than an absence.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', TechnicalOfficial::class);

        return Csv::download('officials', [
            'Registration number',
            'Last name',
            'First name',
            'Club',
            'Province',
            'Status',
            'Current accreditations',
            'Earliest expiry',
        ], $this->filtered($request)->lazy()->map(function (TechnicalOfficial $official): array {
            $current = $official->accreditations
                ->where('status', AccreditationStatus::Active);

            return [
                $official->registration_number,
                $official->last_name,
                $official->first_name,
                $official->club->name,
                $official->province?->name,
                $official->status,
                $current
                    ->map(fn (OfficialAccreditation $accreditation): string => sprintf(
                        '%s (%s)',
                        $accreditation->category->label(),
                        $accreditation->grade->label(),
                    ))
                    ->implode('; '),
                $current
                    ->filter(fn (OfficialAccreditation $accreditation): bool => $accreditation->expires_on !== null)
                    ->min('expires_on')?->toDateString(),
            ];
        }));
    }

    /**
     * @return Builder<TechnicalOfficial>
     */
    private function filtered(Request $request): Builder
    {
        return TechnicalOfficial::query()
            ->forOrganizations($this->context->scopeIds())
            ->search($request->string('search')->toString())
            ->when($request->string('category')->toString(), function ($query, $category): void {
                $query->whereHas('accreditations', fn ($inner) => $inner
                    ->where('category', $category)
                    ->where('status', AccreditationStatus::Active));
            })
            ->when(
                $request->string('province')->toString(),
                fn ($query, $province) => $query->where('province_organization_id', $province),
            )
            ->when(
                $request->string('club')->toString(),
                fn ($query, $club) => $query->where('organization_id', $club),
            )
            ->with(['club:id,name', 'province:id,name', 'accreditations'])
            ->orderBy('last_name');
    }

    public function create(): Response
    {
        $this->authorize('create', TechnicalOfficial::class);

        return Inertia::render('admin/officials/form', [
            'official' => null,
            'clubs' => $this->clubs(),
            'provinces' => $this->provinces(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TechnicalOfficial::class);

        $official = TechnicalOfficial::create($this->validated($request));

        return to_route('admin.officials.show', $official)
            ->with('success', $official->fullName().' has been added to the officials register.');
    }

    public function edit(TechnicalOfficial $official): Response
    {
        $this->authorize('update', $official);

        return Inertia::render('admin/officials/form', [
            'official' => [
                'id' => $official->getKey(),
                'registration_number' => $official->registration_number,
                'organization_id' => $official->organization_id,
                'province_organization_id' => $official->province_organization_id,
                'first_name' => $official->first_name,
                'last_name' => $official->last_name,
                'email' => $official->email,
                'phone' => $official->phone,
                'date_of_birth' => $official->date_of_birth?->toDateString(),
                'status' => $official->status,
            ],
            'clubs' => $this->clubs(),
            'provinces' => $this->provinces(),
        ]);
    }

    public function update(Request $request, TechnicalOfficial $official): RedirectResponse
    {
        $this->authorize('update', $official);

        $official->update($this->validated($request, $official));

        return back()->with('success', $official->fullName().' has been updated.');
    }

    private function canManage(TechnicalOfficial $official): bool
    {
        return request()->user()->can('update', $official);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?TechnicalOfficial $official = null): array
    {
        return $request->validate([
            'registration_number' => [
                'required', 'string', 'max:24',
                Rule::unique('technical_officials', 'registration_number')->ignore($official?->getKey()),
            ],
            'organization_id' => ['required', 'integer', Rule::in($this->context->scopeIds())],
            'province_organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ]);
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
}
