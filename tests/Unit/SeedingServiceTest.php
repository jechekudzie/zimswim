<?php

use App\Services\SeedingService;

beforeEach(function (): void {
    $this->seeding = new SeedingService;
});

it('fills lanes from the centre outwards', function (): void {
    expect($this->seeding->laneOrder(8))->toBe([4, 5, 3, 6, 2, 7, 1, 8])
        ->and($this->seeding->laneOrder(6))->toBe([3, 4, 2, 5, 1, 6])
        ->and($this->seeding->laneOrder(10))->toBe([5, 6, 4, 7, 3, 8, 2, 9, 1, 10]);
});

it('handles an odd number of lanes', function (): void {
    expect($this->seeding->laneOrder(7))->toBe([4, 5, 3, 6, 2, 7, 1]);
});

it('puts every swimmer in one heat when they fit', function (): void {
    expect($this->seeding->heatSizes(6, 8))->toBe([6]);
});

it('fills the fastest heats and leaves the remainder in the slowest', function (): void {
    // Listed fastest heat first: two full heats, then four in the slowest.
    expect($this->seeding->heatSizes(20, 8))->toBe([8, 8, 4]);
});

it('spreads the field rather than leaving a swimmer almost alone', function (): void {
    $sizes = $this->seeding->heatSizes(17, 8);

    expect(array_sum($sizes))->toBe(17)
        ->and(min($sizes))->toBeGreaterThanOrEqual(3)
        ->and($sizes)->toBe([6, 6, 5]);
});

it('returns nothing to seed for an empty event', function (): void {
    expect($this->seeding->heatSizes(0, 8))->toBe([]);
});
