<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Season;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The document library: the handbook, the rules, the forms clubs have to
 * fill in, and the minutes.
 *
 * Files are kept on the private disk and served through a route, not linked
 * straight at the folder they sit in. That is what makes "not public" mean
 * something: a document still being drafted cannot be reached by guessing
 * its address, and every file keeps a readable name when it lands in
 * somebody's downloads rather than the uuid it is stored under.
 *
 * Results files live in this same table but are attached to a gala and
 * managed from the gala's own screen, so they are left out here.
 */
class DocumentController extends Controller
{
    private const DISK = 'local';

    /** What a document can be filed under. */
    private const CATEGORIES = ['handbook', 'rules', 'forms', 'minutes', 'policy', 'general'];

    public function __construct(private readonly OrganizationContext $context) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Document::class);

        return Inertia::render('admin/documents/index', [
            'documents' => $this->filtered($request)
                ->paginate(20)
                ->withQueryString()
                ->through(fn (Document $document): array => [
                    'id' => $document->getKey(),
                    'slug' => $document->slug,
                    'title' => $document->title,
                    'category' => $document->category,
                    'description' => $document->description,
                    'size' => $document->humanFileSize(),
                    'season' => $document->season?->name,
                    'organization' => $document->organization?->name,
                    'is_public' => $document->is_public,
                    'uploaded_on' => $document->created_at?->toDateString(),
                ]),
            'filters' => [
                'search' => $request->string('search')->toString() ?: null,
                'category' => $request->string('category')->toString() ?: null,
            ],
            'categories' => $this->categories(),
            'seasons' => Season::query()
                ->orderByDesc('starts_on')
                ->get(['id', 'name'])
                ->map(fn (Season $season): array => [
                    'value' => $season->getKey(),
                    'label' => $season->name,
                ])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'season_id' => ['nullable', 'integer', 'exists:seasons,id'],
            'is_public' => ['boolean'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,csv', 'max:20480'],
        ], [
            'file.max' => 'That file is over 20MB. Split it, or compress it, before uploading.',
            'file.mimes' => 'Documents go up as PDF, Word, Excel or CSV.',
        ]);

        $file = $request->file('file');
        abort_if(is_array($file) || $file === null, 422);

        Document::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'season_id' => $validated['season_id'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
            'organization_id' => $this->context->currentId(),
            'uploaded_by' => $request->user()?->getKey(),
            'file_path' => $file->storeAs(
                'documents',
                Str::uuid()->toString().'.'.$file->extension(),
                self::DISK,
            ),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', $validated['title'].' has been added to the library.');
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $document->update($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'season_id' => ['nullable', 'integer', 'exists:seasons,id'],
            'is_public' => ['boolean'],
        ]));

        return back()->with('success', $document->title.' has been updated.');
    }

    /**
     * Reading a document from the admin, published or not.
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->download($document->file_path, $this->filename($document));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        Storage::disk(self::DISK)->delete($document->file_path);
        $document->delete();

        return back()->with('success', $document->title.' has been removed from the library.');
    }

    /**
     * A name somebody will recognise in their downloads folder.
     */
    private function filename(Document $document): string
    {
        $extension = pathinfo($document->file_path, PATHINFO_EXTENSION);

        return str($document->title)->slug().($extension === '' ? '' : '.'.$extension);
    }

    /**
     * @return Builder<Document>
     */
    private function filtered(Request $request): Builder
    {
        return Document::query()
            // A gala's results file is managed from the gala, not here.
            ->whereNull('gala_id')
            ->with(['season:id,name', 'organization:id,name'])
            ->when($request->string('category')->toString(), fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($request->string('search')->toString(), function (Builder $query, string $search): void {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $query->where('title', 'ilike', $like);
            })
            ->orderByDesc('created_at');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function categories(): array
    {
        return array_map(
            fn (string $category): array => ['value' => $category, 'label' => Str::headline($category)],
            self::CATEGORIES,
        );
    }
}
