<?php

namespace App\Enums;

enum Stroke: string
{
    case Freestyle = 'freestyle';
    case Backstroke = 'backstroke';
    case Breaststroke = 'breaststroke';
    case Butterfly = 'butterfly';
    case IndividualMedley = 'individual_medley';
    case FreestyleRelay = 'freestyle_relay';
    case MedleyRelay = 'medley_relay';

    public function label(): string
    {
        return match ($this) {
            self::Freestyle => 'Freestyle',
            self::Backstroke => 'Backstroke',
            self::Breaststroke => 'Breaststroke',
            self::Butterfly => 'Butterfly',
            self::IndividualMedley => 'Individual Medley',
            self::FreestyleRelay => 'Freestyle Relay',
            self::MedleyRelay => 'Medley Relay',
        };
    }

    public function isRelay(): bool
    {
        return in_array($this, [self::FreestyleRelay, self::MedleyRelay], true);
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
