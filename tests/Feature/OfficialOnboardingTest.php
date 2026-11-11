<?php

use App\Enums\OfficialApplicationStatus;
use App\Enums\OfficialCategory;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Models\OfficialApplication;
use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Notifications\OfficialApplicationApproved;
use App\Notifications\OfficialApplicationDeclined;
use App\Notifications\OfficialApplicationReceived;
use App\Support\Roles;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->reviewer = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);
});

function applicationPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Tendai',
        'last_name' => 'Moyo',
        'email' => 'tendai@example.com',
        'phone' => '+263 77 123 4567',
        'date_of_birth' => now()->subYears(34)->toDateString(),
        'category' => OfficialCategory::Timekeeper->value,
        ...$overrides,
    ];
}

it('takes an application from somebody with no account', function (): void {
    Notification::fake();

    $this->post(route('officials.apply.store'), applicationPayload([
        'organization_id' => $this->club->getKey(),
        'province_organization_id' => $this->province->getKey(),
    ]))->assertRedirect(route('officials.applied'));

    $application = OfficialApplication::sole();

    expect($application->status)->toBe(OfficialApplicationStatus::Submitted)
        ->and($application->reference)->toStartWith('OFF-')
        ->and($application->category)->toBe(OfficialCategory::Timekeeper);

    Notification::assertSentOnDemand(OfficialApplicationReceived::class);
});

it('refuses a category that has to be earned first', function (): void {
    $this->post(route('officials.apply.store'), applicationPayload([
        'category' => OfficialCategory::Referee->value,
    ]))->assertSessionHasErrors('category');

    expect(OfficialApplication::count())->toBe(0);
});

it('refuses somebody below the minimum age for the category they picked', function (): void {
    $this->post(route('officials.apply.store'), applicationPayload([
        'date_of_birth' => now()->subYears(11)->toDateString(),
    ]))->assertSessionHasErrors('date_of_birth');

    expect(OfficialApplication::count())->toBe(0);
});

it('will not take a second application while one is still open', function (): void {
    Notification::fake();

    $this->post(route('officials.apply.store'), applicationPayload());
    $this->post(route('officials.apply.store'), applicationPayload())
        ->assertSessionHasErrors('email');

    expect(OfficialApplication::count())->toBe(1);
});

it('opens an account, a register entry and an enrolment when it is approved', function (): void {
    Notification::fake();

    $course = Course::factory()->create([
        'official_category' => OfficialCategory::Timekeeper,
        'is_published' => true,
    ]);

    $application = OfficialApplication::factory()
        ->forClub($this->club)
        ->create(['email' => 'tendai@example.com']);

    $this->actingAs($this->reviewer)
        ->post(route('admin.official-applications.approve', $application))
        ->assertRedirect();

    $application->refresh();
    $user = User::where('email', 'tendai@example.com')->sole();
    $official = TechnicalOfficial::where('user_id', $user->getKey())->sole();

    expect($application->status)->toBe(OfficialApplicationStatus::Approved)
        ->and($application->user_id)->toBe($user->getKey())
        ->and($application->technical_official_id)->toBe($official->getKey())
        ->and($official->organization_id)->toBe($this->club->getKey())
        ->and($official->status)->toBe('learner')
        ->and($official->registration_number)->toStartWith('TO-')
        ->and(CourseEnrolment::where('user_id', $user->getKey())
            ->where('course_id', $course->getKey())
            ->exists())->toBeTrue();

    Notification::assertSentTo($user, AccountCreated::class);
    Notification::assertSentTo($user, OfficialApplicationApproved::class);
});

it('keeps one login for somebody who already has an account', function (): void {
    Notification::fake();

    $existing = User::factory()->create(['email' => 'coach@example.com']);

    $application = OfficialApplication::factory()
        ->forClub($this->club)
        ->create(['email' => 'coach@example.com']);

    $this->actingAs($this->reviewer)
        ->post(route('admin.official-applications.approve', $application));

    expect(User::where('email', 'coach@example.com')->count())->toBe(1)
        ->and($application->fresh()->user_id)->toBe($existing->getKey());

    // They already know their password, so no invitation is posted.
    Notification::assertNotSentTo($existing, AccountCreated::class);
});

it('refuses to approve an applicant whose club is not on the register', function (): void {
    $application = OfficialApplication::factory()->create([
        'club_name_given' => 'A club nobody has heard of',
    ]);

    $this->actingAs($this->reviewer)
        ->post(route('admin.official-applications.approve', $application))
        ->assertSessionHasErrors('organization_id');

    expect($application->fresh()->status)->toBe(OfficialApplicationStatus::Submitted);
});

it('tells a declined applicant why, in the reviewer\'s own words', function (): void {
    Notification::fake();

    $application = OfficialApplication::factory()->forClub($this->club)->create();

    $this->actingAs($this->reviewer)
        ->post(route('admin.official-applications.decline', $application), [
            'review_notes' => 'Your club is not affiliated for this season.',
        ])->assertRedirect();

    expect($application->fresh())
        ->status->toBe(OfficialApplicationStatus::Declined)
        ->review_notes->toBe('Your club is not affiliated for this season.');

    Notification::assertSentOnDemand(OfficialApplicationDeclined::class);
});

it('will not decline without a reason', function (): void {
    $application = OfficialApplication::factory()->forClub($this->club)->create();

    $this->actingAs($this->reviewer)
        ->post(route('admin.official-applications.decline', $application), [
            'review_notes' => 'no',
        ])->assertSessionHasErrors('review_notes');

    expect($application->fresh()->status)->toBe(OfficialApplicationStatus::Submitted);
});

it('will not let the same application be decided twice', function (): void {
    Notification::fake();

    $application = OfficialApplication::factory()->forClub($this->club)->approved()->create();

    $this->actingAs($this->reviewer)
        ->post(route('admin.official-applications.approve', $application))
        ->assertSessionHasErrors('status');
});

it('keeps the review queue away from somebody who cannot manage officials', function (): void {
    $swimmer = userWithRole(Roles::SWIMMER, $this->club);

    $this->actingAs($swimmer)
        ->get(route('admin.official-applications.index'))
        ->assertForbidden();
});

it('starts the form on the right step when a course sends somebody to it', function (): void {
    $this->get(route('officials.apply', ['category' => OfficialCategory::Timekeeper->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('preselected', OfficialCategory::Timekeeper->value)
            ->etc());
});

it('will not let a link pre-select a category that has to be earned', function (): void {
    // Otherwise the enrol button on a later course would seat somebody in a
    // form that the server is bound to refuse.
    $this->get(route('officials.apply', ['category' => OfficialCategory::Referee->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('preselected', null)->etc());
});

it('ignores a category that does not exist', function (): void {
    $this->get(route('officials.apply', ['category' => 'water-boy']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('preselected', null)->etc());
});

it('publishes each course with its modules named, not just counted', function (): void {
    $course = Course::factory()->create([
        'official_category' => OfficialCategory::Timekeeper,
        'is_published' => true,
    ]);

    $course->lessons()->create([
        'title' => 'Starting and stopping the watch',
        'slug' => 'starting-and-stopping-the-watch',
        'position' => 0,
        'video_seconds' => 540,
        'is_published' => true,
    ]);

    $this->get(route('officials'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('courses.0.lessons.0.title', 'Starting and stopping the watch')
            ->where('courses.0.lessons.0.minutes', 9)
            ->where('courses.0.is_entry', true)
            ->etc());
});
