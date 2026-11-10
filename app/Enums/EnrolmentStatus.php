<?php

namespace App\Enums;

enum EnrolmentStatus: string
{
    case InProgress = 'in_progress';
    case TheoryPassed = 'theory_passed';
    case AwaitingPractical = 'awaiting_practical';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            self::TheoryPassed => 'Theory passed',
            self::AwaitingPractical => 'Awaiting practical sign-off',
            self::Completed => 'Completed',
            self::Withdrawn => 'Withdrawn',
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
