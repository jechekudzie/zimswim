<?php

namespace App\Http\Controllers\Public;

use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\OfficialAccreditation;
use App\Models\Organization;
use App\Models\TechnicalOfficial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The roll of accredited technical officials.
 *
 * The federation asked for its qualified officials to be named on the site.
 * This publishes the fact of the accreditation and nothing else: who holds
 * what, at which grade, through which club. No address, no telephone number,
 * no date of birth — an official agreed to be on a register, not to be
 * contactable by anyone who reads one.
 *
 * A learner is not on it. They are working towards the qualification and
 * printing them beside the people who hold it would misrepresent both.
 */
class OfficialsRegisterController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $category = $request->string('category')->toString();
        $grade = $request->string('grade')->toString();
        $province = $request->string('province')->toString();

        /*
         * The filters pick who appears, not what is shown against them. A
         * convenor looking for a starter still wants to see that the person
         * is also a national referee — that is what decides who to call.
         */
        $officials = TechnicalOfficial::query()
            ->where('status', 'active')
            ->whereHas('accreditations', fn ($query) => $query
                ->onTheRegister()
                ->when($category, fn ($inner) => $inner->where('category', $category))
                ->when($grade, fn ($inner) => $inner->where('grade', $grade)))
            ->when($province, fn ($query) => $query->where('province_organization_id', $province))
            ->with([
                'club:id,name',
                'province:id,name',
                'accreditations' => fn ($query) => $query->onTheRegister(),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (TechnicalOfficial $official): array => [
                'registration_number' => $official->registration_number,
                'name' => $official->fullName(),
                'club' => $official->club->name,
                'province' => $official->province?->name,
                'accreditations' => $official->accreditations
                    ->sortByDesc(fn (OfficialAccreditation $accreditation): int => $accreditation->grade->rank())
                    ->map(fn (OfficialAccreditation $accreditation): array => [
                        'category' => $accreditation->category->label(),
                        'grade' => $accreditation->grade->label(),
                    ])
                    ->values(),
            ])
            ->values();

        return Inertia::render('public/officials-register', [
            'officials' => $officials,
            'filters' => [
                'category' => $category ?: null,
                'grade' => $grade ?: null,
                'province' => $province ?: null,
            ],
            'categories' => OfficialCategory::options(),
            'grades' => array_values(array_filter(
                OfficialGrade::options(),
                // Learners are not on the register, so offering the grade
                // as a filter would only ever return nothing.
                fn (array $option): bool => $option['value'] !== OfficialGrade::Learner->value,
            )),
            'provinces' => Organization::query()
                ->ofType(OrganizationType::Province)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Organization $organization): array => [
                    'value' => (string) $organization->getKey(),
                    'label' => $organization->name,
                ]),
        ]);
    }
}
