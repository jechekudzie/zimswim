<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseQuestion;
use App\Models\CourseQuestionOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Lessons and the exam question bank for a course.
 */
class CourseContentController extends Controller
{
    public function storeLesson(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string', 'max:50000'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'video_seconds' => ['nullable', 'integer', 'min:0', 'max:36000'],
            'is_published' => ['boolean'],
        ]);

        CourseLesson::create([
            ...$validated,
            'course_id' => $course->getKey(),
            'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(4)),
            'position' => (int) $course->lessons()->max('position') + 1,
        ]);

        return back()->with('success', 'Lesson added.');
    }

    public function updateLesson(Request $request, Course $course, CourseLesson $lesson): RedirectResponse
    {
        $this->authorize('update', $course);

        $lesson->update($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string', 'max:50000'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'position' => ['required', 'integer', 'min:0'],
            'is_published' => ['boolean'],
        ]));

        return back()->with('success', 'Lesson updated.');
    }

    public function destroyLesson(Course $course, CourseLesson $lesson): RedirectResponse
    {
        $this->authorize('update', $course);

        $lesson->delete();

        return back()->with('success', 'Lesson removed.');
    }

    /**
     * Add a question and its options. A question is only usable when exactly
     * one option is correct, or several for a multiple-answer question.
     */
    public function storeQuestion(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'course_lesson_id' => [
                'nullable', 'integer',
                Rule::exists('course_lessons', 'id')->where('course_id', $course->getKey()),
            ],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'rule_reference' => ['nullable', 'string', 'max:40'],
            'in_exam_bank' => ['boolean'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*.label' => ['required', 'string', 'max:500'],
            'options.*.is_correct' => ['boolean'],
        ]);

        /** @var array<int, array{label: string, is_correct?: bool}> $submitted */
        $submitted = $validated['options'];
        $correct = array_filter($submitted, fn (array $option): bool => (bool) ($option['is_correct'] ?? false));
        $type = QuestionType::from($validated['type']);

        if ($correct === []) {
            throw ValidationException::withMessages(['options' => 'Mark at least one option as correct.']);
        }

        if (! $type->allowsManyAnswers() && count($correct) > 1) {
            throw ValidationException::withMessages([
                'options' => 'Only a multiple-answer question may have more than one correct option.',
            ]);
        }

        DB::transaction(function () use ($validated, $course): void {
            $question = CourseQuestion::create([
                'course_id' => $course->getKey(),
                'course_lesson_id' => $validated['course_lesson_id'] ?? null,
                'prompt' => $validated['prompt'],
                'type' => $validated['type'],
                'explanation' => $validated['explanation'] ?? null,
                'rule_reference' => $validated['rule_reference'] ?? null,
                'in_exam_bank' => $validated['in_exam_bank'] ?? true,
                'is_active' => true,
            ]);

            foreach (array_values($validated['options']) as $position => $option) {
                CourseQuestionOption::create([
                    'course_question_id' => $question->getKey(),
                    'label' => $option['label'],
                    'is_correct' => (bool) ($option['is_correct'] ?? false),
                    'position' => $position,
                ]);
            }
        });

        return back()->with('success', 'Question added to the bank.');
    }

    public function destroyQuestion(Course $course, CourseQuestion $question): RedirectResponse
    {
        $this->authorize('update', $course);

        $question->delete();

        return back()->with('success', 'Question removed.');
    }
}
