<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Gala;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The results file a gala was published from.
 *
 * Times are recorded in MeetManager on the day, not typed into this system,
 * so the export it produces is the official record of what was swum. A gala
 * may carry more than one: a two-day meet is usually exported per day, and a
 * corrected reissue has to sit beside the original rather than quietly
 * replace it, so that anybody who downloaded the first can see there was a
 * second.
 */
class GalaResultsFileController extends Controller
{
    /**
     * Where results files live. A private disk with a download route in
     * front, rather than the public one: an unpublished gala's results must
     * not be reachable by guessing a filename, and nothing here depends on
     * `storage:link` having been run on the server.
     */
    private const DISK = 'local';

    public function store(Request $request, Gala $gala): RedirectResponse
    {
        $this->authorize('publishResults', $gala);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'file.mimes' => 'Results must be a PDF, which is what MeetManager exports.',
            'file.max' => 'That file is over 20MB. Export it again without the splash pages if you can.',
        ]);

        $file = $request->file('file');

        $path = $file->storeAs(
            'gala-results/'.$gala->getKey(),
            Str::uuid()->toString().'.pdf',
            self::DISK,
        );

        Document::create([
            'gala_id' => $gala->getKey(),
            'organization_id' => $gala->host_organization_id,
            'season_id' => $gala->season_id,
            'uploaded_by' => $request->user()->getKey(),
            'title' => $validated['title'],
            'category' => 'results',
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => $file->getSize(),
            'is_public' => true,
        ]);

        return back()->with('success', $validated['title'].' has been attached to '.$gala->name.'.');
    }

    public function destroy(Request $request, Gala $gala, Document $document): RedirectResponse
    {
        $this->authorize('publishResults', $gala);

        abort_unless($document->gala_id === $gala->getKey(), 404);

        // The row goes first: a file left on disk is waste, but a row
        // pointing at a file that is gone is a broken download.
        $path = $document->file_path;
        $document->delete();
        Storage::disk(self::DISK)->delete($path);

        return back()->with('success', $document->title.' has been removed.');
    }
}
