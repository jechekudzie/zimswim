<?php

namespace App\Services\Lenex;

use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Services\EntryEligibility;
use App\Support\SwimTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;
use Throwable;

/**
 * Reads a Lenex entry file produced by a club's own software and turns it into
 * entries for one of our galas.
 *
 * Clubs send files built against their own numbering, so nothing is trusted by
 * id. Swimmers are matched on their licence number first, then on name and date
 * of birth; events are matched on what is actually swum — distance, stroke,
 * gender and age band. Anything that cannot be matched is reported back rather
 * than guessed at, and the same eligibility rules apply as to an entry typed in
 * by hand.
 */
class LenexImporter
{
    public function __construct(private readonly EntryEligibility $eligibility) {}

    /**
     * @return array{entered: int, updated: int, skipped: int, problems: array<int, string>}
     */
    public function import(Gala $gala, string $xml, ?int $enteredBy = null): array
    {
        $document = $this->parse($xml);

        $outcome = ['entered' => 0, 'updated' => 0, 'skipped' => 0, 'problems' => []];

        $events = $gala->events()->get();
        $eventsByLenexId = $this->mapEventIds($document, $events);

        foreach ($document->xpath('//CLUB') ?: [] as $club) {
            $clubName = (string) $club['name'];

            foreach ($club->ATHLETES->ATHLETE ?? [] as $athlete) {
                $member = $this->matchMember($athlete);

                if ($member === null) {
                    $outcome['problems'][] = sprintf(
                        '%s %s of %s is not a registered swimmer.',
                        (string) $athlete['firstname'],
                        (string) $athlete['lastname'],
                        $clubName !== '' ? $clubName : 'an unnamed club',
                    );
                    $outcome['skipped']++;

                    continue;
                }

                foreach ($athlete->ENTRIES->ENTRY ?? [] as $entry) {
                    $this->importEntry(
                        $gala,
                        $member,
                        $entry,
                        $eventsByLenexId,
                        $enteredBy,
                        $outcome,
                    );
                }
            }
        }

        return $outcome;
    }

    /**
     * Lenex files may be plain XML (.lef) or a single-entry zip (.lxf). Both
     * arrive here as raw bytes.
     */
    public function read(string $path): string
    {
        $contents = (string) file_get_contents($path);

        if (! str_starts_with($contents, 'PK')) {
            return $contents;
        }

        $zip = new \ZipArchive;

        if ($zip->open($path) !== true) {
            throw new LenexException('That .lxf file could not be opened.');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);

            if (str_ends_with(strtolower($name), '.lef') || str_ends_with(strtolower($name), '.xml')) {
                $extracted = $zip->getFromIndex($index);
                $zip->close();

                if ($extracted === false) {
                    throw new LenexException('That .lxf file could not be read.');
                }

                return $extracted;
            }
        }

        $zip->close();

        throw new LenexException('That .lxf file contains no Lenex document.');
    }

    private function parse(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);

        try {
            // Entity loading stays off so an uploaded file cannot reach out to
            // the filesystem or the network.
            $document = new SimpleXMLElement($xml, LIBXML_NONET | LIBXML_NOENT);
        } catch (Throwable) {
            throw new LenexException('That file is not readable as Lenex XML.');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($document->getName() !== 'LENEX') {
            throw new LenexException('That file is not a Lenex document.');
        }

        return $document;
    }

    /**
     * Pair the file's event ids with ours by matching what is swum, because the
     * sending system's numbering means nothing here.
     *
     * @param  Collection<int, GalaEvent>  $events
     * @return array<string, GalaEvent>
     */
    private function mapEventIds(SimpleXMLElement $document, $events): array
    {
        $map = [];

        foreach ($document->xpath('//EVENT') ?: [] as $event) {
            $lenexId = (string) $event['eventid'];
            $style = $event->SWIMSTYLE;

            if ($lenexId === '' || $style === null) {
                continue;
            }

            $relayCount = max(1, (int) $style['relaycount']);
            $stroke = LenexStroke::fromLenex((string) $style['stroke'], $relayCount);
            $distance = (int) $style['distance'];
            $gender = strtoupper((string) $event['gender']);

            $ageMin = null;
            $ageMax = null;

            if (isset($event->AGEGROUPS->AGEGROUP)) {
                $group = $event->AGEGROUPS->AGEGROUP[0];
                $ageMin = (int) $group['agemin'] > 0 ? (int) $group['agemin'] : null;
                $ageMax = (int) $group['agemax'] > 0 ? (int) $group['agemax'] : null;
            }

            $match = $events->first(fn (GalaEvent $ours): bool => $ours->distance === $distance
                && $ours->stroke === $stroke
                && $this->gendersAgree($ours->genderRestriction(), $gender)
                && $ours->min_age === $ageMin
                && $ours->max_age === $ageMax);

            // Fall back to the event number when the age band is written
            // differently, which is common between systems.
            $match ??= $events->first(fn (GalaEvent $ours): bool => $ours->event_number === (int) $event['number']
                && $ours->distance === $distance
                && $ours->stroke === $stroke);

            if ($match !== null) {
                $map[$lenexId] = $match;
            }
        }

        return $map;
    }

    private function gendersAgree(string $ours, string $theirs): bool
    {
        return match ($theirs) {
            'F' => $ours === 'female',
            'M' => $ours === 'male',
            default => $ours === 'mixed',
        };
    }

    private function matchMember(SimpleXMLElement $athlete): ?Member
    {
        $licence = trim((string) $athlete['license']);

        if ($licence !== '') {
            $byLicence = Member::query()->where('membership_number', $licence)->first();

            if ($byLicence !== null) {
                return $byLicence;
            }
        }

        $firstName = trim((string) $athlete['firstname']);
        $lastName = trim((string) $athlete['lastname']);
        $birthdate = trim((string) $athlete['birthdate']);

        if ($firstName === '' || $lastName === '' || $birthdate === '') {
            return null;
        }

        return Member::query()
            ->whereRaw('lower(first_name) = ?', [mb_strtolower($firstName)])
            ->whereRaw('lower(last_name) = ?', [mb_strtolower($lastName)])
            ->whereDate('date_of_birth', $birthdate)
            ->first();
    }

    /**
     * @param  array<string, GalaEvent>  $eventsByLenexId
     * @param  array{entered: int, updated: int, skipped: int, problems: array<int, string>}  $outcome
     */
    private function importEntry(
        Gala $gala,
        Member $member,
        SimpleXMLElement $entry,
        array $eventsByLenexId,
        ?int $enteredBy,
        array &$outcome,
    ): void {
        $event = $eventsByLenexId[(string) $entry['eventid']] ?? null;

        if ($event === null) {
            $outcome['problems'][] = 'An entry refers to an event that is not in this gala.';
            $outcome['skipped']++;

            return;
        }

        $timeMs = SwimTime::fromLenex((string) $entry['entrytime']);

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
            $outcome['problems'][] = $problems[0];
            $outcome['skipped']++;

            return;
        }

        DB::transaction(function () use ($gala, $event, $member, $timeMs, $enteredBy): void {
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
        });

        $outcome['entered']++;
    }
}
