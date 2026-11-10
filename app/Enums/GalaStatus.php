<?php

namespace App\Enums;

enum GalaStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Sanctioned = 'sanctioned';
    case Rejected = 'rejected';
    case EntriesOpen = 'entries_open';
    case EntriesClosed = 'entries_closed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Awaiting sanction',
            self::Sanctioned => 'Sanctioned',
            self::Rejected => 'Rejected',
            self::EntriesOpen => 'Entries open',
            self::EntriesClosed => 'Entries closed',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Statuses at or beyond board sanction, which may appear on the public calendar.
     */
    public function isSanctioned(): bool
    {
        return in_array($this, [
            self::Sanctioned,
            self::EntriesOpen,
            self::EntriesClosed,
            self::InProgress,
            self::Completed,
        ], true);
    }

    public function acceptsEntries(): bool
    {
        return $this === self::EntriesOpen;
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
