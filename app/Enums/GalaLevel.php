<?php

namespace App\Enums;

enum GalaLevel: string
{
    case Club = 'club';
    case Provincial = 'provincial';
    case National = 'national';
    case Invitational = 'invitational';
    case International = 'international';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * The minimum venue grade a gala at this level may be held at.
     */
    public function minimumVenueGrade(): VenueGrade
    {
        return match ($this) {
            self::National, self::International => VenueGrade::National,
            self::Provincial, self::Invitational => VenueGrade::Provincial,
            self::Club => VenueGrade::ClubDevelopment,
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
