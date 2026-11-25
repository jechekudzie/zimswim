<?php

namespace App\Enums;

/**
 * What a session on the officials' calendar actually is.
 *
 * A clinic teaches the category; an exam tests it. They sit on the same page
 * because somebody working towards an accreditation needs both dates, but
 * they are not interchangeable and the page must not let them read as one.
 */
enum TrainingKind: string
{
    case Clinic = 'clinic';
    case Exam = 'exam';
    case Refresher = 'refresher';

    public function label(): string
    {
        return match ($this) {
            self::Clinic => 'Clinic',
            self::Exam => 'Exam sitting',
            self::Refresher => 'Refresher',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Clinic => 'Taught from the start. Nothing is assumed and no accreditation is needed to attend.',
            self::Exam => 'The written paper for a category. Sit it once the clinic is done and the learner sessions are signed off.',
            self::Refresher => 'For officials who already hold the category and are keeping it current.',
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
