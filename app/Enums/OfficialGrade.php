<?php

namespace App\Enums;

enum OfficialGrade: string
{
    case Learner = 'learner';
    case Provincial = 'provincial';
    case National = 'national';
    case Regional = 'regional';

    public function label(): string
    {
        return match ($this) {
            self::Learner => 'Learner',
            self::Provincial => 'Qualified Provincial',
            self::National => 'Qualified National',
            self::Regional => 'Qualified Regional',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Learner => 0,
            self::Provincial => 1,
            self::National => 2,
            self::Regional => 3,
        };
    }

    /**
     * An accreditation lapses if the official does not work at their highest
     * level for two years.
     */
    public function lapsesAfterYears(): int
    {
        return 2;
    }

    public function satisfies(self $required): bool
    {
        return $this->rank() >= $required->rank();
    }

    /**
     * The grade an official must hold to work a gala at the given level.
     */
    public static function requiredFor(GalaLevel $level): self
    {
        return match ($level) {
            GalaLevel::International => self::Regional,
            GalaLevel::National => self::National,
            GalaLevel::Provincial, GalaLevel::Invitational => self::Provincial,
            GalaLevel::Club => self::Learner,
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
