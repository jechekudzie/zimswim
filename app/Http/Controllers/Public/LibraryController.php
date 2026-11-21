<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The handbook, the rules and the forms, in one place.
 *
 * Grouped by what a thing is rather than listed by date, because somebody
 * arrives here looking for the handbook or for a transfer form, not for
 * whatever was added most recently.
 */
class LibraryController extends Controller
{
    /** The order the shelves are read in. */
    private const ORDER = ['handbook', 'rules', 'forms', 'policy', 'minutes', 'general'];

    private const LABELS = [
        'handbook' => 'Handbook',
        'rules' => 'Rules',
        'forms' => 'Forms',
        'policy' => 'Policies',
        'minutes' => 'Minutes',
        'general' => 'Other documents',
    ];

    public function index(): Response
    {
        $documents = Document::query()
            ->public()
            // A gala's results belong on the results page, with the gala.
            ->whereNull('gala_id')
            ->with(['season:id,name', 'organization:id,name'])
            ->orderBy('title')
            ->get();

        $shelves = collect(self::ORDER)
            ->map(fn (string $category): array => [
                'key' => $category,
                'label' => self::LABELS[$category],
                'documents' => $documents
                    ->where('category', $category)
                    ->map(fn (Document $document): array => [
                        'title' => $document->title,
                        'slug' => $document->slug,
                        'description' => $document->description,
                        'size' => $document->humanFileSize(),
                        'season' => $document->season?->name,
                        'issued_by' => $document->organization?->name,
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $shelf): bool => $shelf['documents'] !== [])
            ->values()
            ->all();

        return Inertia::render('public/library', [
            'shelves' => $shelves,
            'total' => $documents->count(),
        ]);
    }

    /**
     * Served through here rather than linked at the disk, so a document that
     * is not public cannot be reached by guessing its address.
     */
    public function download(Document $document): StreamedResponse
    {
        abort_unless($document->is_public, 404);
        abort_unless($document->gala_id === null, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->file_path), 404);

        $extension = pathinfo($document->file_path, PATHINFO_EXTENSION);

        return $disk->download(
            $document->file_path,
            str($document->title)->slug().($extension === '' ? '' : '.'.$extension),
        );
    }
}
