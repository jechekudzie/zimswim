<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Services\Lenex\EntryCsvImporter;
use App\Services\Lenex\LenexException;
use App\Services\Lenex\LenexExporter;
use App\Services\Lenex\LenexImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Moving a gala in and out of the rest of the sport's software.
 *
 * Lenex is the format Team Manager, Meet Manager and Splash all read, so it
 * carries the programme, the start list and the results. The CSV route exists
 * for clubs who keep their squad in a spreadsheet.
 */
class MeetFileController extends Controller
{
    public function __construct(
        private readonly LenexExporter $exporter,
        private readonly LenexImporter $lenex,
        private readonly EntryCsvImporter $csv,
    ) {}

    /**
     * Download the gala as Lenex. Results are included once any have been
     * recorded, which is what a club or a results app wants afterwards.
     */
    public function exportLenex(Request $request, Gala $gala): Response
    {
        $this->authorize('view', $gala);

        $withResults = $request->boolean('results', $gala->results()->exists());
        $xml = $this->exporter->export($gala, $withResults);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="'.$this->exporter->filename($gala, $withResults).'"',
        ]);
    }

    /**
     * The blank entry spreadsheet for this gala.
     */
    public function template(Gala $gala): StreamedResponse
    {
        $this->authorize('view', $gala);

        $csv = $this->csv->template($gala);

        return response()->streamDownload(
            fn () => print ($csv),
            Str::slug($gala->name).'-entry-sheet.csv',
            ['Content-Type' => 'text/csv'],
        );
    }

    /**
     * Take in a club's entry file. The extension decides how it is read; both
     * paths run the same eligibility checks as a hand-typed entry.
     */
    public function import(Request $request, Gala $gala): RedirectResponse
    {
        $this->authorize('create', GalaEntry::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:8192', 'mimetypes:text/xml,application/xml,text/plain,text/csv,application/zip,application/octet-stream'],
        ]);

        $file = $validated['file'];
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $outcome = match ($extension) {
                'csv', 'txt' => $this->csv->import($gala, $file->getRealPath(), $request->user()->getKey()),
                'lef', 'lxf', 'xml' => $this->lenex->import(
                    $gala,
                    $this->lenex->read($file->getRealPath()),
                    $request->user()->getKey(),
                ),
                default => throw new LenexException(
                    'Upload a Lenex file (.lef or .lxf) or a CSV entry sheet.',
                ),
            };
        } catch (LenexException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        return back()->with('success', $this->summarise($outcome))->with('importProblems', array_slice($outcome['problems'], 0, 25));
    }

    /**
     * @param  array{entered: int, updated: int, skipped: int, problems: array<int, string>}  $outcome
     */
    private function summarise(array $outcome): string
    {
        $parts = [];

        if ($outcome['entered'] > 0) {
            $parts[] = $outcome['entered'].' '.Str::plural('entry', $outcome['entered']).' added';
        }

        if ($outcome['updated'] > 0) {
            $parts[] = $outcome['updated'].' updated';
        }

        if ($outcome['skipped'] > 0) {
            $parts[] = $outcome['skipped'].' skipped';
        }

        return $parts === []
            ? 'That file held nothing to import.'
            : ucfirst(implode(', ', $parts)).'.';
    }
}
