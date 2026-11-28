<?php

namespace Database\Seeders;

use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\GalaSession;
use Illuminate\Database\Seeder;

/**
 * The published programmes of events for the national galas.
 *
 * Every programme here is typed from the National Handbook 2026/2027. Where
 * the handbook contradicts itself the deviation is noted against the gala it
 * belongs to, so the next edition can be checked against it.
 */
class GalaProgrammeSeeder extends Seeder
{
    /**
     * The running order within the gala currently being seeded.
     *
     * The handbook's own numbering is not always in order, and one of the
     * programmes has no numbers at all, so the order events are written in
     * is what the programme is printed in.
     */
    private int $position = 0;

    public function run(): void
    {
        $this->sprintGala();
        $this->juniorLevelOneChampionships();
        $this->seniorNationals();
        $this->juniorNationals();
    }

    private function sprintGala(): void
    {
        $gala = Gala::where('slug', 'national-sprint-gala-2026')->first();

        if ($gala === null) {
            return;
        }

        $friday = GalaSession::updateOrCreate(
            ['gala_id' => $gala->getKey(), 'position' => 1],
            ['name' => 'Friday', 'held_on' => '2026-10-16', 'warmup_at' => '14:00', 'starts_at' => '15:30'],
        );

        $saturday = GalaSession::updateOrCreate(
            ['gala_id' => $gala->getKey(), 'position' => 2],
            ['name' => 'Saturday AM', 'held_on' => '2026-10-17', 'warmup_at' => '06:30', 'starts_at' => '08:30'],
        );

        $programme = [
            [101, $friday, 'girls', 'Open', 200, Stroke::IndividualMedley],
            [102, $friday, 'boys', 'Open', 200, Stroke::IndividualMedley],
            [103, $friday, 'girls', 'Open', 200, Stroke::Freestyle],
            [104, $friday, 'boys', 'Open', 200, Stroke::Freestyle],
            [201, $saturday, 'girls', 'Open', 50, Stroke::Butterfly],
            [202, $saturday, 'boys', 'Open', 50, Stroke::Butterfly],
            [203, $saturday, 'girls', 'Open', 100, Stroke::Breaststroke],
            [204, $saturday, 'boys', 'Open', 100, Stroke::Breaststroke],
            [205, $saturday, 'girls', 'Open', 50, Stroke::Backstroke],
            [206, $saturday, 'boys', 'Open', 50, Stroke::Backstroke],
            [207, $saturday, 'girls', 'Open', 100, Stroke::Freestyle],
            [208, $saturday, 'boys', 'Open', 100, Stroke::Freestyle],
            [209, $saturday, 'girls', 'Open', 50, Stroke::Breaststroke],
            [210, $saturday, 'boys', 'Open', 50, Stroke::Breaststroke],
            [211, $saturday, 'girls', 'Open', 100, Stroke::Butterfly],
            [212, $saturday, 'boys', 'Open', 100, Stroke::Butterfly],
            [213, $saturday, 'girls', 'Open', 50, Stroke::Freestyle],
            [214, $saturday, 'boys', 'Open', 50, Stroke::Freestyle],
            [215, $saturday, 'girls', 'Open', 100, Stroke::Backstroke],
            [216, $saturday, 'boys', 'Open', 100, Stroke::Backstroke],
        ];

        foreach ($programme as [$number, $session, $gender, $ageGroup, $distance, $stroke]) {
            GalaEvent::updateOrCreate(
                ['gala_id' => $gala->getKey(), 'event_number' => $number],
                [
                    'position' => $number,
                    'gala_session_id' => $session->getKey(),
                    'gender' => $gender,
                    'age_group' => $ageGroup,
                    'min_age' => 8,
                    'stroke' => $stroke,
                    'distance' => $distance,
                    'round' => 'timed_final',
                ],
            );
        }

        $this->addRelays($gala, $saturday);
        $this->addFinals($gala, $saturday);
    }

