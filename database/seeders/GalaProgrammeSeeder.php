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

            $heats->forceFill(['round' => 'heats'])->save();

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
}
