<?php

namespace App\Enums;

enum VenueGrade: string
{
    case National = 'national';
    case Provincial = 'provincial';
    case ClubDevelopment = 'club_development';

    public function label(): string
    {
        return match ($this) {
            self::National => 'National',
            self::Provincial => 'Provincial',
            self::ClubDevelopment => 'Club and development',
        };
    }

    public function canHost(): string
    {
        return match ($this) {
            self::National => 'Senior nationals, sprint gala and international meets',
            self::Provincial => 'Provincial championships and age-group galas',
            self::ClubDevelopment => 'Club galas, development and learn-to-swim events',
        };
    }

    /**
     * Higher is more capable; a venue may host anything at or below its own rank.
     */
    public function rank(): int
    {
        return match ($this) {
            self::National => 3,
            self::Provincial => 2,
            self::ClubDevelopment => 1,
        };
    }

    public function satisfies(self $required): bool
    {
        return $this->rank() >= $required->rank();
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