    /**
     * The relays that close the Saturday session.
     */
    private function addRelays(Gala $gala, GalaSession $session): void
    {
        $relays = [
            [217, 'girls', 200, Stroke::FreestyleRelay],
            [218, 'boys', 200, Stroke::FreestyleRelay],
            [219, 'mixed', 200, Stroke::MedleyRelay],
        ];

        foreach ($relays as [$number, $gender, $distance, $stroke]) {
            GalaEvent::updateOrCreate(
                ['gala_id' => $gala->getKey(), 'event_number' => $number],
                [
                    'position' => $number,
                    'gala_session_id' => $session->getKey(),
                    'gender' => $gender,
                    'age_group' => 'Open',
                    'min_age' => 8,
                    'stroke' => $stroke,
                    'distance' => $distance,
                    'round' => 'timed_final',
                    'is_relay' => true,
                    'relay_legs' => 4,
                ],
            );
        }
    }

    /**
     * The 100m freestyle is swum as heats and a final, so the programme shows
     * both halves of a progression.
     */
    private function addFinals(Gala $gala, GalaSession $session): void
    {
        $pairs = [
            ['heats' => 207, 'final' => 251, 'gender' => 'girls'],
            ['heats' => 208, 'final' => 252, 'gender' => 'boys'],
        ];

        foreach ($pairs as $pair) {
            $heats = GalaEvent::where('gala_id', $gala->getKey())
                ->where('event_number', $pair['heats'])
                ->first();

            if ($heats === null) {
                continue;
            }

            $heats->forceFill(['round' => 'heat'])->save();

            GalaEvent::updateOrCreate(
                ['gala_id' => $gala->getKey(), 'event_number' => $pair['final']],
                [
                    'position' => $pair['final'],
                    'gala_session_id' => $session->getKey(),
                    'gender' => $pair['gender'],
                    'age_group' => 'Open',
                    'min_age' => 8,
                    'stroke' => Stroke::Freestyle,
                    'distance' => 100,
                    'round' => 'final',
                    'parent_event_id' => $heats->getKey(),
                    'qualifiers' => 8,
                ],
            );
        }
    }

