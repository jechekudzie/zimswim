<?php

namespace Database\Seeders;

use App\Enums\Stroke;
use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\GalaSession;
use Illuminate\Database\Seeder;

/**
 * The published programme of events for the National Sprint Gala.
 */
class GalaProgrammeSeeder extends Seeder
{
    public function run(): void
    {
        $this->sprintGala();
        $this->juniorLevelOneNationals();
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
     * The Zimbabwe Junior Level 1 Nationals, as printed in the National
     * Handbook 2026/2027.
     *
     * Three days: seeded finals on the Friday, then heats in the morning and
     * finals in the afternoon on the Saturday and Sunday, each closing with
     * the relays. The handbook prints the programme against March 2026,
     * which is before this season opens; the dates used here are the ones
     * the same handbook's calendar gives for the meet.
     */
    private function juniorLevelOneNationals(): void
    {
        $gala = Gala::where('slug', 'junior-level-1-nationals-2027')->first();

        if ($gala === null) {
            return;
        }

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

    private function session(Gala $gala, int $position, string $name, string $heldOn, ?string $warmup, string $start): GalaSession
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
        int $number,
        string $gender,
        string $ageGroup,
        int $distance,
        Stroke $stroke,
        string $round,
        ?GalaEvent $heat = null,
        bool $isRelay = false,
    ): GalaEvent {
        $ages = array_map('intval', explode('/', $ageGroup));

        return GalaEvent::updateOrCreate(
            ['gala_id' => $gala->getKey(), 'event_number' => $number, 'round' => $round],
            [
                'gala_session_id' => $session->getKey(),
                'gender' => $gender,
                'age_group' => $ageGroup,
                'min_age' => min($ages),
                'max_age' => max($ages),
                'stroke' => $stroke,
                'distance' => $distance,
                'parent_event_id' => $heat?->getKey(),
                'is_relay' => $isRelay,
                'relay_legs' => $isRelay ? 4 : null,
            ],
        );
    }
}
