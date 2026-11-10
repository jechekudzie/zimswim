<?php

namespace App\Http\Controllers\Academy;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Models\ExamAttempt;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function __construct(private readonly ExamService $exams) {}

    public function start(Request $request, Course $course): RedirectResponse
    {
        $enrolment = $this->enrolment($request, $course);

        $attempt = $this->exams->start($enrolment);

        return to_route('academy.exam.show', [$course, $attempt]);
    }

    public function show(Request $request, Course $course, ExamAttempt $attempt): Response
    {
        abort_unless($attempt->user_id === $request->user()->getKey(), 403);

        $attempt->load('answers.question.options');

        if ($attempt->status !== 'in_progress') {
            return Inertia::render('academy/exam-result', [
                'course' => ['slug' => $course->slug, 'title' => $course->title],
                'attempt' => [
                    'number' => $attempt->attempt_number,
                    'score' => $attempt->score,
                    'passed' => $attempt->passed,
                    'correct' => $attempt->correct_count,
                    'total' => $attempt->question_count,
                    'pass_mark' => $course->pass_mark,
                    'status' => $attempt->status,
                ],
                'review' => $attempt->answers->map(fn ($answer): array => [
                    'prompt' => $answer->question->prompt,
                    'is_correct' => $answer->is_correct,
                    'explanation' => $answer->question->explanation,
                    'rule_reference' => $answer->question->rule_reference,
                ]),
            ]);
        }

        return Inertia::render('academy/exam', [
            'course' => [
                'slug' => $course->slug,
                'title' => $course->title,
                'pass_mark' => $course->pass_mark,
            ],
            'attempt' => [
                'id' => $attempt->getKey(),
                'number' => $attempt->attempt_number,
                'seconds_remaining' => $attempt->secondsRemaining(),
                'total' => $attempt->question_count,
            ],
            // Correct answers are never sent to the browser during an attempt.
            'questions' => $attempt->answers->map(fn ($answer): array => [
                'id' => $answer->course_question_id,
                'position' => $answer->position,
                'prompt' => $answer->question->prompt,
                'type' => $answer->question->type->value,
                'options' => $answer->question->options->map(fn ($option): array => [
                    'id' => $option->getKey(),
                    'label' => $option->label,
                ]),
            ]),
        ]);
    }

    public function submit(Request $request, Course $course, ExamAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->getKey(), 403);

        $validated = $request->validate([
            'answers' => ['present', 'array'],
            'answers.*' => ['array'],
            'answers.*.*' => ['integer'],
        ]);

        $this->exams->submit($attempt, $validated['answers']);

        return to_route('academy.exam.show', [$course, $attempt])
            ->with('success', 'Exam submitted.');
    }

    private function enrolment(Request $request, Course $course): CourseEnrolment
    {
        return CourseEnrolment::query()
            ->where('course_id', $course->getKey())
            ->where('user_id', $request->user()->getKey())
            ->firstOrFail();
    }
}
