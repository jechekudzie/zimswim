<?php

use App\Models\Gala;
use App\Models\Season;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Notifications\GalaSanctioned;
use App\Support\Roles;
use Illuminate\Support\Facades\Notification as NotificationFake;
use Illuminate\Support\Str;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->user = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);
});

it('writes a notice to the database as well as sending it', function (): void {
    $gala = Gala::factory()->sanctioned()->create([
        'season_id' => Season::factory()->create()->getKey(),
        'host_organization_id' => $this->province->getKey(),
    ]);

    $this->user->notify(new GalaSanctioned($gala));

    $notification = $this->user->notifications()->sole();

    expect($notification->data)
        ->toHaveKey('kind', 'gala')
        ->toHaveKey('title', $gala->name.' has been sanctioned')
        ->and($notification->data['url'])->toContain('/admin/galas/'.$gala->slug);
});

it('shows the unread tally to the person signed in', function (): void {
    $this->user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => GalaSanctioned::class,
        'data' => ['kind' => 'gala', 'title' => 'A gala was sanctioned', 'message' => null, 'url' => null],
        'read_at' => null,
    ]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('notifications.unread', 1)
            ->where('notifications.items.0.title', 'A gala was sanctioned')
            ->where('notifications.items.0.read', false)
            ->etc());
});

it('marks a notice read and sends the reader to what it was about', function (): void {
    $notification = $this->user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => GalaSanctioned::class,
        'data' => ['kind' => 'gala', 'title' => 'A gala was sanctioned', 'message' => null, 'url' => 'http://localhost:8000/admin/galas?page=2'],
        'read_at' => null,
    ]);

    $this->actingAs($this->user)
        ->post(route('notifications.read', $notification))
        // The stored link was built from APP_URL by a queued job, so only its
        // path is used and the reader stays on the site they are browsing.
        ->assertRedirect('/admin/galas?page=2');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('will not let somebody mark another person\'s notice read', function (): void {
    $other = User::factory()->create();

    $notification = $other->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => GalaSanctioned::class,
        'data' => ['kind' => 'gala', 'title' => 'Not yours', 'message' => null, 'url' => null],
        'read_at' => null,
    ]);

    $this->actingAs($this->user)
        ->post(route('notifications.read', $notification))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

it('clears the whole tally at once', function (): void {
    foreach (range(1, 3) as $index) {
        $this->user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => GalaSanctioned::class,
            'data' => ['kind' => 'gala', 'title' => 'Notice '.$index, 'message' => null, 'url' => null],
            'read_at' => null,
        ]);
    }

    $this->actingAs($this->user)->post(route('notifications.readAll'));

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('keeps the account invitation out of the database, because it carries a reset link', function (): void {
    NotificationFake::fake();

    $user = User::factory()->create();

    $user->notify(new AccountCreated('Harare Amateur', 'Club secretary'));

    NotificationFake::assertSentTo(
        $user,
        AccountCreated::class,
        fn ($notification, array $channels): bool => $channels === ['mail'],
    );
});
