<?php

use App\Models\FeeSchedule;
use App\Models\Gala;
use App\Models\Season;

beforeEach(function (): void {
    $this->season = Season::factory()->create(['is_current' => true]);
});

it('lists what the season costs alongside the gala fees', function (): void {
    FeeSchedule::factory()->create([
        'season_id' => $this->season->getKey(),
        'name' => 'Swimming Athlete Capitation',
        'amount' => 20.00,
        'unit' => 'per athlete',
    ]);

    Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'name' => 'Junior Nationals',
        'is_published' => true,
        'entry_fee' => 20.00,
        'late_entry_fee' => 30.00,
        'late_entry_event_fee' => 5.00,
    ]);

    $this->get(route('fees'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/fees')
            ->where('fees.0.name', 'Swimming Athlete Capitation')
            ->where('fees.0.amount', 20)
            ->where('galas.0.late_entry_fee', 30)
            ->where('galas.0.late_entry_event_fee', 5)
            ->etc());
});

it('leaves a gala that has not been priced off the table', function (): void {
    Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'name' => 'Not Priced Yet',
        'is_published' => true,
        // Not null in the database: an unpriced gala carries zero.
        'entry_fee' => 0,
        'late_entry_fee' => 0,
    ]);

    $this->get(route('fees'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('galas', [])->etc());
});

it('keeps an unpublished gala off a public page', function (): void {
    Gala::factory()->create([
        'season_id' => $this->season->getKey(),
        'name' => 'Still A Draft',
        'is_published' => false,
        'entry_fee' => 20.00,
    ]);

    $this->get(route('fees'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('galas', [])->etc());
});
