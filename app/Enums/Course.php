<?php

namespace App\Enums;

enum Course: string
{
    case LongCourse = 'LCM';
    case ShortCourse = 'SCM';

    public function label(): string
    {
        return match ($this) {
            self::LongCourse => 'Long course (50m)',
            self::ShortCourse => 'Short course (25m)',
        };
    }

    public function poolLength(): int
    {
        return $this === self::LongCourse ? 50 : 25;
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
