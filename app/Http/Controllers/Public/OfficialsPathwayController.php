<?php

namespace App\Http\Controllers\Public;

use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\TechnicalOfficial;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public explainer for the officials pathway, built from the certification
 * and grading standards rather than hand-written copy, so it cannot drift from
 * the rules the register enforces.
 */
class OfficialsPathwayController extends Controller
{
    public function __invoke(): Response
    {
        $categories = array_map(
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
            ],
            OfficialCategory::cases(),
        );

        return Inertia::render('public/officials', [
            'categories' => $categories,
            'grades' => array_map(
                fn (OfficialGrade $grade): array => [
                    'value' => $grade->value,
                    'label' => $grade->label(),
                ],
                OfficialGrade::cases(),
            ),
            'registeredCount' => TechnicalOfficial::query()->where('status', 'active')->count(),
            'courses' => $this->courses(),
        ]);
    }

    /**
     * What the Academy actually teaches, so the page is a prospectus rather
     * than a promise.
     *
     * A course whose question bank is too small cannot examine anybody yet,
     * and saying so here is better than a learner finding out at the end of
     * the lessons.
     *
     * @return array<int, array<string, mixed>>
     */
    private function courses(): array
    {
        return Course::query()
            ->published()
            ->with(['lessons' => fn ($query) => $query
                ->where('is_published', true)
                ->orderBy('position'),
            ])
            ->orderBy('position')
            ->orderBy('title')
            ->get()
            ->map(function (Course $course): array {
                $category = $course->official_category;

                return [
                    'title' => $course->title,
                    'summary' => $course->summary,
                    'description' => $course->description,
                    'category' => $category?->value,
                    'category_label' => $category?->label(),
                    'grade' => $course->awards_grade?->label(),
                    // The modules by name, which is what turns a list of
                    // courses into something somebody can judge before they
                    // commit a season to it.
                    'lessons' => $course->lessons
                        ->map(fn (CourseLesson $lesson): array => [
                            'title' => $lesson->title,
                            'minutes' => (int) round(($lesson->video_seconds ?? 0) / 60),
                        ])
                        ->values()
                        ->all(),
                    'study_minutes' => (int) round(
                        $course->lessons->sum(fn (CourseLesson $lesson): int => $lesson->video_seconds ?? 0) / 60,
                    ),
                    'exam_minutes' => $course->exam_minutes,
                    'pass_mark' => $course->pass_mark,
                    'max_attempts' => $course->max_attempts,
                    'practical_sessions_required' => $course->practical_sessions_required,
                    'minimum_age' => $category?->minimumAge() ?? $course->minimum_age,
                    'prerequisites' => array_map(
                        fn (OfficialCategory $prerequisite): string => $prerequisite->label(),
                        $category?->prerequisites() ?? [],
                    ),
                    'is_entry' => ($category?->prerequisites() ?? []) === [],
                    'exam_ready' => $course->hasEnoughQuestions(),
                ];
            })
            ->all();
    }
}
