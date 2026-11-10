<?php

use App\Console\Commands\SendAccreditationReminders;
use App\Console\Commands\SendEntryDeadlineReminders;
use App\Console\Commands\SendRenewalReminders;
use App\Enums\AccreditationStatus;
use App\Enums\Course;
use App\Enums\GalaStatus;
use App\Enums\MemberStatus;
use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\MemberRegistration;
use App\Models\OfficialAccreditation;
use App\Models\Organization;
use App\Models\Result;
use App\Models\Season;
use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\AccreditationExpiring;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\EntryDeadlineApproaching;
use App\Notifications\GalaSanctioned;
use App\Notifications\HeatSheetPublished;
use App\Notifications\RegistrationRenewalDue;
use App\Notifications\ResultsPublished;
use App\Support\Roles;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->season = Season::factory()->create(['is_current' => true, 'age_up_date' => '2026-12-31']);
    $this->venue = Venue::factory()->create(['lanes' => 8]);

    $this->clubAdmin = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);
    $this->provinceAdmin = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $this->province);

    $this->gala = Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'sanctioning_organization_id' => $this->national->getKey(),
        'venue_id' => $this->venue->getKey(),
        'course' => Course::LongCourse,
        'status' => GalaStatus::EntriesOpen,
        'entries_close_at' => now()->addDays(7),
        'age_up_date' => '2026-12-31',
    ]);
});

it('reminds clubs seven days before entries close', function (): void {
    $this->artisan(SendEntryDeadlineReminders::class)->assertSuccessful();

    Notification::assertSentTo(
        $this->clubAdmin,
        EntryDeadlineApproaching::class,
        fn (EntryDeadlineApproaching $notification): bool => $notification->daysLeft === 7
            && $notification->gala->is($this->gala),
    );
});

it('does not remind on a day that is not a milestone', function (): void {
    $this->gala->update(['entries_close_at' => now()->addDays(9)]);

    $this->artisan(SendEntryDeadlineReminders::class)->assertSuccessful();

    Notification::assertNothingSent();
});

it('does not remind about a gala whose entries are not open', function (): void {
    $this->gala->update(['status' => GalaStatus::Draft]);

    $this->artisan(SendEntryDeadlineReminders::class)->assertSuccessful();

    Notification::assertNothingSent();
});

it('sends nothing on a dry run', function (): void {
    $this->artisan(SendEntryDeadlineReminders::class, ['--dry-run' => true])->assertSuccessful();

    Notification::assertNothingSent();
});

it('sends the entry deadline notice by email and text', function (): void {
    $this->artisan(SendEntryDeadlineReminders::class)->assertSuccessful();

    Notification::assertSentTo(
        $this->clubAdmin,
        EntryDeadlineApproaching::class,
        function (EntryDeadlineApproaching $notification, array $channels): bool {
            return in_array('mail', $channels, true)
                && in_array(SmsChannel::class, $channels, true);
        },
    );
});

it('keeps a deadline notice to email when SMS is switched off', function (): void {
    config()->set('sms.enabled', false);

    $this->artisan(SendEntryDeadlineReminders::class)->assertSuccessful();

    Notification::assertSentTo(
        $this->clubAdmin,
        EntryDeadlineApproaching::class,
        fn (EntryDeadlineApproaching $notification, array $channels): bool => $channels === ['mail'],
    );
});

it('tells a club about swimmers not yet registered for the season, in one message', function (): void {
    Member::factory()->count(3)->create([
        'organization_id' => $this->club->getKey(),
        'status' => MemberStatus::Active,
    ]);

    $registered = Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'status' => MemberStatus::Active,
    ]);

    MemberRegistration::factory()->create([
        'member_id' => $registered->getKey(),
        'season_id' => $this->season->getKey(),
        'organization_id' => $this->club->getKey(),
    ]);

    $this->artisan(SendRenewalReminders::class)->assertSuccessful();

    Notification::assertSentToTimes($this->clubAdmin, RegistrationRenewalDue::class, 1);
    Notification::assertSentTo(
        $this->clubAdmin,
        RegistrationRenewalDue::class,
        fn (RegistrationRenewalDue $notification): bool => count($notification->swimmers) === 3,
    );
});

it('does not chase a club whose swimmers are all registered', function (): void {
    $member = Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'status' => MemberStatus::Active,
    ]);

    MemberRegistration::factory()->create([
        'member_id' => $member->getKey(),
        'season_id' => $this->season->getKey(),
        'organization_id' => $this->club->getKey(),
    ]);

    $this->artisan(SendRenewalReminders::class)->assertSuccessful();

    Notification::assertNothingSent();
});

