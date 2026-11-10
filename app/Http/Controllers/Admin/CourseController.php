<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Models\CourseLesson;
use App\Models\CourseQuestion;
use App\Models\CourseQuestionOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        $this->authorize('manage', Course::class);

        $courses = Course::query()
            ->withCount(['lessons', 'questions', 'enrolments'])
            ->orderBy('position')
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course): array => [
                'id' => $course->getKey(),
                'slug' => $course->slug,
                'title' => $course->title,
                'category' => $course->official_category?->label(),
                'grade' => $course->awards_grade?->label(),
                'is_published' => $course->is_published,
                'pass_mark' => $course->pass_mark,
                'exam_question_count' => $course->exam_question_count,
                'lessons_count' => $course->lessons_count,
                'questions_count' => $course->questions_count,
                'enrolments_count' => $course->enrolments_count,
                'bank_ready' => $course->hasEnoughQuestions(),
            ]);

        return Inertia::render('admin/academy/index', [
            'courses' => $courses,
            'categories' => OfficialCategory::options(),
            'grades' => OfficialGrade::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Course::class);

        $course = Course::create($this->validated($request));

        return to_route('admin.academy.edit', $course)
            ->with('success', $course->title.' created. Add lessons and questions next.');
    }

    public function edit(Course $course): Response
    {
        $this->authorize('update', $course);

        $course->load(['lessons', 'questions.options', 'questions.lesson']);

        return Inertia::render('admin/academy/form', [
            'course' => [
                'id' => $course->getKey(),
                'slug' => $course->slug,
                'title' => $course->title,
                'official_category' => $course->official_category?->value,
                'awards_grade' => $course->awards_grade?->value,
                'summary' => $course->summary,
                'description' => $course->description,
                'pass_mark' => $course->pass_mark,
                'max_attempts' => $course->max_attempts,
                'exam_question_count' => $course->exam_question_count,
                'exam_minutes' => $course->exam_minutes,
                'practical_sessions_required' => $course->practical_sessions_required,
                'minimum_age' => $course->minimum_age,
                'fee' => $course->fee,
                'is_published' => $course->is_published,
                'bank_ready' => $course->hasEnoughQuestions(),
                'bank_size' => $course->examBank()->count(),
            ],
            'lessons' => $course->lessons->map(fn (CourseLesson $lesson): array => [
                'id' => $lesson->getKey(),
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'position' => $lesson->position,
                'summary' => $lesson->summary,
                'video_url' => $lesson->video_url,
                'is_published' => $lesson->is_published,
            ]),
            'questions' => $course->questions->map(fn (CourseQuestion $question): array => [
                'id' => $question->getKey(),
                'prompt' => $question->prompt,
                'type' => $question->type->value,
                'lesson' => $question->lesson?->title,
                'rule_reference' => $question->rule_reference,
                'in_exam_bank' => $question->in_exam_bank,
                'options' => $question->options->map(fn (CourseQuestionOption $option): array => [
                    'id' => $option->getKey(),
                    'label' => $option->label,
                    'is_correct' => $option->is_correct,
                ]),
            ]),
            'categories' => OfficialCategory::options(),
            'grades' => OfficialGrade::options(),
            'questionTypes' => QuestionType::options(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $course->update($this->validated($request, $course));

        return back()->with('success', $course->title.' updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        $course->delete();

        return to_route('admin.academy.index')->with('success', $course->title.' removed.');
    }

    /**
     * Who is working through this course, and where they have got to.
     */
    public function enrolments(Course $course): Response
    {
        $this->authorize('update', $course);

        $enrolments = $course->enrolments()
            ->with(['user:id,name,first_name,last_name,email', 'technicalOfficial:id,first_name,last_name'])
            ->get()
            ->map(fn (CourseEnrolment $enrolment): array => [
                'id' => $enrolment->getKey(),
                'name' => $enrolment->user->displayName(),
                'email' => $enrolment->user->email,
                'status' => $enrolment->status->value,
                'status_label' => $enrolment->status->label(),
                'progress' => $enrolment->progressPercent(),
                'theory_passed_at' => $enrolment->theory_passed_at?->toDateString(),
                'practical_done' => $enrolment->practical_sessions_completed,
                'practical_required' => $course->practical_sessions_required,
                'ready_for_certificate' => $enrolment->isReadyForCertificate(),
                'attempts_used' => $enrolment->attemptsUsed(),
            ]);

        return Inertia::render('admin/academy/enrolments', [
            'course' => ['slug' => $course->slug, 'title' => $course->title],
            'enrolments' => $enrolments,
            'can' => ['certify' => request()->user()->can('certify', Course::class)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'official_category' => ['nullable', Rule::enum(OfficialCategory::class)],
            'awards_grade' => ['nullable', Rule::enum(OfficialGrade::class)],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'pass_mark' => ['required', 'integer', 'min:1', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'exam_question_count' => ['required', 'integer', 'min:1', 'max:200'],
            'exam_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'practical_sessions_required' => ['required', 'integer', 'min:0', 'max:50'],
            'minimum_age' => ['nullable', 'integer', 'min:8', 'max:99'],
            'fee' => ['required', 'numeric', 'min:0'],
            'is_published' => ['boolean'],
        ]);
    }
}
