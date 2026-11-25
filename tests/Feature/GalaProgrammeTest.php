<?php

use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\GalaSession;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->editor = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);
    $this->gala = Gala::factory()->create([
        'host_organization_id' => $this->national->getKey(),
        'starts_on' => now()->addMonth()->toDateString(),
        'ends_on' => now()->addMonth()->addDay()->toDateString(),
    ]);
});

it('builds a programme and shows it on the public gala page', function (): void {
    $this->actingAs($this->editor)
        ->post(route('admin.galas.sessions.store', $this->gala), [
            'name' => 'Saturday AM',
            'held_on' => $this->gala->starts_on->toDateString(),
            'warmup_at' => '06:30',
            'starts_at' => '08:30',
        ])->assertRedirect();

    $session = GalaSession::where('gala_id', $this->gala->getKey())->firstOrFail();

    $this->actingAs($this->editor)
        ->post(route('admin.galas.events.store', $this->gala), [
            'gala_session_id' => $session->getKey(),
            'event_number' => 101,
            'gender' => 'girls',
            'age_group' => 'Open',
            'stroke' => Stroke::Freestyle->value,
            'distance' => 50,
            'round' => 'timed_final',
        ])->assertRedirect();

    $this->gala->update(['is_published' => true]);

    $this->get(route('galas.show', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sessions.0.name', 'Saturday AM')
            ->where('sessions.0.events.0.number', 101)
            ->etc());
});

it('refuses two events with the same number at one gala', function (): void {
    $session = GalaSession::factory()->create(['gala_id' => $this->gala->getKey()]);

    $payload = [
        'gala_session_id' => $session->getKey(),
        'event_number' => 101,
        'gender' => 'girls',
        'age_group' => 'Open',
        'stroke' => Stroke::Freestyle->value,
        'distance' => 50,
        'round' => 'timed_final',
    ];

    // The number is how a swimmer finds their event on the heat sheet. Two
    // events carrying 101 would put them on the wrong block.
    $this->actingAs($this->editor)
        ->post(route('admin.galas.events.store', $this->gala), $payload)
        ->assertRedirect();

    $this->actingAs($this->editor)
        ->post(route('admin.galas.events.store', $this->gala), $payload)
        ->assertSessionHasErrors('event_number');
});

it('will not put an event in another gala\'s session', function (): void {
    $other = Gala::factory()->create(['host_organization_id' => $this->national->getKey()]);
    $theirs = GalaSession::factory()->create(['gala_id' => $other->getKey()]);

    $this->actingAs($this->editor)
        ->post(route('admin.galas.events.store', $this->gala), [
            'gala_session_id' => $theirs->getKey(),
            'event_number' => 101,
            'gender' => 'girls',
            'age_group' => 'Open',
            'stroke' => Stroke::Freestyle->value,
            'distance' => 50,
            'round' => 'timed_final',
        ])->assertSessionHasErrors('gala_session_id');
});

it('gives a relay its legs and takes them off an individual event', function (): void {
    $session = GalaSession::factory()->create(['gala_id' => $this->gala->getKey()]);

    $this->actingAs($this->editor)
        ->post(route('admin.galas.events.store', $this->gala), [
            'gala_session_id' => $session->getKey(),
            'event_number' => 201,
            'gender' => 'mixed',
            'age_group' => 'Open',
            'stroke' => Stroke::FreestyleRelay->value,
            'distance' => 50,
            'round' => 'timed_final',
            'is_relay' => true,
        ])->assertRedirect();

    $relay = GalaEvent::where('event_number', 201)->firstOrFail();

    expect($relay->relay_legs)->toBe(4);

    $this->actingAs($this->editor)
        ->patch(route('admin.galas.events.update', [$this->gala, $relay]), [
            'gala_session_id' => $session->getKey(),
            'event_number' => 201,
            'gender' => 'girls',
            'age_group' => 'Open',
            'stroke' => Stroke::Freestyle->value,
            'distance' => 50,
            'round' => 'timed_final',
            'relay_legs' => 4,
        ])->assertRedirect();

    // Legs on an individual event mean nothing and would mislead a seeder.
    expect($relay->fresh()->relay_legs)->toBeNull();
});

it('will not delete an event that has already been seeded', function (): void {
    $session = GalaSession::factory()->create(['gala_id' => $this->gala->getKey()]);
    $event = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gala_session_id' => $session->getKey(),
        'seeded_at' => now(),
    ]);

    // Swimmers and lanes hang off it by then. Losing those is not a
    // decision for a programme screen.
    $this->actingAs($this->editor)
        ->delete(route('admin.galas.events.destroy', [$this->gala, $event]))
        ->assertStatus(422);

    expect(GalaEvent::whereKey($event->getKey())->exists())->toBeTrue();
});

it('will not delete a session that still holds events', function (): void {
    $session = GalaSession::factory()->create(['gala_id' => $this->gala->getKey()]);
    GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gala_session_id' => $session->getKey(),
    ]);

    $this->actingAs($this->editor)
        ->delete(route('admin.galas.sessions.destroy', [$this->gala, $session]))
        ->assertStatus(422);
});

it('keeps the programme away from somebody who cannot edit the gala', function (): void {
    $swimmer = userWithRole(Roles::SWIMMER, $this->club);

    $this->actingAs($swimmer)
        ->post(route('admin.galas.sessions.store', $this->gala), [
            'name' => 'Not theirs',
            'held_on' => $this->gala->starts_on->toDateString(),
        ])->assertForbidden();
});
