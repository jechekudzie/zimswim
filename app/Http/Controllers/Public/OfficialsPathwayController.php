<?php

namespace App\Http\Controllers\Public;

use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Http\Controllers\Controller;
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
        ]);
    }
}
