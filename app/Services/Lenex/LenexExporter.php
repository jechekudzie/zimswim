<?php

namespace App\Services\Lenex;

use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\Organization;
use App\Models\RelayTeam;
use App\Models\Result;
use App\Support\SwimTime;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Writes a gala out as a Lenex 3.0 document.
 *
 * Lenex is the interchange format the rest of the sport uses, so a gala built
 * here can be opened in Team Manager or Meet Manager, and the start list can be
 * handed to a timing console or a results app without anyone retyping it.
 */
class LenexExporter
{
    private const NATION = 'ZIM';

    /**
     * Build the document. Entries are always written; results are included
     * once there are any, which is what turns a start list into a results file.
     */
    public function export(Gala $gala, bool $withResults = true): string
    {
        $gala->load([
            'venue',
            'season',
            'host',
            'sessions',
            'events.session',
            'events.entries.member.club',
            'events.results.member',
        ]);

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElement('LENEX');
        $root->setAttribute('version', '3.0');
        $document->appendChild($root);

        $root->appendChild($this->constructor($document));

        $meets = $document->createElement('MEETS');
        $meets->appendChild($this->meet($document, $gala, $withResults));
        $root->appendChild($meets);

        return (string) $document->saveXML();
    }

    public function filename(Gala $gala, bool $withResults = true): string
    {
        return sprintf(
            '%s-%s.lef',
            Str::slug($gala->name),
            $withResults ? 'results' : 'entries',
        );
    }

    private function constructor(DOMDocument $document): DOMElement
    {
        $constructor = $document->createElement('CONSTRUCTOR');
        $constructor->setAttribute('name', 'ZimSwim Online');
        $constructor->setAttribute('registration', config('app.url'));
        $constructor->setAttribute('version', '1.0');

        $contact = $document->createElement('CONTACT');
        $contact->setAttribute('name', config('app.name'));
        $contact->setAttribute('email', (string) config('mail.from.address'));
        $constructor->appendChild($contact);

        return $constructor;
    }

    private function meet(DOMDocument $document, Gala $gala, bool $withResults): DOMElement
    {
        $meet = $document->createElement('MEET');
        $meet->setAttribute('name', $gala->name);
        $venue = $gala->bookedVenue();

        $meet->setAttribute('city', $venue === null ? 'Harare' : ($venue->city ?? 'Harare'));
        $meet->setAttribute('nation', self::NATION);
        $meet->setAttribute('course', $gala->course->value);

        if ($gala->entries_close_at !== null) {
            $meet->setAttribute('deadline', $gala->entries_close_at->toDateString());
        }

        $pool = $document->createElement('POOL');
        $pool->setAttribute('lanemin', '1');
        $pool->setAttribute('lanemax', (string) $gala->laneCount());

        if ($venue !== null) {
            $pool->setAttribute('name', $venue->name);
        }

        $meet->appendChild($pool);

        // Lenex needs to know the date ages are taken at, or every age group
        // in the file would be ambiguous.
        $ageDate = $document->createElement('AGEDATE');
        $ageDate->setAttribute('type', 'DATE');
        $ageDate->setAttribute('value', $gala->effectiveAgeUpDate()->toDateString());
        $meet->appendChild($ageDate);

        if ($gala->contact_name !== null || $gala->entries_email !== null) {
            $contact = $document->createElement('CONTACT');
            $contact->setAttribute('name', (string) ($gala->contact_name ?? $gala->host->name));
            $contact->setAttribute('email', (string) ($gala->entries_email ?? ''));
            $contact->setAttribute('phone', (string) ($gala->contact_phone ?? ''));
            $meet->appendChild($contact);
        }

        $meet->appendChild($this->sessions($document, $gala));
        $meet->appendChild($this->clubs($document, $gala, $withResults));

        return $meet;
    }

    private function sessions(DOMDocument $document, Gala $gala): DOMElement
    {
        $sessions = $document->createElement('SESSIONS');

        // Events without a session of their own still have to sit in one, so
        // they are collected into a single session on the opening day.
        $grouped = $gala->events->groupBy(fn (GalaEvent $event): int => $event->gala_session_id ?? 0);

        $number = 0;

        foreach ($gala->sessions as $session) {
            $events = $grouped->get($session->getKey(), collect());
            $sessions->appendChild($this->session(
                $document,
                ++$number,
                $session->held_on->toDateString(),
                $session->starts_at,
                $events,
            ));
        }

        $unassigned = $grouped->get(0, collect());

        if ($unassigned->isNotEmpty()) {
            $sessions->appendChild($this->session(
                $document,
                ++$number,
                $gala->starts_on->toDateString(),
                null,
                $unassigned,
            ));
        }

        return $sessions;
    }

