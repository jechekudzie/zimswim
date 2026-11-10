<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A comma separated download of whatever a listing is currently showing.
 *
 * Rows are written as they are read rather than gathered first, so exporting
 * the national register does not depend on the whole register fitting in
 * memory. Pair it with a lazy query.
 */
final class Csv
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<mixed, array<int, string|int|float|bool|null>>  $rows
     */
    public static function download(string $subject, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($headings, $rows): void {
                $handle = fopen('php://output', 'wb');

                if ($handle === false) {
                    return;
                }

                /*
                 * Excel reads a CSV in the machine's legacy encoding unless
                 * the file opens with a byte order mark, which is what turns
                 * a name like Chikwava into mojibake on a Windows desktop.
                 */
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, $headings, ',', '"', '');

                foreach ($rows as $row) {
                    fputcsv($handle, array_map(self::cell(...), $row), ',', '"', '');
                }

                fclose($handle);
            },
            self::filename($subject),
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * Booleans are written as words, because a spreadsheet shows a raw
     * boolean as 1 and an empty cell, which nobody can read.
     */
    private static function cell(string|int|float|bool|null $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'Yes' : 'No',
            $value === null => '',
            default => (string) $value,
        };
    }

    private static function filename(string $subject): string
    {
        return sprintf(
            'zimbabwe-swimming-%s-%s.csv',
            Str::slug($subject),
            now()->format('Y-m-d'),
        );
    }
}
