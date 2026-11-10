<?php

namespace App\Enums;

enum AccreditationStatus: string
{
    case InProgress = 'in_progress';
    case AwaitingRatification = 'awaiting_ratification';
    case Active = 'active';
    case Lapsed = 'lapsed';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            self::AwaitingRatification => 'Awaiting ratification',
            self::Active => 'Active',
            self::Lapsed => 'Lapsed',
            self::Revoked => 'Revoked',
        };
    }

    public function isUsable(): bool
    {
        return $this === self::Active;
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
