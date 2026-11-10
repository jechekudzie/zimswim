<?php

namespace App\Enums;

/**
 * Where somebody's application to officiate stands.
 *
 * Nobody reaches the Academy on their own say-so. An application names a
 * category and a club, and the technical committee checks both before an
 * account exists: the register is what a referee is trusted on, so the people
 * entering it are admitted deliberately.
 */
enum OfficialApplicationStatus: string
{
    case Submitted = 'submitted';

    case UnderReview = 'under_review';

    case Approved = 'approved';

    case Declined = 'declined';

    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::Approved => 'Approved',
            self::Declined => 'Declined',
            self::Withdrawn => 'Withdrawn',
        };
    }

    /**
     * What the applicant is told is happening, in their words rather than the
     * committee's.
     */
    public function applicantLabel(): string
    {
        return match ($this) {
            self::Submitted => 'With the technical committee',
            self::UnderReview => 'Being reviewed',
            self::Approved => 'Approved — check your email',
            self::Declined => 'Not taken forward',
            self::Withdrawn => 'Withdrawn',
        };
    }

    /**
     * Is this application still waiting on somebody?
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Submitted, self::UnderReview], true);
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
