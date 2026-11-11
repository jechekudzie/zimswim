<?php

namespace App\Http\Controllers\Public;

use App\Enums\OfficialCategory;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\OfficialApplication;
use App\Models\Organization;
use App\Services\OfficialOnboarding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Applying to officiate, without an account.
 *
 * Nobody can give themselves a place on the register, so this collects what
 * the technical committee needs to decide and nothing else. The rules it
 * enforces are the certification standards, read from the enum, so the form
 * cannot accept somebody the register would later have to turn away.
 */
class OfficialApplicationController extends Controller
{
    public function __construct(private readonly OfficialOnboarding $onboarding) {}

    public function create(Request $request): Response
    {
        $wanted = $request->string('category')->toString();

        return Inertia::render('public/officials-apply', [
            'categories' => $this->categories(),
            'provinces' => $this->provinces(),
            'courses' => $this->courses(),
            // Arriving from a course on the pathway page, the choice is
            // already made; only a category somebody could actually start
            // with is honoured, so the link cannot pre-select something the
            // form would then refuse.
            'preselected' => in_array($wanted, array_map(
                fn (OfficialCategory $category): string => $category->value,
                $this->entryCategories(),
            ), true) ? $wanted : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $entryCategories = array_map(
            fn (OfficialCategory $category): string => $category->value,
            $this->entryCategories(),
        );

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'category' => ['required', Rule::in($entryCategories)],
            'organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')->where('type', OrganizationType::Club->value)],
            'province_organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')->where('type', OrganizationType::Province->value)],
            'club_name_given' => ['nullable', 'string', 'max:255'],
            'membership_number' => ['nullable', 'string', 'max:32'],
            'motivation' => ['nullable', 'string', 'max:1000'],
        ], [
            'category.in' => 'Start with a category that has no prerequisite. The rest are earned from there.',
        ]);

        $category = OfficialCategory::from($validated['category']);
        $age = (int) now()->diffInYears($validated['date_of_birth'], absolute: true);

        if ($age < $category->minimumAge()) {
            throw ValidationException::withMessages([
                'date_of_birth' => sprintf(
                    'A %s must be at least %d. You are welcome to apply once you are.',
                    $category->label(),
                    $category->minimumAge(),
                ),
            ]);
        }

        // One open application at a time, so the committee is not asked the
        // same question twice and the applicant is not left wondering which
        // reference is the live one.
        $open = OfficialApplication::query()
            ->where('email', $validated['email'])
            ->open()
            ->first();

        if ($open !== null) {
            throw ValidationException::withMessages([
                'email' => 'You already have an application with us under reference '.$open->reference.'. We will write as soon as it has been looked at.',
            ]);
        }

        $application = $this->onboarding->apply($validated);

        return to_route('officials.applied')->with('application', [
            'reference' => $application->reference,
            'category' => $application->category->label(),
            'email' => $application->email,
        ]);
    }

    /**
     * The page they land on once it is in.
     */
    public function applied(Request $request): Response
    {
        $application = $request->session()->get('application');

        // Arriving here without having just applied is a bookmark or a
        // refresh, not an error worth a 404.
        if (! is_array($application)) {
            return Inertia::render('public/officials-applied', ['application' => null]);
        }

        return Inertia::render('public/officials-applied', ['application' => $application]);
    }

    /**
     * The categories somebody can apply for straight off: the ones the
     * standards set no prerequisite for.
     *
     * @return array<int, OfficialCategory>
     */
    private function entryCategories(): array
    {
        return array_values(array_filter(
            OfficialCategory::cases(),
            fn (OfficialCategory $category): bool => $category->prerequisites() === [],
        ));
    }

    /**
     * Every category, with what it asks of you and whether it can be the
     * first one you hold.
     *
     * @return array<int, array<string, mixed>>
     */
    private function categories(): array
    {
        return array_map(
            fn (OfficialCategory $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
                'minimum_age' => $category->minimumAge(),
                'learner_sessions' => $category->learnerSessionsRequired(),
                'has_exam' => $category->hasExam(),
                'prerequisites' => array_map(
                    fn (OfficialCategory $prerequisite): string => $prerequisite->label(),
                    $category->prerequisites(),
                ),
                'is_entry' => $category->prerequisites() === [],
            ],
            OfficialCategory::cases(),
        );
    }

    /**
     * Provinces, each carrying the clubs affiliated to it, so the wizard can
     * narrow a long list to the one province the applicant swims in.
     *
     * @return array<int, array{id: int, name: string, clubs: array<int, array{id: int, name: string}>}>
     */
    private function provinces(): array
    {
        return Organization::query()
            ->ofType(OrganizationType::Province)
            ->active()
            ->with(['children' => fn ($query) => $query
                ->where('type', OrganizationType::Club)
                ->where('is_active', true)
                ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Organization $province): array => [
                'id' => $province->getKey(),
                'name' => $province->name,
                'clubs' => $province->children
                    ->map(fn (Organization $club): array => [
                        'id' => $club->getKey(),
                        'name' => $club->name,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * What the Academy actually teaches, so the pathway page is a prospectus
     * rather than a promise.
     *
     * @return array<int, array<string, mixed>>
     */
    private function courses(): array
    {
        return Course::query()
            ->published()
            ->withCount(['lessons' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('position')
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course): array => [
                'title' => $course->title,
                'summary' => $course->summary,
                'category' => $course->official_category?->value,
                'category_label' => $course->official_category?->label(),
                'grade' => $course->awards_grade?->label(),
                'lessons_count' => $course->lessons_count,
                'exam_minutes' => $course->exam_minutes,
                'pass_mark' => $course->pass_mark,
                'practical_sessions_required' => $course->practical_sessions_required,
                'minimum_age' => $course->minimum_age,
                // A course whose bank is too small cannot examine anybody
                // yet, and saying so is better than letting a learner find
                // out at the end of the lessons.
                'exam_ready' => $course->hasEnoughQuestions(),
            ])
            ->all();
    }
}
