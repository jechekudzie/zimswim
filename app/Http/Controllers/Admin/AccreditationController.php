<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccreditationStatus;
use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Http\Controllers\Controller;
use App\Models\OfficialAccreditation;
use App\Models\TechnicalOfficial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Moves an official along the certification pathway. The grading standards are
 * enforced here rather than trusted to the form.
 */
class AccreditationController extends Controller
{
    public function store(Request $request, TechnicalOfficial $official): RedirectResponse
    {
        $this->authorize('update', $official);

        $validated = $request->validate([
            'category' => ['required', Rule::enum(OfficialCategory::class)],
            'clinic_attended_on' => ['nullable', 'date'],
        ]);

        $category = OfficialCategory::from($validated['category']);
        $official->load('accreditations');

        $this->guardPrerequisites($official, $category);
        $this->guardMinimumAge($official, $category);

        OfficialAccreditation::create([
            'technical_official_id' => $official->getKey(),
            'category' => $category,
            'grade' => OfficialGrade::Learner,
            'status' => AccreditationStatus::InProgress,
            'clinic_attended_on' => $validated['clinic_attended_on'] ?? null,
        ]);

        return back()->with('success', $category->label().' started for '.$official->fullName().'.');
    }

    public function update(Request $request, TechnicalOfficial $official, OfficialAccreditation $accreditation): RedirectResponse
    {
        $this->authorize('update', $official);

        $validated = $request->validate([
            'learner_sessions_completed' => ['required', 'integer', 'min:0', 'max:100'],
            'clinic_attended_on' => ['nullable', 'date'],
            'exam_passed_on' => ['nullable', 'date'],
        ]);

        $accreditation->update($validated);

        return back()->with('success', 'Progress recorded.');
    }

    /**
     * Ratification is the technical committee's decision and is what makes a
     * grade usable at a gala.
     */
    public function ratify(Request $request, TechnicalOfficial $official, OfficialAccreditation $accreditation): RedirectResponse
    {
        $this->authorize('ratify', $official);

        $validated = $request->validate([
            'grade' => ['required', Rule::enum(OfficialGrade::class)],
        ]);

        if ($accreditation->category->hasExam() && $accreditation->exam_passed_on === null) {
            throw ValidationException::withMessages([
                'grade' => 'The '.$accreditation->category->label().' exam must be passed before this grade can be ratified.',
            ]);
        }

        if ($accreditation->outstandingLearnerSessions() > 0) {
            throw ValidationException::withMessages([
                'grade' => $accreditation->outstandingLearnerSessions().' learner sessions are still outstanding.',
            ]);
        }

        $grade = OfficialGrade::from($validated['grade']);

        $accreditation->update([
            'grade' => $grade,
            'status' => AccreditationStatus::Active,
            'attained_on' => now()->toDateString(),
            // Accreditation lapses after two years without officiating at this level.
            'expires_on' => now()->addYears($grade->lapsesAfterYears())->toDateString(),
            'ratified_by' => $request->user()->getKey(),
            'ratified_at' => now(),
        ]);

        return back()->with(
            'success',
            $official->fullName().' is now '.$grade->label().' for '.$accreditation->category->label().'.',
        );
    }

    private function guardPrerequisites(TechnicalOfficial $official, OfficialCategory $category): void
    {
        foreach ($category->prerequisites() as $prerequisite) {
            if ($official->gradeFor($prerequisite) === null) {
                throw ValidationException::withMessages([
                    'category' => $category->label().' requires a qualified '.$prerequisite->label().' first.',
                ]);
            }
        }
    }

    private function guardMinimumAge(TechnicalOfficial $official, OfficialCategory $category): void
    {
        if ($official->date_of_birth === null) {
            return;
        }

        if ($official->date_of_birth->age < $category->minimumAge()) {
            throw ValidationException::withMessages([
                'category' => $category->label().' has a minimum age of '.$category->minimumAge().' years.',
            ]);
        }
    }
}