    /**
     * @param  Collection<int, GalaEvent>  $events
     */
    private function session(
        DOMDocument $document,
        int $number,
        string $date,
        ?string $startsAt,
        Collection $events,
    ): DOMElement {
        $element = $document->createElement('SESSION');
        $element->setAttribute('number', (string) $number);
        $element->setAttribute('date', $date);

        if ($startsAt !== null) {
            $element->setAttribute('daytime', Str::substr($startsAt, 0, 5));
        }

        $eventsElement = $document->createElement('EVENTS');

        foreach ($events->sortBy('event_number') as $event) {
            $eventsElement->appendChild($this->event($document, $event));
        }

        $element->appendChild($eventsElement);

        return $element;
    }

    private function event(DOMDocument $document, GalaEvent $event): DOMElement
    {
        $element = $document->createElement('EVENT');
        $element->setAttribute('eventid', (string) $event->getKey());
        $element->setAttribute('number', (string) $event->event_number);
        $element->setAttribute('gender', $this->gender($event->genderRestriction()));
        $element->setAttribute('round', $this->round($event->round));

        if ($event->parent_event_id !== null) {
            $element->setAttribute('preveventid', (string) $event->parent_event_id);
        }

        $style = $document->createElement('SWIMSTYLE');
        $style->setAttribute('distance', (string) $event->distance);
        $style->setAttribute('relaycount', (string) ($event->is_relay ? ($event->relay_legs ?? 4) : 1));
        $style->setAttribute('stroke', LenexStroke::toLenex($event->stroke));
        $style->setAttribute('name', $event->title());
        $element->appendChild($style);

        $groups = $document->createElement('AGEGROUPS');
        $group = $document->createElement('AGEGROUP');
        $group->setAttribute('agegroupid', (string) $event->getKey());
        $group->setAttribute('agemin', (string) ($event->min_age ?? -1));
        $group->setAttribute('agemax', (string) ($event->max_age ?? -1));
        $group->setAttribute('gender', $this->gender($event->genderRestriction()));
        $group->setAttribute('name', $event->age_group);
        $groups->appendChild($group);
        $element->appendChild($groups);

        return $element;
    }

    private function clubs(DOMDocument $document, Gala $gala, bool $withResults): DOMElement
    {
        $clubs = $document->createElement('CLUBS');

        $entries = $gala->events->flatMap(fn (GalaEvent $event): Collection => $event->entries);
        $results = $gala->events->flatMap(fn (GalaEvent $event): Collection => $event->results);

        $relays = RelayTeam::query()
            ->where('gala_id', $gala->getKey())
            ->with(['swimmers.member', 'club'])
            ->get();

        $clubIds = $entries->pluck('organization_id')
            ->merge($relays->pluck('organization_id'))
            ->unique();

        $organizations = Organization::query()->whereKey($clubIds)->get()->keyBy('id');

        foreach ($clubIds as $clubId) {
            $organization = $organizations->get($clubId);

            if ($organization === null) {
                continue;
            }

            $clubs->appendChild($this->club(
                $document,
                $organization,
                $entries->where('organization_id', $clubId),
                $results->where('organization_id', $clubId),
                $relays->where('organization_id', $clubId),
                $withResults,
            ));
        }

        return $clubs;
    }

    /**
     * @param  Collection<int, GalaEntry>  $entries
     * @param  Collection<int, Result>  $results
     * @param  Collection<int, RelayTeam>  $relays
     */
    private function club(
        DOMDocument $document,
        Organization $organization,
        Collection $entries,
        Collection $results,
        Collection $relays,
        bool $withResults,
    ): DOMElement {
        $element = $document->createElement('CLUB');
        $element->setAttribute('name', $organization->name);
        $element->setAttribute('shortname', (string) ($organization->short_name ?? $organization->name));
        $element->setAttribute('code', (string) ($organization->code ?? ''));
        $element->setAttribute('nation', self::NATION);

        $athletes = $document->createElement('ATHLETES');

        // Every swimmer the club has in the meet, whether they got there
        // through an individual entry or only as a relay leg.
        $members = $entries->pluck('member')
            ->merge($relays->flatMap(fn (RelayTeam $team): Collection => $team->swimmers->pluck('member')))
            ->filter()
            ->unique('id');

        foreach ($members as $member) {
            $athletes->appendChild($this->athlete(
                $document,
                $member,
                $entries->where('member_id', $member->getKey()),
                $results->where('member_id', $member->getKey()),
                $withResults,
            ));
        }

        $element->appendChild($athletes);

        if ($relays->isNotEmpty()) {
            $element->appendChild($this->relays($document, $relays));
        }

        return $element;
    }