    /**
     * The National Junior Level 1 Championships, as printed in the National
     * Handbook 2026/2027.
     *
     * Three days: seeded finals on the Friday, then heats in the morning and
     * finals in the afternoon on the Saturday and Sunday, each closing with
     * the relays. The handbook prints the programme against March 2026,
     * which is before this season opens; the dates used here are the ones
     * the same handbook's calendar gives for the meet.
     */
    private function juniorLevelOneChampionships(): void
    {
        $gala = Gala::where('slug', 'national-junior-level-1-championships-2027')->first();

        if ($gala === null) {
            return;
        }

        $this->position = 0;

        $friday = $this->session($gala, 1, 'Friday', $gala->starts_on->toDateString(), null, '15:00');
        $saturdayHeats = $this->session($gala, 2, 'Saturday morning heats', $gala->starts_on->addDay()->toDateString(), '07:30', '09:00');
        $saturdayFinals = $this->session($gala, 3, 'Saturday afternoon finals', $gala->starts_on->addDay()->toDateString(), '13:00', '14:00');
        $sundayHeats = $this->session($gala, 4, 'Sunday morning heats', $gala->starts_on->addDays(2)->toDateString(), '07:00', '08:00');
        $sundayFinals = $this->session($gala, 5, 'Sunday mid-morning finals', $gala->starts_on->addDays(2)->toDateString(), '10:30', '11:00');

        // The individual medley is swum as a seeded final on the Friday, so
        // it has no heat to be promoted from.
        foreach ([
            [101, 'girls', '7/8'], [102, 'boys', '7/8'],
            [103, 'girls', '9'], [104, 'boys', '9'],
            [105, 'girls', '10'], [106, 'boys', '10'],
            [107, 'girls', '11'], [108, 'boys', '11'],
            [109, 'girls', '12'], [110, 'boys', '12'],
        ] as [$number, $gender, $age]) {
            $this->event($gala, $friday, $number, $gender, $age, 100, Stroke::IndividualMedley, 'timed_final');
        }

        $saturday = [
            [201, 'boys', '12', 50, Stroke::Backstroke],
            [202, 'girls', '12', 50, Stroke::Freestyle],
            [203, 'boys', '11', 50, Stroke::Backstroke],
            [204, 'girls', '11', 50, Stroke::Butterfly],
            [205, 'boys', '10', 25, Stroke::Backstroke],
            [206, 'girls', '10', 25, Stroke::Breaststroke],
            [207, 'boys', '9', 25, Stroke::Freestyle],
            [208, 'girls', '9', 25, Stroke::Butterfly],
            [209, 'boys', '7/8', 25, Stroke::Butterfly],
            [210, 'girls', '7/8', 25, Stroke::Butterfly],
            [211, 'boys', '12', 50, Stroke::Breaststroke],
            [212, 'girls', '12', 50, Stroke::Backstroke],
            [213, 'boys', '11', 50, Stroke::Butterfly],
            [214, 'girls', '11', 50, Stroke::Backstroke],
            [215, 'boys', '10', 25, Stroke::Butterfly],
            [216, 'girls', '10', 25, Stroke::Backstroke],
            [217, 'boys', '9', 25, Stroke::Butterfly],
            [218, 'girls', '9', 25, Stroke::Freestyle],
            [219, 'boys', '7/8', 25, Stroke::Breaststroke],
            [220, 'girls', '7/8', 25, Stroke::Breaststroke],
        ];

        $sunday = [
            [301, 'girls', '12', 50, Stroke::Breaststroke],
            [302, 'boys', '12', 50, Stroke::Butterfly],
            [303, 'girls', '11', 50, Stroke::Freestyle],
            [304, 'boys', '11', 50, Stroke::Freestyle],
            [305, 'girls', '10', 25, Stroke::Butterfly],
            [306, 'boys', '10', 50, Stroke::Freestyle],
            [307, 'girls', '9', 25, Stroke::Backstroke],
            [308, 'boys', '9', 25, Stroke::Breaststroke],
            [309, 'girls', '7/8', 25, Stroke::Backstroke],
            [310, 'boys', '7/8', 25, Stroke::Backstroke],
            [311, 'girls', '12', 50, Stroke::Butterfly],
            [312, 'boys', '12', 50, Stroke::Freestyle],
            [313, 'girls', '11', 50, Stroke::Breaststroke],
            [314, 'boys', '11', 50, Stroke::Breaststroke],
            [315, 'girls', '10', 50, Stroke::Freestyle],
            [316, 'boys', '10', 25, Stroke::Breaststroke],
            [317, 'girls', '9', 25, Stroke::Breaststroke],
            [318, 'boys', '9', 25, Stroke::Backstroke],
            [319, 'girls', '7/8', 25, Stroke::Freestyle],
            [320, 'boys', '7/8', 25, Stroke::Freestyle],
        ];

        /*
         * Every individual event is swum twice under the same number: a heat
         * in the morning and the final it feeds in the afternoon. The final
         * points back at its heat so the finals screen can promote from it.
         */
        foreach ([[$saturday, $saturdayHeats, $saturdayFinals], [$sunday, $sundayHeats, $sundayFinals]] as [$events, $heatSession, $finalSession]) {
            foreach ($events as [$number, $gender, $age, $distance, $stroke]) {
                $heat = $this->event($gala, $heatSession, $number, $gender, $age, $distance, $stroke, 'heat');
                $this->event($gala, $finalSession, $number, $gender, $age, $distance, $stroke, 'final', $heat);
            }
        }

        // Freestyle for the boys and medley for the girls on the Saturday,
        // the other way round on the Sunday, exactly as the handbook prints.
        $relays = [
            [$saturdayFinals, 221, Stroke::FreestyleRelay, Stroke::MedleyRelay],
            [$sundayFinals, 321, Stroke::MedleyRelay, Stroke::FreestyleRelay],
        ];

        foreach ($relays as [$session, $first, $boysStroke, $girlsStroke]) {
            $number = $first;

            foreach (['12', '11', '10', '9', '7/8'] as $age) {
                $this->event($gala, $session, $number++, 'boys', $age, 100, $boysStroke, 'final', null, true);
                $this->event($gala, $session, $number++, 'girls', $age, 100, $girlsStroke, 'final', null, true);
            }
        }
    }

