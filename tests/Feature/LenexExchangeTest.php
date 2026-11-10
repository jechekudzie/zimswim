<?php

use App\Enums\AffiliationStatus;
use App\Enums\Course;
use App\Enums\GalaStatus;
use App\Enums\MemberStatus;
use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\MemberRegistration;
use App\Models\Result;
use App\Models\Season;
use App\Models\Venue;
use App\Services\Lenex\EntryCsvImporter;
use App\Services\Lenex\LenexException;
use App\Services\Lenex\LenexExporter;
use App\Services\Lenex\LenexImporter;
use App\Support\Roles;
use App\Support\SwimTime;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->season = Season::factory()->create();
    $this->venue = Venue::factory()->create(['lanes' => 8, 'city' => 'Bulawayo']);

    $this->gala = Gala::factory()->sanctioned()->create([
        'season_id' => $this->season->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'sanctioning_organization_id' => $this->national->getKey(),
        'venue_id' => $this->venue->getKey(),
        'course' => Course::LongCourse,
        'status' => GalaStatus::EntriesOpen,
        'entries_close_at' => now()->addWeek(),
        'age_up_date' => '2026-12-31',
    ]);

    $this->event = GalaEvent::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'event_number' => 1,
        'gender' => 'girls',
        'age_group' => '11/12',
        'min_age' => 11,
        'max_age' => 12,
        'stroke' => Stroke::Freestyle,
        'distance' => 50,
    ]);
});

function registeredSwimmer(string $first, string $last, string $dob, $context): Member
{
    $member = Member::factory()->create([
        'organization_id' => $context->club->getKey(),
        'first_name' => $first,
        'last_name' => $last,
        'date_of_birth' => $dob,
        'gender' => 'female',
        'status' => MemberStatus::Active,
    ]);

    MemberRegistration::factory()->create([
        'member_id' => $member->getKey(),
        'season_id' => $context->season->getKey(),
        'organization_id' => $context->club->getKey(),
    ]);

    return $member;
}

it('exports a gala as Lenex that carries the programme, entries and results', function (): void {
    $member = registeredSwimmer('Tariro', 'Chikwava', '2015-04-10', $this);

    $entry = GalaEntry::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gala_event_id' => $this->event->getKey(),
        'member_id' => $member->getKey(),
        'organization_id' => $this->club->getKey(),
        'entry_time_ms' => 31_450,
        'heat' => 2,
        'lane' => 4,
        'status' => 'confirmed',
    ]);

    Result::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gala_event_id' => $this->event->getKey(),
        'gala_entry_id' => $entry->getKey(),
        'member_id' => $member->getKey(),
        'organization_id' => $this->club->getKey(),
        'time_ms' => 30_880,
        'place' => 1,
        'heat' => 2,
        'lane' => 4,
        'status' => 'official',
    ]);

    $xml = app(LenexExporter::class)->export($this->gala->fresh());
    $document = new SimpleXMLElement($xml);

    expect((string) $document['version'])->toBe('3.0');
    expect((string) $document->MEETS->MEET['course'])->toBe('LCM');
    expect((string) $document->MEETS->MEET['city'])->toBe('Bulawayo');
    expect((string) $document->MEETS->MEET->AGEDATE['value'])->toBe('2026-12-31');
    expect((string) $document->MEETS->MEET->POOL['lanemax'])->toBe('8');

    $event = $document->xpath('//EVENT')[0];
    expect((string) $event['gender'])->toBe('F');
    expect((string) $event->SWIMSTYLE['stroke'])->toBe('FREE');
    expect((string) $event->SWIMSTYLE['distance'])->toBe('50');
    expect((string) $event->AGEGROUPS->AGEGROUP['agemin'])->toBe('11');

    $athlete = $document->xpath('//ATHLETE')[0];
    expect((string) $athlete['lastname'])->toBe('Chikwava');
    expect((string) $athlete['license'])->toBe($member->membership_number);
    expect((string) $athlete->ENTRIES->ENTRY['entrytime'])->toBe('00:00:31.45');
    expect((string) $athlete->ENTRIES->ENTRY['lane'])->toBe('4');
    expect((string) $athlete->RESULTS->RESULT['swimtime'])->toBe('00:00:30.88');
    expect((string) $athlete->RESULTS->RESULT['place'])->toBe('1');
});

