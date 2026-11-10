<?php

namespace App\Enums;

enum OrganizationType: string
{
    case National = 'national';
    case Province = 'province';
    case Club = 'club';

    public function label(): string
    {
        return match ($this) {
            self::National => 'National body',
            self::Province => 'Province',
            self::Club => 'Club',
        };
    }

    /**
     * Organisation types this type may contain, top down.
     */
    public function childType(): ?self
    {
        return match ($this) {
            self::National => self::Province,
            self::Province => self::Club,
            self::Club => null,
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