    /**
     * @param  Collection<int, GalaEntry>  $entries
     * @param  Collection<int, Result>  $results
     */
    private function athlete(
        DOMDocument $document,
        Member $member,
        Collection $entries,
        Collection $results,
        bool $withResults,
    ): DOMElement {
        $element = $document->createElement('ATHLETE');
        $element->setAttribute('athleteid', (string) $member->getKey());
        $element->setAttribute('firstname', $member->first_name);
        $element->setAttribute('lastname', $member->last_name);
        $element->setAttribute('gender', $member->gender === 'female' ? 'F' : 'M');
        $element->setAttribute('birthdate', $member->date_of_birth->toDateString());
        $element->setAttribute('license', $member->membership_number);

        if ($entries->isNotEmpty()) {
            $entriesElement = $document->createElement('ENTRIES');

            foreach ($entries as $entry) {
                $entryElement = $document->createElement('ENTRY');
                $entryElement->setAttribute('eventid', (string) $entry->gala_event_id);
                $entryElement->setAttribute('entrytime', $entry->entry_time_ms === null
                    ? 'NT'
                    : SwimTime::toLenex($entry->entry_time_ms));

                if ($entry->heat !== null) {
                    $entryElement->setAttribute('heat', (string) $entry->heat);
                }

                if ($entry->lane !== null) {
                    $entryElement->setAttribute('lane', (string) $entry->lane);
                }

                $entriesElement->appendChild($entryElement);
            }

            $element->appendChild($entriesElement);
        }

        if ($withResults && $results->isNotEmpty()) {
            $resultsElement = $document->createElement('RESULTS');

            foreach ($results as $result) {
                $resultsElement->appendChild($this->result($document, $result));
            }

            $element->appendChild($resultsElement);
        }

        return $element;
    }

    private function result(DOMDocument $document, Result $result): DOMElement
    {
        $element = $document->createElement('RESULT');
        $element->setAttribute('resultid', (string) $result->getKey());
        $element->setAttribute('eventid', (string) $result->gala_event_id);
        $element->setAttribute('swimtime', $result->time_ms === null
            ? '00:00:00.00'
            : SwimTime::toLenex($result->time_ms));

        if ($result->heat !== null) {
            $element->setAttribute('heat', (string) $result->heat);
        }

        if ($result->lane !== null) {
            $element->setAttribute('lane', (string) $result->lane);
        }

        if ($result->place !== null) {
            $element->setAttribute('place', (string) $result->place);
        }

        if ($result->points !== null) {
            $element->setAttribute('points', (string) $result->points);
        }

        $status = $this->resultStatus($result->status);

        if ($status !== null) {
            $element->setAttribute('status', $status);
        }

        if ($result->dq_code !== null) {
            $element->setAttribute('comment', $result->dq_code.' '.($result->dq_reason ?? ''));
        }

        return $element;
    }

    /**
     * @param  Collection<int, RelayTeam>  $relays
     */
    private function relays(DOMDocument $document, Collection $relays): DOMElement
    {
        $element = $document->createElement('RELAYS');

        foreach ($relays as $index => $team) {
            $relay = $document->createElement('RELAY');
            $relay->setAttribute('number', (string) ($index + 1));
            $relay->setAttribute('name', $team->displayName());
            $relay->setAttribute('gender', $this->gender($team->event->genderRestriction()));
            $relay->setAttribute('agemin', (string) ($team->event->min_age ?? -1));
            $relay->setAttribute('agemax', (string) ($team->event->max_age ?? -1));
            $relay->setAttribute('agetotalmin', '-1');
            $relay->setAttribute('agetotalmax', '-1');

            $entries = $document->createElement('ENTRIES');
            $entry = $document->createElement('ENTRY');
            $entry->setAttribute('eventid', (string) $team->gala_event_id);
            $entry->setAttribute('entrytime', $team->entry_time_ms === null
                ? 'NT'
                : SwimTime::toLenex($team->entry_time_ms));

            if ($team->heat !== null) {
                $entry->setAttribute('heat', (string) $team->heat);
            }

            if ($team->lane !== null) {
                $entry->setAttribute('lane', (string) $team->lane);
            }

            $positions = $document->createElement('RELAYPOSITIONS');

            foreach ($team->swimmers as $swimmer) {
                $position = $document->createElement('RELAYPOSITION');
                $position->setAttribute('number', (string) $swimmer->leg);
                $position->setAttribute('athleteid', (string) $swimmer->member_id);
                $positions->appendChild($position);
            }

            $entry->appendChild($positions);
            $entries->appendChild($entry);
            $relay->appendChild($entries);
            $element->appendChild($relay);
        }

        return $element;
    }

    private function gender(string $restriction): string
    {
        return match ($restriction) {
            'female' => 'F',
            'male' => 'M',
            default => 'X',
        };
    }

    private function round(string $round): string
    {
        return match ($round) {
            'final' => 'FIN',
            'semi_final' => 'SEM',
            'quarter_final' => 'QUA',
            'heats' => 'PRE',
            default => 'TIM',
        };
    }

    /**
     * Lenex leaves the status off a clean swim and names only the exceptions.
     */
    private function resultStatus(string $status): ?string
    {
        return match ($status) {
            'dq' => 'DSQ',
            'dns' => 'DNS',
            'dnf' => 'DNF',
            'withdrawn' => 'WDR',
            default => null,
        };
    }
}
