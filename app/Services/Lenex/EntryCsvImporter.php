<?php

namespace App\Services\Lenex;

use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Services\EntryEligibility;
use App\Support\SwimTime;
use Illuminate\Support\Collection;

/**
 * The plain-spreadsheet route into a gala, for clubs that keep their squad in
 * Excel rather than in meet software.
 *
 * One row is one swim. Swimmers are matched on their membership number, and
 * events on their number in the programme, so a club only has to know the two
 * things already printed on the entry pack.
 */
class EntryCsvImporter
{
    /**
     * The column order the template is written in and read back from.
     *
     * @var array<int, string>
     */
    public const COLUMNS = ['membership_number', 'swimmer', 'event_number', 'entry_time'];

    public function __construct(private readonly EntryEligibility $eligibility) {}

    /**
     * A blank entry sheet for a gala. One row is pre-filled with the first
     * event so the expected shape of a row is obvious, and the swimmer column
     * is there only to make the sheet readable — matching is on the
     * membership number.
     */
    public function template(Gala $gala): string
    {
        $first = $gala->events()->first();

        $lines = [
            implode(',', self::COLUMNS),
            sprintf(
                '%s,Replace this row,%d,32.10',
                Member::query()->value('membership_number') ?? 'ZSW-01001',
                $first === null ? 1 : $first->event_number,
            ),
        ];

        return implode("\n", $lines)."\n";
    }

    /**
     * @return array{entered: int, updated: int, skipped: int, problems: array<int, string>}
     */
    public function import(Gala $gala, string $path, ?int $enteredBy = null): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new LenexException('That file could not be opened.');
        }

        $outcome = ['entered' => 0, 'updated' => 0, 'skipped' => 0, 'problems' => []];
        $events = $gala->events()->get()->keyBy('event_number');
        $line = 0;

        try {
            while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                $line++;

                if ($line === 1 || $row === [null] || $this->isBlank($row)) {
                    continue;
                }

                $this->importRow($gala, $row, $events, $line, $enteredBy, $outcome);
            }
        } finally {
            fclose($handle);
        }

        return $outcome;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isBlank(array $row): bool
    {
        return trim(implode('', array_map(fn (?string $cell): string => (string) $cell, $row))) === '';
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  Collection<int|string, GalaEvent>  $events
     * @param  array{entered: int, updated: int, skipped: int, problems: array<int, string>}  $outcome
     */
    private function importRow(
        Gala $gala,
        array $row,
        $events,
        int $line,
        ?int $enteredBy,
        array &$outcome,
    ): void {
        $membershipNumber = trim((string) ($row[0] ?? ''));
        $eventNumber = (int) trim((string) ($row[2] ?? ''));

        $event = $events->get($eventNumber);

        if ($event === null) {
            $outcome['problems'][] = "Line {$line}: event {$eventNumber} is not in this gala.";
            $outcome['skipped']++;

            return;
        }

        $member = Member::query()->where('membership_number', $membershipNumber)->first();

        if ($member === null) {
            $outcome['problems'][] = "Line {$line}: no swimmer has membership number {$membershipNumber}.";
            $outcome['skipped']++;

            return;
        }

        $timeMs = SwimTime::tryParse(trim((string) ($row[3] ?? '')) ?: null);

        $existing = GalaEntry::query()
            ->where('gala_event_id', $event->getKey())
            ->where('member_id', $member->getKey())
            ->first();

        if ($existing !== null) {
            $existing->update(['entry_time_ms' => $timeMs ?? $existing->entry_time_ms]);
            $outcome['updated']++;

            return;
        }

        $problems = $this->eligibility->problems($member, $event);

        if ($problems !== []) {
            $outcome['problems'][] = "Line {$line}: ".$problems[0];
            $outcome['skipped']++;

            return;
        }

        GalaEntry::create([
            'gala_id' => $gala->getKey(),
            'gala_event_id' => $event->getKey(),
            'member_id' => $member->getKey(),
            'organization_id' => $member->organization_id,
            'entry_time_ms' => $timeMs ?? $this->eligibility->bestTimeFor($member, $event),
            'status' => 'entered',
            'is_late_entry' => $this->eligibility->isLate($event),
            'entered_by' => $enteredBy,
        ]);

        $outcome['entered']++;
    }
}
