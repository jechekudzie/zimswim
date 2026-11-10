<?php

use App\Enums\EnrolmentStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Models\CourseLesson;
use App\Models\CourseQuestion;
use App\Models\ExamAttempt;
use App\Models\LessonCompletion;
use App\Models\User;
use App\Services\CertificateIssuer;
use App\Services\ExamService;
use App\Support\Roles;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();

    $this->course = Course::factory()->create([
        'exam_question_count' => 4,
        'pass_mark' => 75,
        'max_attempts' => 2,
        'practical_sessions_required' => 2,
    ]);

    $this->lesson = CourseLesson::factory()->create([
        'course_id' => $this->course->getKey(),
    ]);

    CourseQuestion::factory()->count(8)->withOptions()->create([
        'course_id' => $this->course->getKey(),
    ]);

    $this->learner = User::factory()->create([
        'current_organization_id' => $this->club->getKey(),
        'date_of_birth' => now()->subYears(40)->toDateString(),
    ]);
    $this->learner->organizations()->attach($this->club->getKey());
});

function enrolLearner($context): CourseEnrolment
{
    return CourseEnrolment::factory()->create([
        'course_id' => $context->course->getKey(),
        'user_id' => $context->learner->getKey(),
    ]);
}

function completeLessons(CourseEnrolment $enrolment): void
{
    foreach ($enrolment->course->lessons as $lesson) {
        LessonCompletion::firstOrCreate([
            'course_enrolment_id' => $enrolment->getKey(),
            'course_lesson_id' => $lesson->getKey(),
        ], ['completed_at' => now()]);
    }
}

function answersFor(ExamAttempt $attempt, bool $correct): array
{
    $attempt->load('answers.question.options');

    return $attempt->answers->mapWithKeys(function ($answer) use ($correct): array {
        $options = $answer->question->options;
        $option = $correct
            ? $options->firstWhere('is_correct', true)
            : $options->firstWhere('is_correct', false);

        return [$answer->course_question_id => [$option->getKey()]];
    })->all();
}

it('enrols a user on a published course', function (): void {
    $this->actingAs($this->learner)
        ->from(route('academy.show', $this->course))
        ->post(route('academy.enrol', $this->course))
        ->assertRedirect();

    expect(CourseEnrolment::where('user_id', $this->learner->getKey())->exists())->toBeTrue();
});

it('refuses an exam before every lesson is complete', function (): void {
    $enrolment = enrolLearner($this);

    expect(fn () => app(ExamService::class)->start($enrolment))
        ->toThrow(ValidationException::class);
});

it('draws a random set of questions for each attempt', function (): void {
    $enrolment = enrolLearner($this);
    completeLessons($enrolment);

    $attempt = app(ExamService::class)->start($enrolment->fresh());

    expect($attempt->question_count)->toBe(4)
        ->and($attempt->answers()->count())->toBe(4)
        ->and($attempt->status)->toBe('in_progress');
});

it('passes a learner who answers enough questions correctly', function (): void {
    $enrolment = enrolLearner($this);
    completeLessons($enrolment);

    $service = app(ExamService::class);
    $attempt = $service->start($enrolment->fresh());
    $graded = $service->submit($attempt, answersFor($attempt, true));

    expect($graded->score)->toBe(100)
        ->and($graded->passed)->toBeTrue()
        ->and($enrolment->fresh()->hasPassedTheory())->toBeTrue()
        ->and($enrolment->fresh()->status)->toBe(EnrolmentStatus::AwaitingPractical);
});

it('fails a learner who answers wrongly and leaves the theory unpassed', function (): void {
    $enrolment = enrolLearner($this);
    completeLessons($enrolment);

    $service = app(ExamService::class);
    $attempt = $service->start($enrolment->fresh());
    $graded = $service->submit($attempt, answersFor($attempt, false));

    expect($graded->score)->toBe(0)
        ->and($graded->passed)->toBeFalse()
        ->and($enrolment->fresh()->hasPassedTheory())->toBeFalse();
});

it('stops a learner after the allowed number of attempts', function (): void {
    $enrolment = enrolLearner($this);
    completeLessons($enrolment);
    $service = app(ExamService::class);

    foreach (range(1, 2) as $ignored) {
        $attempt = $service->start($enrolment->fresh());
        $service->submit($attempt, answersFor($attempt, false));
    }

    expect($enrolment->fresh()->attemptsRemaining())->toBe(0)
        ->and(fn () => $service->start($enrolment->fresh()))
        ->toThrow(ValidationException::class);
});

it('marks an attempt that ran out of time as expired and unpassed', function (): void {
    $enrolment = enrolLearner($this);
    completeLessons($enrolment);

    $service = app(ExamService::class);
    $attempt = $service->start($enrolment->fresh());
    $attempt->forceFill(['expires_at' => now()->subMinute()])->save();

    $graded = $service->submit($attempt->fresh(), answersFor($attempt, true));

    expect($graded->status)->toBe('expired')
        ->and($graded->passed)->toBeFalse();
});

it('will not certify until the practical sessions are signed off', function (): void {
    $enrolment = enrolLearner($this);
    completeLessons($enrolment);

    $service = app(ExamService::class);
    $attempt = $service->start($enrolment->fresh());
    $service->submit($attempt, answersFor($attempt, true));

    expect(fn () => app(CertificateIssuer::class)->issue($enrolment->fresh()))
        ->toThrow(ValidationException::class);
});

it('issues a verifiable certificate once theory and practicals are done', function (): void {
    $enrolment = enrolLearner($this);
    completeLessons($enrolment);

    $service = app(ExamService::class);
    $attempt = $service->start($enrolment->fresh());
    $service->submit($attempt, answersFor($attempt, true));

    $enrolment->fresh()->forceFill(['practical_sessions_completed' => 2])->save();

    $certificate = app(CertificateIssuer::class)->issue($enrolment->fresh());

    expect($certificate->isValid())->toBeTrue()
        ->and($certificate->verification_code)->toStartWith('ZSW-');

    $this->get(route('certificates.verify', $certificate))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/certificate')
            ->where('certificate.valid', true)
            ->where('certificate.holder_name', $certificate->holder_name));
});

it('shows an expired certificate as no longer valid', function (): void {
    $certificate = Certificate::factory()->expired()->create();

    $this->get(route('certificates.verify', $certificate))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('certificate.valid', false)
            ->where('certificate.expired', true));
});

it('does not let a club administrator issue a certificate', function (): void {
    $enrolment = enrolLearner($this);
    $clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);

    $this->actingAs($clubAdmin)
        ->post(route('admin.academy.certificate.issue', [$this->course, $enrolment]))
        ->assertForbidden();
});

it('keeps draft courses out of the Academy listing', function (): void {
    Course::factory()->draft()->create(['title' => 'Referee draft']);

    $this->actingAs($this->learner)
        ->get(route('academy.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academy/index')
            ->count('courses', 1));
});
