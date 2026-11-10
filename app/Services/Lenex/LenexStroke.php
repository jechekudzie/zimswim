<?php

namespace App\Services\Lenex;

use App\Enums\Stroke;

/**
 * Translates between our stroke names and the Lenex SWIMSTYLE codes that
 * Team Manager, Meet Manager and Splash all speak.
 */
final class LenexStroke
{
    public static function toLenex(Stroke $stroke): string
    {
        return match ($stroke) {
            Stroke::Freestyle, Stroke::FreestyleRelay => 'FREE',
            Stroke::Backstroke => 'BACK',
            Stroke::Breaststroke => 'BREAST',
            Stroke::Butterfly => 'FLY',
            Stroke::IndividualMedley, Stroke::MedleyRelay => 'MEDLEY',
        };
    }

    /**
     * A relay count above one turns FREE and MEDLEY into their relay strokes,
     * which is the only thing separating them in Lenex.
     */
    public static function fromLenex(string $code, int $relayCount = 1): Stroke
    {
        $relay = $relayCount > 1;

        return match (strtoupper($code)) {
            'BACK' => Stroke::Backstroke,
            'BREAST' => Stroke::Breaststroke,
            'FLY' => Stroke::Butterfly,
            'MEDLEY', 'IM' => $relay ? Stroke::MedleyRelay : Stroke::IndividualMedley,
            default => $relay ? Stroke::FreestyleRelay : Stroke::Freestyle,
        };
    }
}
