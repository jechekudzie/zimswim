<?php

namespace App\Enums;

enum RecordStatus: string
{
    case Pending = 'pending';
    case Ratified = 'ratified';
    case Superseded = 'superseded';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting ratification',
            self::Ratified => 'Ratified',
            self::Superseded => 'Superseded',
            self::Rejected => 'Rejected',
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
