<?php

use App\Support\SwimTime;

it('formats times under a minute without a minutes part', function (): void {
    expect(SwimTime::format(29_840))->toBe('29.84')
        ->and(SwimTime::format(58_070))->toBe('58.07')
        ->and(SwimTime::format(9_900))->toBe('9.90');
});

it('formats times over a minute with padded seconds', function (): void {
    expect(SwimTime::format(62_340))->toBe('1:02.34')
        ->and(SwimTime::format(180_000))->toBe('3:00.00')
        ->and(SwimTime::format(370_890))->toBe('6:10.89');
});

it('reads times written with a full stop or a colon', function (): void {
    expect(SwimTime::parse('1:02.34'))->toBe(62_340)
        ->and(SwimTime::parse('58.07'))->toBe(58_070)
        ->and(SwimTime::parse('1:02:34'))->toBe(62_340);
});

it('survives a round trip through parse and format', function (string $time): void {
    expect(SwimTime::format(SwimTime::parse($time)))->toBe($time);
})->with(['29.84', '1:02.34', '2:59.36', '6:10.89', '10:30.20']);

it('rejects a time it cannot read', function (string $time): void {
    expect(fn (): int => SwimTime::parse($time))
        ->toThrow(InvalidArgumentException::class);
})->with(['NT', '', 'quite fast', '1:75.00', 'abc.12']);

it('returns null rather than throwing when reading an optional time', function (): void {
    expect(SwimTime::tryParse(null))->toBeNull()
        ->and(SwimTime::tryParse('NT'))->toBeNull()
        ->and(SwimTime::tryParse('1:02.34'))->toBe(62_340);
});

it('orders swimmers correctly by the milliseconds it produces', function (): void {
    $times = ['1:02.34', '58.07', '1:02.33', '59.99'];
    $parsed = array_map(SwimTime::parse(...), $times);
    sort($parsed);

    expect(array_map(SwimTime::format(...), $parsed))
        ->toBe(['58.07', '59.99', '1:02.33', '1:02.34']);
});