    /**
     * The Zimbabwe Senior National Championships, Bulawayo, 18-22 February 2027.
     *
     * Typed from the handbook's programme of events, with three deviations
     * the federation's results convenor has confirmed and will amend in the
     * next edition:
     *
     * 1. The printed programme skips the 21st, so its last two days are
     *    headed a day late. They are seeded here on the 21st and the 22nd.
     * 2. The last day is day five, so its events are numbered 5xx. The page
     *    numbers the 200m individual medley finals 401 and 402, which the
     *    100m butterfly finals already hold on the Sunday, and repeats the
     *    Sunday heats in place of the Monday ones.
     * 3. Saturday prints the 50m breaststroke heats twice and gives one
     *    200m backstroke final the number of a 200m butterfly final. The
     *    100m freestyle and 200m backstroke finals carry their own heats'
     *    numbers here, as every other day's finals do.
     */
    private function seniorNationals(): void
    {
        $gala = Gala::where('slug', 'senior-nationals-2027')->first();

        if ($gala === null) {
            return;
        }

        $this->position = 0;

        $thursday = $this->session($gala, 1, 'Thursday evening', '2027-02-18', null, '17:00');
        $fridayHeats = $this->session($gala, 2, 'Friday morning heats', '2027-02-19', null, '09:00');
        $fridayFinals = $this->session($gala, 3, 'Friday evening finals', '2027-02-19', null, '16:00');
        $saturdayHeats = $this->session($gala, 4, 'Saturday morning heats', '2027-02-20', null, '09:00');
        $saturdayFinals = $this->session($gala, 5, 'Saturday finals', '2027-02-20', null, null);
        $sundayHeats = $this->session($gala, 6, 'Sunday morning heats', '2027-02-21', null, '09:00');
        $sundayFinals = $this->session($gala, 7, 'Sunday evening finals', '2027-02-21', null, '15:30');
        $mondayHeats = $this->session($gala, 8, 'Monday morning heats', '2027-02-22', null, '09:00');
        $mondayFinals = $this->session($gala, 9, 'Monday mid-morning finals', '2027-02-22', null, null);

        // The 400m freestyle opens the championship as a timed final.
        foreach ([
            [101, 'girls', '12 & Under'], [102, 'boys', '12 & Under'],
            [103, 'girls', '13 Years'], [104, 'boys', '13 Years'],
            [105, 'girls', '14 Years'], [106, 'boys', '14 Years'],
            [107, 'girls', '15 Years'], [108, 'boys', '15 Years'],
            [109, 'girls', '16 Years'], [110, 'boys', '16 Years'],
            [111, 'girls', '17 & Over'], [112, 'boys', '17 & Over'],
        ] as [$number, $gender, $age]) {
            $this->event($gala, $thursday, $number, $gender, $age, 400, Stroke::Freestyle, 'timed_final');
        }

        /*
         * Everything swum as prelims and finals. The handbook prints no age
         * band against these, so none is claimed here; the gala's own rules
         * carry who may enter.
         */
        $prelims = [
            [$fridayHeats, $fridayFinals, [
                [201, 'girls', 200, Stroke::Freestyle],
                [202, 'boys', 200, Stroke::Freestyle],
                [203, 'girls', 50, Stroke::Backstroke],
                [204, 'boys', 50, Stroke::Backstroke],
                [205, 'girls', 100, Stroke::Breaststroke],
                [206, 'boys', 100, Stroke::Breaststroke],
            ]],
            [$saturdayHeats, $saturdayFinals, [
                [301, 'girls', 100, Stroke::Freestyle],
                [302, 'boys', 100, Stroke::Freestyle],
                [303, 'girls', 200, Stroke::Backstroke],
                [304, 'boys', 200, Stroke::Backstroke],
            ]],
            [$sundayHeats, $sundayFinals, [
                [401, 'girls', 100, Stroke::Butterfly],
                [402, 'boys', 100, Stroke::Butterfly],
                [403, 'girls', 200, Stroke::Breaststroke],
                [404, 'boys', 200, Stroke::Breaststroke],
                [405, 'girls', 100, Stroke::Backstroke],
                [406, 'boys', 100, Stroke::Backstroke],
                [407, 'girls', 50, Stroke::Freestyle],
                [408, 'boys', 50, Stroke::Freestyle],
            ]],
            [$mondayHeats, $mondayFinals, [
                [501, 'girls', 200, Stroke::IndividualMedley],
                [502, 'boys', 200, Stroke::IndividualMedley],
                [503, 'girls', 50, Stroke::Butterfly],
                [504, 'boys', 50, Stroke::Butterfly],
            ]],
        ];

        foreach ($prelims as [$heatSession, $finalSession, $events]) {
            foreach ($events as [$number, $gender, $distance, $stroke]) {
                $heat = $this->event($gala, $heatSession, $number, $gender, 'Open', $distance, $stroke, 'heat');
                $this->event($gala, $finalSession, $number, $gender, 'Open', $distance, $stroke, 'final', $heat);
            }
        }

        // The one event whose final is numbered apart from its heat.
        foreach ([[305, 309, 'girls'], [306, 310, 'boys']] as [$heatNumber, $finalNumber, $gender]) {
            $heat = $this->event($gala, $saturdayHeats, $heatNumber, $gender, 'Open', 50, Stroke::Breaststroke, 'heat');
            $this->event($gala, $saturdayFinals, $finalNumber, $gender, 'Open', 50, Stroke::Breaststroke, 'final', $heat);
        }

        // The distance events and the 200m butterfly are timed finals.
        foreach ([
            [$fridayFinals, 207, 'girls', '14 & Under', 400, Stroke::IndividualMedley],
            [$fridayFinals, 208, 'boys', '14 & Under', 400, Stroke::IndividualMedley],
            [$fridayFinals, 209, 'girls', '15 & Over', 400, Stroke::IndividualMedley],
            [$fridayFinals, 210, 'boys', '15 & Over', 400, Stroke::IndividualMedley],
            [$saturdayFinals, 307, 'girls', '14 & Under', 200, Stroke::Butterfly],
            [$saturdayFinals, 308, 'boys', '14 & Under', 200, Stroke::Butterfly],
            [$saturdayFinals, 311, 'girls', '15 & Over', 200, Stroke::Butterfly],
            [$saturdayFinals, 312, 'boys', '15 & Over', 200, Stroke::Butterfly],
            [$sundayFinals, 409, 'girls', '14 & Under', 800, Stroke::Freestyle],
            [$sundayFinals, 410, 'boys', '14 & Under', 800, Stroke::Freestyle],
            [$sundayFinals, 411, 'girls', '15 & Over', 800, Stroke::Freestyle],
            [$sundayFinals, 412, 'boys', '15 & Over', 800, Stroke::Freestyle],
            [$mondayFinals, 505, 'girls', '14 & Under', 1500, Stroke::Freestyle],
            [$mondayFinals, 506, 'boys', '14 & Under', 1500, Stroke::Freestyle],
            [$mondayFinals, 509, 'girls', '15 & Over', 1500, Stroke::Freestyle],
            [$mondayFinals, 510, 'boys', '15 & Over', 1500, Stroke::Freestyle],
        ] as [$session, $number, $gender, $age, $distance, $stroke]) {
            $this->event($gala, $session, $number, $gender, $age, $distance, $stroke, 'timed_final');
        }

        foreach ([
            [$fridayFinals, 211, 'girls', '14 & Under', 400, Stroke::FreestyleRelay],
            [$fridayFinals, 212, 'boys', '14 & Under', 400, Stroke::FreestyleRelay],
            [$fridayFinals, 213, 'girls', '15 & Over', 400, Stroke::FreestyleRelay],
            [$fridayFinals, 214, 'boys', '15 & Over', 400, Stroke::FreestyleRelay],
            [$saturdayFinals, 313, 'girls', '14 & Under', 400, Stroke::MedleyRelay],
            [$saturdayFinals, 314, 'boys', '14 & Under', 400, Stroke::MedleyRelay],
            [$saturdayFinals, 315, 'girls', '15 & Over', 400, Stroke::MedleyRelay],
            [$saturdayFinals, 316, 'boys', '15 & Over', 400, Stroke::MedleyRelay],
            [$sundayFinals, 415, 'girls', '14 & Under', 200, Stroke::FreestyleRelay],
            [$sundayFinals, 416, 'boys', '14 & Under', 200, Stroke::FreestyleRelay],
            [$sundayFinals, 417, 'girls', '15 & Over', 200, Stroke::FreestyleRelay],
            [$sundayFinals, 418, 'boys', '15 & Over', 200, Stroke::FreestyleRelay],
        ] as [$session, $number, $gender, $age, $distance, $stroke]) {
            $this->event($gala, $session, $number, $gender, $age, $distance, $stroke, 'timed_final', null, true);
        }
    }

