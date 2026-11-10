<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Swim times are held as whole milliseconds so comparisons, seeding and record
 * checks never depend on string parsing.
 */
final class SwimTime
{
    /**
     * Render milliseconds as a swim time: 1:02.34, or 58.07 under a minute.
     */
    public static function format(int $milliseconds): string
    {
        $hundredths = (int) round($milliseconds / 10);
        $totalSeconds = intdiv($hundredths, 100);
        $remainder = $hundredths % 100;
        $minutes = intdiv($totalSeconds, 60);
        $seconds = $totalSeconds % 60;

        if ($minutes === 0) {
            return sprintf('%d.%02d', $seconds, $remainder);
        }

        return sprintf('%d:%02d.%02d', $minutes, $seconds, $remainder);
    }

    /**
     * Parse a swim time written as 1:02.34, 58.07 or 1:02:34 into milliseconds.
     */
    public static function parse(string $time): int
    {
        $trimmed = trim($time);

        if ($trimmed === '' || strcasecmp($trimmed, 'NT') === 0) {
            throw new InvalidArgumentException('A swim time is required.');
        }

        if (! preg_match('/^(?:(\d+):)?(\d{1,2})[.:](\d{1,2})$/', $trimmed, $matches)) {
            throw new InvalidArgumentException("Could not read [{$time}] as a swim time.");
        }

        $minutes = (int) $matches[1];
        $seconds = (int) $matches[2];
        $fraction = str_pad($matches[3], 2, '0');

        if ($seconds > 59) {
            throw new InvalidArgumentException("Could not read [{$time}] as a swim time.");
        }

        return (($minutes * 60) + $seconds) * 1000 + ((int) $fraction) * 10;
    }

    /**
     * Render milliseconds in the Lenex swimtime format, NN:NN:NN.NN, which is
     * always hours, minutes, seconds and hundredths.
     */
    public static function toLenex(int $milliseconds): string
    {
        $hundredths = (int) round($milliseconds / 10);
        $totalSeconds = intdiv($hundredths, 100);

        return sprintf(
            '%02d:%02d:%02d.%02d',
            intdiv($totalSeconds, 3600),
            intdiv($totalSeconds % 3600, 60),
            $totalSeconds % 60,
            $hundredths % 100,
        );
    }

    /**
     * Read a Lenex swimtime. "NT" and the placeholder 00:00:00.00 both mean
     * no time was declared.
     */
    public static function fromLenex(?string $time): ?int
    {
        $trimmed = trim((string) $time);

        if (! preg_match('/^(\d{1,2}):(\d{1,2}):(\d{1,2})\.(\d{1,2})$/', $trimmed, $matches)) {
            return null;
        }

        $milliseconds = ((int) $matches[1] * 3600 + (int) $matches[2] * 60 + (int) $matches[3]) * 1000
            + (int) str_pad($matches[4], 2, '0') * 10;

        return $milliseconds === 0 ? null : $milliseconds;
    }

    /**
     * Read a time, returning null for "NT" and anything unreadable.
     */
    public static function tryParse(?string $time): ?int
    {
        if ($time === null) {
            return null;
        }

        try {
            return self::parse($time);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
