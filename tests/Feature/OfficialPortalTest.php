<?php

use App\Enums\OfficialCategory;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Models\ExamAttempt;
use App\Models\Organization;
use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
});

/**
 * Somebody on the officials register with an account, and nothing more.
 */
function officialUser(Organization $club): array
{
    $user = userWithRole(Roles::GALA_OFFICIAL, $club);

    $official = TechnicalOfficial::factory()->create([
        'user_id' => $user->getKey(),
        'organization_id' => $club->getKey(),
        'status' => 'learner',
    ]);

    return [$user, $official];
}

it('sends an official to their own portal rather than the board dashboard', function (): void {
    [$user] = officialUser($this->club);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('portal'));
});

it('leaves an administrator on the board dashboard', function (): void {
    $admin = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);

    TechnicalOfficial::factory()->create([
        'user_id' => $admin->getKey(),
        'organization_id' => $this->club->getKey(),
    ]);

    // They are an official too, but they also run the federation, so the
    // dashboard is still the right landing.
    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/dashboard'));
});

it('shows every exam attempt, the ones that failed included', function (): void {
    [$user, $official] = officialUser($this->club);

    $course = Course::factory()->create([
        'official_category' => OfficialCategory::Timekeeper,
        'is_published' => true,
        'pass_mark' => 80,
        'max_attempts' => 3,
    ]);

    $enrolment = CourseEnrolment::create([
        'course_id' => $course->getKey(),
        'user_id' => $user->getKey(),
        'technical_official_id' => $official->getKey(),
        'enrolled_at' => now(),
    ]);

    foreach ([[1, 50, false], [2, 90, true]] as [$number, $score, $passed]) {
        ExamAttempt::create([
            'course_enrolment_id' => $enrolment->getKey(),
            'course_id' => $course->getKey(),
            'user_id' => $user->getKey(),
            'attempt_number' => $number,
            'status' => 'submitted',
            'question_count' => 10,
            'correct_count' => (int) ($score / 10),
            'score' => $score,
            'passed' => $passed,
            'started_at' => now()->subDays(10 - $number),
            'submitted_at' => now()->subDays(10 - $number),
        ]);
    }

    $this->actingAs($user)
        ->get(route('portal'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/officiating')
            ->count('journey', 1)
            ->count('journey.0.attempts', 2)
            ->where('journey.0.attempts.0.score', 50)
            ->where('journey.0.attempts.0.passed', false)
            ->where('journey.0.attempts.1.score', 90)
            ->where('journey.0.attempts.1.passed', true)
            ->etc());
});

it('gives the holder their certificate as a PDF', function (): void {
    [$user] = officialUser($this->club);

    $certificate = Certificate::factory()->create([
        'user_id' => $user->getKey(),
        'holder_name' => 'Nomsa Sibanda',
        'title' => 'Timekeeper',
    ]);

    $response = $this->actingAs($user)
        ->get(route('certificates.download', $certificate))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->getContent())->toStartWith('%PDF-');
});

it('will not hand somebody else a certificate that is not theirs', function (): void {
    [$holder] = officialUser($this->club);
    $stranger = userWithRole(Roles::SWIMMER, $this->club);

    $certificate = Certificate::factory()->create(['user_id' => $holder->getKey()]);

    $this->actingAs($stranger)
        ->get(route('certificates.download', $certificate))
        ->assertForbidden();
});

it('lets the technical committee reprint one for somebody', function (): void {
    [$holder] = officialUser($this->club);
    $convenor = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $certificate = Certificate::factory()->create(['user_id' => $holder->getKey()]);

    $this->actingAs($convenor)
        ->get(route('certificates.download', $certificate))
        ->assertOk();
});

it('keeps the printable certificate behind a login', function (): void {
    $certificate = Certificate::factory()->create([
        'user_id' => User::factory()->create()->getKey(),
    ]);

    $this->get(route('certificates.download', $certificate))
        ->assertRedirect(route('login'));
});

it('still verifies a certificate publicly, which is what the QR code points at', function (): void {
    $certificate = Certificate::factory()->create(['holder_name' => 'Nomsa Sibanda']);

    $this->get(route('certificates.verify', $certificate))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/certificate')
            ->where('certificate.holder_name', 'Nomsa Sibanda')
            ->etc());
});
