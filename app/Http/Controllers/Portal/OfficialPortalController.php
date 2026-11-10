<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CourseEnrolment;
use App\Models\ExamAttempt;
use App\Models\OfficialAccreditation;
use App\Models\OfficialSession;
use App\Models\TechnicalOfficial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Where a technical official lands when they sign in.
 *
 * An official does not administer a federation; they work through a course,
 * sit an exam, stand on a pool deck and collect a certificate. The board
 * dashboard answers none of those, so they get their own screen: one page
 * showing exactly where they are on each category they are working towards,
 * including the exams they did not pass, because an attempt that failed is
 * part of the record and hiding it only makes the next one a surprise.
 */
class OfficialPortalController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $official = TechnicalOfficial::query()
            ->with(['club:id,name', 'province:id,name'])
            ->where('user_id', $user->getKey())
            ->first();

        return Inertia::render('portal/officiating', [
            'official' => $official === null ? null : [
                'registration_number' => $official->registration_number,
                'name' => $official->fullName(),
                'club' => $official->club?->name,
                'province' => $official->province?->name,
                'status' => $official->status,
            ],
            'journey' => $this->journey($user->getKey()),
            'accreditations' => $official === null ? [] : $this->accreditations($official),
            'sessions' => $official === null ? [] : $this->sessions($official),
            'certificates' => $this->certificates($user->getKey()),
        ]);
    }

    /**
     * Every course they are on, with the whole history of it.
     *
     * @return array<int, array<string, mixed>>
     */
    private function journey(int $userId): array
    {
        return CourseEnrolment::query()
            ->where('user_id', $userId)
            ->with(['course', 'attempts'])
            ->withCount('lessonCompletions')
            ->get()
            ->map(function (CourseEnrolment $enrolment): array {
                $course = $enrolment->course;
                $lessons = $course->lessons()->where('is_published', true)->count();

                return [
                    'course_slug' => $course->slug,
                    'course_title' => $course->title,
                    'category' => $course->official_category?->label(),
                    'status' => $enrolment->status->value,
                    'status_label' => $enrolment->status->label(),
                    'lessons_total' => $lessons,
                    'lessons_done' => (int) $enrolment->getAttribute('lesson_completions_count'),
                    'progress' => $enrolment->progressPercent(),
                    'pass_mark' => $course->pass_mark,
                    'attempts_used' => $enrolment->attemptsUsed(),
                    'attempts_allowed' => $course->max_attempts,
                    'attempts_remaining' => $enrolment->attemptsRemaining(),
                    'can_sit_exam' => $enrolment->canAttemptExam() && $enrolment->hasCompletedAllLessons(),
                    'exam_ready' => $course->hasEnoughQuestions(),
                    'theory_passed' => $enrolment->hasPassedTheory(),
                    'practicals_required' => $course->practical_sessions_required,
                    'practicals_done' => $enrolment->practical_sessions_completed,
                    'practicals_outstanding' => $enrolment->outstandingPracticalSessions(),
                    'ready_for_certificate' => $enrolment->isReadyForCertificate(),
                    'attempts' => $enrolment->attempts
                        ->map(fn (ExamAttempt $attempt): array => [
                            'number' => $attempt->attempt_number,
                            'score' => $attempt->score,
                            'correct' => $attempt->correct_count,
                            'total' => $attempt->question_count,
                            'passed' => $attempt->passed,
                            'status' => $attempt->status,
                            'sat_on' => $attempt->submitted_at?->toDateString()
                                ?? $attempt->started_at->toDateString(),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function accreditations(TechnicalOfficial $official): array
    {
        return $official->accreditations()
            ->orderByDesc('attained_on')
            ->get()
            ->map(fn (OfficialAccreditation $accreditation): array => [
                'category' => $accreditation->category->label(),
                'grade' => $accreditation->grade->label(),
                'status' => $accreditation->status->value,
                'status_label' => $accreditation->status->label(),
                'attained_on' => $accreditation->attained_on?->toDateString(),
                'expires_on' => $accreditation->expires_on?->toDateString(),
                'lapses_soon' => $accreditation->expires_on !== null
                    && $accreditation->expires_on->isBefore(now()->addDays(90)),
            ])
            ->all();
    }

    /**
     * The last of their poolside work, signed off or still waiting on a
     * referee's signature.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sessions(TechnicalOfficial $official): array
    {
        return $official->sessions()
            ->with('gala:id,name,slug')
            ->orderByDesc('worked_on')
            ->limit(10)
            ->get()
            ->map(fn (OfficialSession $session): array => [
                'gala' => $session->gala->name ?? 'A gala no longer on the calendar',
                'role' => $session->role,
                'worked_on' => $session->worked_on->toDateString(),
                'as_learner' => $session->as_learner,
                'signed_off' => $session->signed_off_at !== null,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function certificates(int $userId): array
    {
        return Certificate::query()
            ->where('user_id', $userId)
            ->orderByDesc('issued_on')
            ->get()
            ->map(fn (Certificate $certificate): array => [
                'code' => $certificate->verification_code,
                'title' => $certificate->title,
                'grade' => $certificate->grade,
                'issued_on' => $certificate->issued_on->toDateString(),
                'expires_on' => $certificate->expires_on?->toDateString(),
                'valid' => $certificate->isValid(),
                'expired' => $certificate->hasExpired(),
            ])
            ->all();
    }
}
