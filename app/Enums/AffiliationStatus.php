<?php

namespace App\Enums;

/**
 * Where a province or club stands with the national body for the season.
 *
 * Clubs affiliate through their province, and the province is given a deadline
 * by which everything owed must have reached Zimbabwe Swimming. A province that
 * has not met it is not fully affiliated, and its clubs may not enter swimmers
 * until it is put right.
 */
enum AffiliationStatus: string
{
    case Pending = 'pending';

    case Affiliated = 'affiliated';

    case Lapsed = 'lapsed';

    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Affiliated => 'Affiliated',
            self::Lapsed => 'Lapsed',
            self::Suspended => 'Suspended',
        };
    }

    /**
     * May swimmers be entered in galas under this standing?
     *
     * Only a completed affiliation allows it. Pending covers a province whose
     * paperwork or payment has not arrived yet, and the other two cover one
     * that has fallen out of good standing.
     */
    public function permitsCompetition(): bool
    {
        return $this === self::Affiliated;
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
