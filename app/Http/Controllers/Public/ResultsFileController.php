<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Gala;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serving a gala's results file to whoever wants it.
 *
 * Streamed through a route rather than linked straight at the disk, so an
 * unpublished gala's results cannot be reached by guessing a filename, and
 * so the file keeps a readable name when it lands in somebody's Downloads
 * instead of the uuid it is stored under.
 */
class ResultsFileController extends Controller
{
    public function __invoke(Gala $gala, Document $document): StreamedResponse
    {
        abort_unless($gala->is_published, 404);
        abort_unless($document->gala_id === $gala->getKey(), 404);
        abort_unless($document->is_public, 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($document->file_path), 404);

        return $disk->download(
            $document->file_path,
            str($gala->name.' '.$document->title)->slug().'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
