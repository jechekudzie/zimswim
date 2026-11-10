<?php

namespace App\Http\Controllers\Academy;

use App\Enums\EnrolmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Models\CourseLesson;
use App\Models\LessonCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Academy as a learner sees it: pick a course, work through the lessons,
 * sit the exam, then book poolside practicals.
 */
class AcademyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $enrolments = CourseEnrolment::query()
            ->where('user_id', $user->getKey())
            ->with('course')
            ->get()
            ->keyBy('course_id');

        $courses = Course::query()
            ->published()
            ->withCount(['lessons' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('position')
            ->orderBy('title')
            ->get()
            ->map(function (Course $course) use ($enrolments): array {
                $enrolment = $enrolments[$course->getKey()] ?? null;

                return [
                    'slug' => $course->slug,
                    'title' => $course->title,
                    'summary' => $course->summary,
                    'category' => $course->official_category?->label(),
                    'grade' => $course->awards_grade?->label(),
                    'lessons_count' => $course->lessons_count,
                    'exam_minutes' => $course->exam_minutes,
                    'pass_mark' => $course->pass_mark,
                    'practical_sessions_required' => $course->practical_sessions_required,
                    'minimum_age' => $course->minimum_age,
                    'fee' => $course->fee,
                    'enrolled' => $enrolment !== null,
                    'status' => $enrolment?->status->label(),
                    'progress' => $enrolment?->progressPercent() ?? 0,
                ];
            });

        return Inertia::render('academy/index', [
            'courses' => $courses,
            'certificates' => $user->certificates()
                ->with('course:id,title')
                ->orderByDesc('issued_on')
                ->get()
                ->map(fn ($certificate): array => [
                    'code' => $certificate->verification_code,
                    'title' => $certificate->title,
                    'issued_on' => $certificate->issued_on->toDateString(),
                    'expires_on' => $certificate->expires_on?->toDateString(),
                    'valid' => $certificate->isValid(),
                ]),
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        $this->authorize('view', $course);

        $enrolment = $this->enrolmentFor($request, $course);
        $completed = $enrolment?->lessonCompletions()->pluck('course_lesson_id')->all() ?? [];

        return Inertia::render('academy/course', [
            'course' => [
                'slug' => $course->slug,
                'title' => $course->title,
                'summary' => $course->summary,
                'description' => $course->description,
                'category' => $course->official_category?->label(),
                'grade' => $course->awards_grade?->label(),
                'pass_mark' => $course->pass_mark,
                'exam_minutes' => $course->exam_minutes,
                'exam_question_count' => $course->exam_question_count,
                'max_attempts' => $course->max_attempts,
                'practical_sessions_required' => $course->practical_sessions_required,
                'minimum_age' => $course->minimum_age,
                'fee' => $course->fee,
            ],
            'lessons' => $course->lessons()->where('is_published', true)->get()
                ->map(fn (CourseLesson $lesson): array => [
                    'slug' => $lesson->slug,
                    'title' => $lesson->title,
                    'summary' => $lesson->summary,
                    'runtime' => $lesson->runtime(),
                    'completed' => in_array($lesson->getKey(), $completed, true),
                ]),
            'enrolment' => $enrolment === null ? null : [
                'status' => $enrolment->status->value,
                'status_label' => $enrolment->status->label(),
                'progress' => $enrolment->progressPercent(),
                'theory_passed' => $enrolment->hasPassedTheory(),
                'can_sit_exam' => $enrolment->canAttemptExam(),
                'attempts_remaining' => $enrolment->attemptsRemaining(),
                'practical_done' => $enrolment->practical_sessions_completed,
                'practical_outstanding' => $enrolment->outstandingPracticalSessions(),
                'ready_for_certificate' => $enrolment->isReadyForCertificate(),
                'attempts' => $enrolment->attempts()->where('status', '!=', 'in_progress')->get()
                    ->map(fn ($attempt): array => [
                        'number' => $attempt->attempt_number,
                        'score' => $attempt->score,
                        'passed' => $attempt->passed,
                        'submitted_at' => $attempt->submitted_at?->toDateString(),
                    ]),
            ],
        ]);
    }

    public function enrol(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('enrol', $course);

        $user = $request->user();

        if ($course->minimum_age !== null && $user->date_of_birth !== null
            && $user->date_of_birth->age < $course->minimum_age) {
            return back()->withErrors([
                'enrol' => 'This course has a minimum age of '.$course->minimum_age.' years.',
            ]);
        }

        CourseEnrolment::firstOrCreate(
            ['course_id' => $course->getKey(), 'user_id' => $user->getKey()],
            [
                'technical_official_id' => $user->technicalOfficial?->getKey(),
                'organization_id' => $user->current_organization_id,
                'status' => EnrolmentStatus::InProgress,
                'enrolled_at' => now(),
            ],
        );

        return back()->with('success', 'You are enrolled on '.$course->title.'.');
    }

    public function lesson(Request $request, Course $course, CourseLesson $lesson): Response
    {
        $this->authorize('view', $course);

        abort_unless($lesson->course_id === $course->getKey(), 404);

        $enrolment = $this->enrolmentFor($request, $course);
        $lessons = $course->lessons()->where('is_published', true)->get();
        $index = $lessons->search(fn (CourseLesson $item): bool => $item->is($lesson));

        return Inertia::render('academy/lesson', [
            'course' => ['slug' => $course->slug, 'title' => $course->title],
            'lesson' => [
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'summary' => $lesson->summary,
                'content' => $lesson->content,
                'video_url' => $lesson->video_url,
                'runtime' => $lesson->runtime(),
                'position' => $index === false ? 1 : $index + 1,
                'total' => $lessons->count(),
                'completed' => $enrolment !== null && $enrolment->lessonCompletions()
                    ->where('course_lesson_id', $lesson->getKey())->exists(),
            ],
            'next' => $index === false || $index + 1 >= $lessons->count()
                ? null
                : ['slug' => $lessons[$index + 1]->slug, 'title' => $lessons[$index + 1]->title],
            'quiz' => $lesson->quiz()->with('options')->get()->map(fn ($question): array => [
                'id' => $question->getKey(),
                'prompt' => $question->prompt,
                'type' => $question->type->value,
                'explanation' => $question->explanation,
                'options' => $question->options->map(fn ($option): array => [
                    'id' => $option->getKey(),
                    'label' => $option->label,
                    'is_correct' => $option->is_correct,
                ]),
            ]),
            'enrolled' => $enrolment !== null,
        ]);
    }

    public function completeLesson(Request $request, Course $course, CourseLesson $lesson): RedirectResponse
    {
        $enrolment = $this->enrolmentFor($request, $course);

        abort_if($enrolment === null, 403, 'Enrol on this course first.');
        abort_unless($lesson->course_id === $course->getKey(), 404);

        LessonCompletion::firstOrCreate([
            'course_enrolment_id' => $enrolment->getKey(),
            'course_lesson_id' => $lesson->getKey(),
        ], ['completed_at' => now()]);

        return back()->with('success', 'Lesson marked as complete.');
    }

    private function enrolmentFor(Request $request, Course $course): ?CourseEnrolment
    {
        return CourseEnrolment::query()
            ->where('course_id', $course->getKey())
            ->where('user_id', $request->user()->getKey())
            ->first();
    }
}