    /**
     * The Zimbabwe Junior National Championships, Les Brown, 29-31 January 2027.
     *
     * The handbook prints this one as a plain list of races with an empty
     * Event column and no heats or finals split, each race carrying every
     * age group on one line. The federation has asked for it to go up as
     * printed rather than be numbered here, so it is: no event numbers, and
     * the running order is the order it is printed in. The day headings in
     * the handbook read "30th February" and "31st February"; the dates used
     * here are the ones the federation gave.
     */
    private function juniorNationals(): void
    {
        $gala = Gala::where('slug', 'junior-nationals-2027')->first();

        if ($gala === null) {
            return;
        }

        $this->position = 0;

        $individual = '10, 11, 12, 13-14, 15-16';
        $relay = '12 & Under, 13 & Over';

        $programme = [
            [1, 'Friday', '2027-01-29', [
                ['girls', $individual, 50, Stroke::Breaststroke],
                ['boys', $individual, 50, Stroke::Breaststroke],
                ['girls', $individual, 100, Stroke::Freestyle],
                ['boys', $individual, 100, Stroke::Freestyle],
                ['girls', $individual, 50, Stroke::Backstroke],
                ['boys', $individual, 50, Stroke::Backstroke],
                ['girls', $individual, 200, Stroke::IndividualMedley],
                ['boys', $individual, 200, Stroke::IndividualMedley],
                ['girls', $relay, 200, Stroke::MedleyRelay],
                ['boys', $relay, 200, Stroke::MedleyRelay],
                ['mixed', $relay, 200, Stroke::FreestyleRelay],
            ]],
            [2, 'Saturday', '2027-01-30', [
                ['girls', $individual, 200, Stroke::Freestyle],
                ['boys', $individual, 200, Stroke::Freestyle],
                ['girls', $individual, 100, Stroke::Backstroke],
                ['boys', $individual, 100, Stroke::Backstroke],
                ['girls', $individual, 50, Stroke::Butterfly],
                ['boys', $individual, 50, Stroke::Butterfly],
                ['girls', $individual, 100, Stroke::Breaststroke],
                ['boys', $individual, 100, Stroke::Breaststroke],
                ['girls', $relay, 400, Stroke::FreestyleRelay],
                ['boys', $relay, 400, Stroke::FreestyleRelay],
            ]],
            [3, 'Sunday', '2027-01-31', [
                ['girls', $individual, 200, Stroke::Backstroke],
                ['boys', $individual, 200, Stroke::Backstroke],
                ['girls', $individual, 200, Stroke::Breaststroke],
                ['boys', $individual, 200, Stroke::Breaststroke],
                ['girls', $individual, 100, Stroke::Butterfly],
                ['boys', $individual, 100, Stroke::Butterfly],
                ['girls', $individual, 50, Stroke::Freestyle],
                ['boys', $individual, 50, Stroke::Freestyle],
                ['girls', $relay, 200, Stroke::FreestyleRelay],
                ['boys', $relay, 200, Stroke::FreestyleRelay],
                ['mixed', $relay, 200, Stroke::MedleyRelay],
            ]],
        ];

        foreach ($programme as [$position, $name, $heldOn, $events]) {
            $session = $this->session($gala, $position, $name, $heldOn, null, '08:00');

            foreach ($events as [$gender, $age, $distance, $stroke]) {
                $this->event($gala, $session, null, $gender, $age, $distance, $stroke, 'timed_final', null, $stroke->isRelay());
            }
        }
    }

