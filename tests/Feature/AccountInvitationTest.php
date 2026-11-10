<?php

use App\Models\User;
use App\Notifications\AccountCreated;
use App\Support\Roles;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
});

it('invites a new user to set a password when their account is opened', function (): void {
    Notification::fake();

    $admin = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Tendai Moyo',
            'email' => 'tendai@zimbabweswimming.co.zw',
            'organization_id' => $this->province->getKey(),
            'role' => Roles::PROVINCIAL_ADMINISTRATOR,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $invited = User::where('email', 'tendai@zimbabweswimming.co.zw')->sole();

    Notification::assertSentTo(
        $invited,
        AccountCreated::class,
        fn (AccountCreated $notification): bool => $notification->organizationName === $this->province->name
            && $notification->roleLabel === Roles::label(Roles::PROVINCIAL_ADMINISTRATOR),
    );
});

it('builds the invitation around a reset link rather than a password', function (): void {
    $user = User::factory()->create(['email' => 'coach@zimbabweswimming.co.zw']);

    $mail = (new AccountCreated('Harare Swimming', 'Club Administrator'))->toMail($user);

    expect($mail->subject)->toBe('Your Zimbabwe Swimming account is ready')
        ->and($mail->actionUrl)->toContain('reset-password/')
        ->and($mail->actionUrl)->toContain(urlencode($user->email))
        ->and(implode(' ', $mail->introLines))->toContain('Harare Swimming');
});