it('climbs to the province when a club has nobody who can register swimmers', function (): void {
    $orphanClub = Organization::factory()->club($this->province)->create();

    Member::factory()->create([
        'organization_id' => $orphanClub->getKey(),
        'status' => MemberStatus::Active,
    ]);

    $this->artisan(SendRenewalReminders::class)->assertSuccessful();

    Notification::assertSentTo($this->provinceAdmin, RegistrationRenewalDue::class);
});

it('warns an official whose accreditation lapses in thirty days', function (): void {
    $user = User::factory()->create(['phone' => '0771234567']);

    $official = TechnicalOfficial::factory()->create([
        'organization_id' => $this->club->getKey(),
        'user_id' => $user->getKey(),
    ]);

    OfficialAccreditation::factory()->create([
        'technical_official_id' => $official->getKey(),
        'category' => OfficialCategory::Timekeeper,
        'grade' => OfficialGrade::National,
        'status' => AccreditationStatus::Active,
        'expires_on' => now()->addDays(30)->toDateString(),
    ]);

    $this->artisan(SendAccreditationReminders::class)->assertSuccessful();

    Notification::assertSentTo(
        $user,
        AccreditationExpiring::class,
        fn (AccreditationExpiring $notification): bool => $notification->daysLeft === 30,
    );
});

it('writes to an official with no login on the address in the register', function (): void {
    $official = TechnicalOfficial::factory()->create([
        'organization_id' => $this->club->getKey(),
        'user_id' => null,
        'email' => 'craig@example.test',
    ]);

    OfficialAccreditation::factory()->create([
        'technical_official_id' => $official->getKey(),
        'category' => OfficialCategory::JudgeOfStroke,
        'grade' => OfficialGrade::National,
        'status' => AccreditationStatus::Active,
        'expires_on' => now()->addDays(90)->toDateString(),
    ]);

    $this->artisan(SendAccreditationReminders::class)->assertSuccessful();

    Notification::assertSentOnDemand(
        AccreditationExpiring::class,
        fn (AccreditationExpiring $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'craig@example.test',
    );
});

it('does not warn about an accreditation that has already lapsed', function (): void {
    $official = TechnicalOfficial::factory()->create([
        'organization_id' => $this->club->getKey(),
        'user_id' => User::factory()->create()->getKey(),
    ]);

    OfficialAccreditation::factory()->create([
        'technical_official_id' => $official->getKey(),
        'status' => AccreditationStatus::Lapsed,
        'expires_on' => now()->addDays(30)->toDateString(),
    ]);

    $this->artisan(SendAccreditationReminders::class)->assertSuccessful();

    Notification::assertNothingSent();
});

it('tells the host when the board sanctions their gala', function (): void {
    $this->gala->update(['status' => GalaStatus::Submitted, 'is_published' => false]);
    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->post(route('admin.galas.sanction', $this->gala), ['decision' => 'sanction'])
        ->assertRedirect();

    Notification::assertSentTo($this->provinceAdmin, GalaSanctioned::class);
});

it('tells nobody when the board returns a gala for changes', function (): void {
    $this->gala->update(['status' => GalaStatus::Submitted]);
    $board = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $this->actingAs($board)
        ->post(route('admin.galas.sanction', $this->gala), [
            'decision' => 'reject',
            'notes' => 'Book a graded pool first.',
        ])
        ->assertRedirect();

    Notification::assertNotSentTo($this->provinceAdmin, GalaSanctioned::class);
});

it('tells the clubs with entries when the heat sheet is seeded', function (): void {
    $event = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gender' => 'girls',
        'stroke' => Stroke::Freestyle,
        'distance' => 50,
    ]);

    $member = Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'status' => MemberStatus::Active,
    ]);

    GalaEntry::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gala_event_id' => $event->getKey(),
        'member_id' => $member->getKey(),
        'organization_id' => $this->club->getKey(),
        'status' => 'entered',
    ]);

    $referee = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($referee)
        ->post(route('admin.galas.seed', $this->gala), [])
        ->assertRedirect();

    Notification::assertSentTo($this->clubAdmin, HeatSheetPublished::class);
});

it('tells the clubs in an event when its results are published', function (): void {
    $event = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gender' => 'girls',
        'stroke' => Stroke::Freestyle,
        'distance' => 50,
    ]);

    $member = Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'status' => MemberStatus::Active,
    ]);

    Result::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gala_event_id' => $event->getKey(),
        'member_id' => $member->getKey(),
        'organization_id' => $this->club->getKey(),
        'time_ms' => 31_000,
        'status' => 'official',
    ]);

    $referee = userWithRole(Roles::TECHNICAL_COMMITTEE, $this->national);

    $this->actingAs($referee)
        ->post(route('admin.galas.results.publish', [$this->gala, $event]))
        ->assertRedirect();

    Notification::assertSentTo(
        $this->clubAdmin,
        ResultsPublished::class,
        fn (ResultsPublished $notification): bool => $notification->eventNumber === $event->event_number,
    );
});