it('marks a disqualification in the exported results', function (): void {
    $member = registeredSwimmer('Rudo', 'Moyo', '2015-01-05', $this);

    $entry = GalaEntry::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gala_event_id' => $this->event->getKey(),
        'member_id' => $member->getKey(),
        'organization_id' => $this->club->getKey(),
    ]);

    Result::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'gala_event_id' => $this->event->getKey(),
        'gala_entry_id' => $entry->getKey(),
        'member_id' => $member->getKey(),
        'organization_id' => $this->club->getKey(),
        'time_ms' => null,
        'status' => 'dq',
        'dq_code' => '5.2',
    ]);

    $xml = app(LenexExporter::class)->export($this->gala->fresh());
    $result = (new SimpleXMLElement($xml))->xpath('//RESULT')[0];

    expect((string) $result['status'])->toBe('DSQ');
    expect((string) $result['swimtime'])->toBe('00:00:00.00');
    expect((string) $result['comment'])->toContain('5.2');
});

it('imports entries from a Lenex file by matching swimmers on licence and events on what is swum', function (): void {
    $member = registeredSwimmer('Peter', 'Wetzlar', '2015-06-20', $this);

    // The club's own file numbers the event 99, which must not matter.
    $xml = <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <LENEX version="3.0">
      <MEETS><MEET name="Club file" course="LCM">
        <SESSIONS><SESSION number="1" date="2026-11-14"><EVENTS>
          <EVENT eventid="99" number="1" gender="F" round="TIM">
            <SWIMSTYLE distance="50" relaycount="1" stroke="FREE"/>
            <AGEGROUPS><AGEGROUP agegroupid="1" agemin="11" agemax="12" gender="F"/></AGEGROUPS>
          </EVENT>
        </EVENTS></SESSION></SESSIONS>
        <CLUBS><CLUB name="Les Brown" code="LBS" nation="ZIM">
          <ATHLETES><ATHLETE athleteid="7" firstname="Peter" lastname="Wetzlar" gender="F" birthdate="2015-06-20" license="{$member->membership_number}">
            <ENTRIES><ENTRY eventid="99" entrytime="00:00:32.10"/></ENTRIES>
          </ATHLETE></ATHLETES>
        </CLUB></CLUBS>
      </MEET></MEETS>
    </LENEX>
    XML;

    $outcome = app(LenexImporter::class)->import($this->gala, $xml);

    expect($outcome['entered'])->toBe(1);
    expect($outcome['skipped'])->toBe(0);

    $entry = GalaEntry::query()->where('member_id', $member->getKey())->sole();
    expect($entry->gala_event_id)->toBe($this->event->getKey());
    expect($entry->entry_time_ms)->toBe(32_100);
});

it('reports a swimmer who is not registered rather than creating one', function (): void {
    $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <LENEX version="3.0">
      <MEETS><MEET name="Club file" course="LCM">
        <SESSIONS><SESSION number="1" date="2026-11-14"><EVENTS>
          <EVENT eventid="1" number="1" gender="F" round="TIM">
            <SWIMSTYLE distance="50" relaycount="1" stroke="FREE"/>
            <AGEGROUPS><AGEGROUP agegroupid="1" agemin="11" agemax="12" gender="F"/></AGEGROUPS>
          </EVENT>
        </EVENTS></SESSION></SESSIONS>
        <CLUBS><CLUB name="Ghost SC">
          <ATHLETES><ATHLETE athleteid="1" firstname="Nobody" lastname="Here" gender="F" birthdate="2015-01-01" license="ZZZ999">
            <ENTRIES><ENTRY eventid="1" entrytime="00:00:30.00"/></ENTRIES>
          </ATHLETE></ATHLETES>
        </CLUB></CLUBS>
      </MEET></MEETS>
    </LENEX>
    XML;

    $outcome = app(LenexImporter::class)->import($this->gala, $xml);

    expect($outcome['entered'])->toBe(0);
    expect($outcome['skipped'])->toBe(1);
    expect($outcome['problems'][0])->toContain('Nobody Here');
    expect(Member::query()->where('last_name', 'Here')->exists())->toBeFalse();
});

it('refuses a file that is not Lenex', function (): void {
    app(LenexImporter::class)->import($this->gala, '<html><body>not lenex</body></html>');
})->throws(LenexException::class);

