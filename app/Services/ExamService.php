<?php

namespace App\Services;

use App\Enums\EnrolmentStatus;
use App\Models\CourseEnrolment;
use App\Models\CourseQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Builds and marks Academy exams. Each attempt draws its questions at random
 * from the course bank, so no two exams are identical.
 */
class ExamService
{
    public function start(CourseEnrolment $enrolment): ExamAttempt
    {
        $course = $enrolment->course;

        if (! $enrolment->hasCompletedAllLessons()) {
            throw ValidationException::withMessages([
                'exam' => 'Work through every lesson before sitting the exam.',
            ]);
        }

        if ($enrolment->hasPassedTheory()) {
            throw ValidationException::withMessages([
                'exam' => 'You have already passed this exam.',
            ]);
        }

        if ($enrolment->attemptsRemaining() < 1) {
            throw ValidationException::withMessages([
                'exam' => 'No attempts remain. Speak to the technical officials convenor.',
            ]);
        }

        if (! $course->hasEnoughQuestions()) {
            throw ValidationException::withMessages([
                'exam' => 'This exam is not ready yet; the question bank is too small.',
            ]);
        }

        $open = $enrolment->attempts()->where('status', 'in_progress')->latest('id')->first();

        if ($open !== null && $open->isOpen()) {
            return $open;
        }

        return DB::transaction(function () use ($enrolment, $course): ExamAttempt {
            $questions = $course->examBank()
                ->inRandomOrder()
                ->limit($course->exam_question_count)
                ->get();

            $attempt = ExamAttempt::create([
                'course_enrolment_id' => $enrolment->getKey(),
                'course_id' => $course->getKey(),
                'user_id' => $enrolment->user_id,
                'attempt_number' => $enrolment->attempts()->count() + 1,
                'status' => 'in_progress',
                'question_count' => $questions->count(),
                'started_at' => now(),
                'expires_at' => now()->addMinutes($course->exam_minutes),
            ]);

            foreach ($questions->values() as $position => $question) {
                ExamAttemptAnswer::create([
                    'exam_attempt_id' => $attempt->getKey(),
                    'course_question_id' => $question->getKey(),
                    'position' => $position + 1,
                ]);
            }

            return $attempt->fresh();
        });
    }

    /**
     * Mark an attempt. Answers are supplied as question id => selected option ids.
     *
     * @param  array<int|string, array<int, int|string>>  $answers
     */
    public function submit(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'exam' => 'This attempt has already been submitted.',
            ]);
        }

        $expired = $attempt->hasExpired();

        return DB::transaction(function () use ($attempt, $answers, $expired): ExamAttempt {
            $attempt->load('answers.question.options');
            $correct = 0;

            foreach ($attempt->answers as $answer) {
                $selected = array_map('intval', $answers[$answer->course_question_id] ?? []);
                $isCorrect = ! $expired
                    && $answer->question instanceof CourseQuestion
                    && $answer->question->isAnsweredCorrectlyBy($selected);

                $answer->forceFill([
                    'selected_option_ids' => $selected,
                    'is_correct' => $isCorrect,
                ])->save();

                $correct += $isCorrect ? 1 : 0;
            }

            $course = $attempt->course;
            $score = $attempt->question_count === 0
                ? 0
                : (int) round(($correct / $attempt->question_count) * 100);
            $passed = ! $expired && $score >= $course->pass_mark;

            $attempt->forceFill([
                'status' => $expired ? 'expired' : 'submitted',
                'correct_count' => $correct,
                'score' => $score,
                'passed' => $passed,
                'submitted_at' => now(),
            ])->save();

            if ($passed) {
                $this->markTheoryPassed($attempt->enrolment);
            }

            return $attempt->fresh();
        });
    }

    private function markTheoryPassed(CourseEnrolment $enrolment): void
    {
        $enrolment->forceFill([
            'theory_passed_at' => now(),
            'status' => $enrolment->outstandingPracticalSessions() === 0
                ? EnrolmentStatus::TheoryPassed
                : EnrolmentStatus::AwaitingPractical,
        ])->save();
    }
}