    private function session(Gala $gala, int $position, string $name, string $heldOn, ?string $warmup, ?string $start): GalaSession
    {
        return GalaSession::updateOrCreate(
            ['gala_id' => $gala->getKey(), 'position' => $position],
            ['name' => $name, 'held_on' => $heldOn, 'warmup_at' => $warmup, 'starts_at' => $start],
        );
    }

    /**
     * One event on the programme.
     *
     * The age group is printed as the handbook prints it — "7/8" rather than
     * a pair of numbers — and the ages it accepts are read off that, so the
     * entry checks and the printed programme cannot drift apart.
     */
    private function event(
        Gala $gala,
        GalaSession $session,
        ?int $number,
        string $gender,
        string $ageGroup,
        int $distance,
        Stroke $stroke,
        string $round,
        ?GalaEvent $heat = null,
        bool $isRelay = false,
    ): GalaEvent {
        [$minimum, $maximum] = $this->ages($ageGroup);

        $attributes = [
            'position' => ++$this->position,
            'gala_session_id' => $session->getKey(),
            'gender' => $gender,
            'age_group' => $ageGroup,
            'min_age' => $minimum,
            'max_age' => $maximum,
            'stroke' => $stroke,
            'distance' => $distance,
            'parent_event_id' => $heat?->getKey(),
            'is_relay' => $isRelay,
            'relay_legs' => $isRelay ? 4 : null,
        ];

        /*
         * An unnumbered programme has nothing to key on but its own running
         * order, so the position stands in for the number there.
         */
        return $number === null
            ? GalaEvent::updateOrCreate(
                ['gala_id' => $gala->getKey(), 'position' => $attributes['position']],
                [...$attributes, 'event_number' => null, 'round' => $round],
            )
            : GalaEvent::updateOrCreate(
                ['gala_id' => $gala->getKey(), 'event_number' => $number, 'round' => $round],
                $attributes,
            );
    }

    /**
     * The ages a printed age band accepts.
     *
     * The band is stored as the handbook prints it — "15 & Over", "13-14",
     * "7/8" — and the ages are read off that, so the entry checks and the
     * printed programme cannot drift apart.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function ages(string $ageGroup): array
    {
        $band = strtolower($ageGroup);

        if (str_contains($band, 'open')) {
            return [null, null];
        }

        preg_match_all('/\d+/', $band, $matches);

        $ages = array_map('intval', $matches[0]);

        if ($ages === []) {
            return [null, null];
        }

        $under = str_contains($band, 'under') || str_contains($band, '&u');
        $over = str_contains($band, 'over') || str_contains($band, '&o');

        return match (true) {
            // A band written as both, such as the relays' "12&U, 13 &O",
            // is open to everybody at the gala.
            $under && $over => [null, null],
            $under => [null, max($ages)],
            $over => [min($ages), null],
            default => [min($ages), max($ages)],
        };
    }
}