it('enforces the same eligibility rules on an imported entry as on a typed one', function (): void {
    // The club's province has not settled with the national body, so none of
    // its swimmers may be entered — by hand or from a club's export file.
    $this->province->forceFill(['affiliation_status' => AffiliationStatus::Lapsed])->save();

    $member = Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'first_name' => 'Unaffiliated',
        'last_name' => 'Swimmer',
        'date_of_birth' => '2015-02-02',
        'gender' => 'female',
        'status' => MemberStatus::Active,
    ]);

    $xml = <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <LENEX version="3.0">
      <MEETS><MEET name="Club file" course="LCM">
        <SESSIONS><SESSION number="1" date="2026-11-14"><EVENTS>
          <EVENT eventid="1" number="1" gender="F" round="TIM">
            <SWIMSTYLE distance="50" relaycount="1" stroke="FREE"/>
            <AGEGROUPS><AGEGROUP agegroupid="1" agemin="11" agemax="12" gender="F"/></AGEGROUPS>
          </EVENT>
        </EVENTS></SESSION></SESSIONS>
        <CLUBS><CLUB name="Les Brown">
          <ATHLETES><ATHLETE athleteid="1" firstname="Unaffiliated" lastname="Swimmer" gender="F" birthdate="2015-02-02" license="{$member->membership_number}">
            <ENTRIES><ENTRY eventid="1" entrytime="00:00:30.00"/></ENTRIES>
          </ATHLETE></ATHLETES>
        </CLUB></CLUBS>
      </MEET></MEETS>
    </LENEX>
    XML;

    $outcome = app(LenexImporter::class)->import($this->gala, $xml);

    expect($outcome['entered'])->toBe(0);
    expect($outcome['skipped'])->toBe(1);
    expect(GalaEntry::query()->where('member_id', $member->getKey())->exists())->toBeFalse();
});

it('imports a CSV entry sheet and reports the rows it could not place', function (): void {
    $member = registeredSwimmer('Amy', 'Wicks', '2015-03-03', $this);

    $path = tempnam(sys_get_temp_dir(), 'entries').'.csv';
    file_put_contents($path, implode("\n", [
        'membership_number,swimmer,event_number,entry_time',
        "{$member->membership_number},Amy Wicks,1,33.20",
        'NOTAMEMBER,Who Knows,1,30.00',
        "{$member->membership_number},Amy Wicks,77,30.00",
        '',
    ]));

    $outcome = app(EntryCsvImporter::class)->import($this->gala, $path);
    unlink($path);

    expect($outcome['entered'])->toBe(1);
    expect($outcome['skipped'])->toBe(2);
    expect($outcome['problems'])->toHaveCount(2);

    $entry = GalaEntry::query()->where('member_id', $member->getKey())->sole();
    expect($entry->entry_time_ms)->toBe(33_200);
});

it('updates an existing entry rather than duplicating it on re-import', function (): void {
    $member = registeredSwimmer('Repeat', 'Import', '2015-05-05', $this);

    $path = tempnam(sys_get_temp_dir(), 'entries').'.csv';
    $write = fn (string $time) => file_put_contents($path, implode("\n", [
        'membership_number,swimmer,event_number,entry_time',
        "{$member->membership_number},Repeat Import,1,{$time}",
        '',
    ]));

    $write('33.20');
    app(EntryCsvImporter::class)->import($this->gala, $path);

    $write('31.90');
    $second = app(EntryCsvImporter::class)->import($this->gala, $path);
    unlink($path);

    expect($second['entered'])->toBe(0);
    expect($second['updated'])->toBe(1);
    expect(GalaEntry::query()->where('member_id', $member->getKey())->count())->toBe(1);
    expect(GalaEntry::query()->where('member_id', $member->getKey())->sole()->entry_time_ms)->toBe(31_900);
});

it('lets an administrator download the Lenex file', function (): void {
    $user = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);

    $this->actingAs($user)
        ->get(route('admin.galas.lenex', $this->gala))
        ->assertOk()
        ->assertHeader('content-type', 'application/xml')
        ->assertHeader('content-disposition', 'attachment; filename="'.app(LenexExporter::class)->filename($this->gala, false).'"');
});

it('round-trips a time through the Lenex format', function (int $milliseconds, string $lenex): void {
    expect(SwimTime::toLenex($milliseconds))->toBe($lenex);
    expect(SwimTime::fromLenex($lenex))->toBe($milliseconds);
})->with([
    'under a minute' => [30_880, '00:00:30.88'],
    'over a minute' => [71_450, '00:01:11.45'],
    'over an hour' => [3_671_450, '01:01:11.45'],
]);

it('reads a Lenex no-time as null', function (): void {
    expect(SwimTime::fromLenex('00:00:00.00'))->toBeNull();
    expect(SwimTime::fromLenex('NT'))->toBeNull();
    expect(SwimTime::fromLenex(null))->toBeNull();
});
