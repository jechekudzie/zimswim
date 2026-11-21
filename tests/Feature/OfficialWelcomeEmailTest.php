<?php

use App\Enums\OfficialCategory;
use App\Models\Course;
use App\Models\OfficialApplication;
use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Notifications\OfficialWelcome;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();

    $this->user = User::factory()->create(['email' => 'tendai@example.com']);

    $this->application = OfficialApplication::factory()
        ->forClub($this->club)
        ->approved()
        ->create([
            'first_name' => 'Tendai',
            'last_name' => 'Moyo',
            'email' => 'tendai@example.com',
            'category' => OfficialCategory::Timekeeper,
        ]);

    $this->official = TechnicalOfficial::factory()->create([
        'registration_number' => 'TO-0042',
        'user_id' => $this->user->getKey(),
        'organization_id' => $this->club->getKey(),
        'status' => 'learner',
    ]);
});

/**
 * The rendered email, as the official would receive it.
 */
function welcomeHtml(OfficialWelcome $notification, User $user): string
{
    return (string) $notification->toMail($user)->render();
}

it('carries everything a new official needs in one email', function (): void {
    $course = Course::factory()->create([
        'title' => 'Timekeeping, Grade One',
        'official_category' => OfficialCategory::Timekeeper,
        'pass_mark' => 80,
        'max_attempts' => 3,
        'exam_question_count' => 20,
        'exam_minutes' => 45,
        'practical_sessions_required' => 6,
    ]);

    $html = welcomeHtml(
        new OfficialWelcome($this->application, $this->official, $course, accountIsNew: true),
        $this->user,
    );

    expect($html)
        // Who they are on the register.
        ->toContain('TO-0042')
        ->toContain('Tendai')
        ->toContain('Timekeeper')
        ->toContain($this->club->name)
        ->toContain($this->province->name)
        ->toContain('Learner')
        // What they are working towards.
        ->toContain('Timekeeping, Grade One')
        ->toContain('Officiate 6 sessions as a learner')
        ->toContain('Collect your accreditation')
        // The exam in figures.
        ->toContain('20')
        ->toContain('45')
        ->toContain('80%')
        // Where to work from.
        ->toContain(route('portal'))
        ->toContain(route('academy.show', $course))
        ->toContain(route('officials'))
        // And the reference they quoted while they waited.
        ->toContain($this->application->reference);
});

it('leads a brand-new account to set a password rather than to sign in', function (): void {
    $notification = new OfficialWelcome($this->application, $this->official, null, accountIsNew: true);

    $mail = $notification->toMail($this->user);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('Welcome to the officials register — TO-0042')
        ->and($html)->toContain('reset-password/')
        ->and($html)->toContain(urlencode('tendai@example.com'))
        ->and($html)->toContain('Set your password')
        ->and($html)->toContain('good for sixty minutes');
});

it('sends somebody who already had an account straight to their portal', function (): void {
    $html = welcomeHtml(
        new OfficialWelcome($this->application, $this->official, null, accountIsNew: false),
        $this->user,
    );

    // A coach or parent already here keeps the password they have, and must
    // not be handed a reset link they never asked for.
    expect($html)
        ->toContain('Open your portal')
        ->not->toContain('reset-password/')
        ->not->toContain('good for sixty minutes');
});

it('points a category with no course at the next clinic instead', function (): void {
    $notification = new OfficialWelcome($this->application, $this->official, null, accountIsNew: true);

    expect($notification->exam())->toBeNull();

    $html = welcomeHtml($notification, $this->user);

    expect($html)
        ->toContain('Wait for the next clinic')
        // The category's own floor stands in for a course that is not there.
        ->toContain('Officiate 6 sessions as a learner')
        // There is no exam to pass, so the last step must not wait on one.
        ->not->toContain('Once the exam is passed')
        ->toContain(route('academy.index'));
});

it('leaves the exam step out for a category that sits no exam', function (): void {
    $this->application->update(['category' => OfficialCategory::Announcer]);

    $course = Course::factory()->create([
        'title' => 'Announcing',
        'official_category' => OfficialCategory::Announcer,
    ]);

    $notification = new OfficialWelcome(
        $this->application->fresh(),
        $this->official,
        $course,
        accountIsNew: true,
    );

    expect($notification->exam())->toBeNull()
        ->and(collect($notification->steps())->pluck('title'))
        ->not->toContain('Sit the exam');

    expect(welcomeHtml($notification, $this->user))->not->toContain('Sit the exam');
});

it('is the only notice an approval sends, and reaches the bell too', function (): void {
    $notification = new OfficialWelcome($this->application, $this->official, null, accountIsNew: true);

    expect($notification->via($this->user))->toContain('mail', 'database')
        ->and($notification->toArray($this->user))
        ->toMatchArray(['kind' => 'accreditation'])
        ->and($notification->toArray($this->user)['title'])->toContain('TO-0042')
        // The stored copy is read by somebody already signed in, so it must
        // not carry the single-use password link.
        ->and($notification->toArray($this->user)['url'])->toBe(route('portal'));
});
