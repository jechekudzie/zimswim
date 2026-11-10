<?php

namespace App\Enums;

enum QuestionType: string
{
    case Single = 'single';
    case Multiple = 'multiple';
    case TrueFalse = 'true_false';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'One correct answer',
            self::Multiple => 'Several correct answers',
            self::TrueFalse => 'True or false',
        };
    }

    public function allowsManyAnswers(): bool
    {
        return $this === self::Multiple;
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
