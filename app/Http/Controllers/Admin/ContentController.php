<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Support\Content\ContentCatalogue;
use App\Support\Content\RichText;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Changing the wording and pictures on the public pages.
 *
 * Every box shows what the page currently says, whether that is the wording
 * it shipped with or something the federation has written since. Emptying a
 * box is how you undo: the row is removed and the original comes back, which
 * is why there is no separate reset button to go looking for.
 */
class ContentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize(Permissions::MANAGE_CONTENT);

        $page = $request->string('page')->toString();
        $pages = ContentCatalogue::pages();

        if (! array_key_exists($page, $pages)) {
            $page = (string) array_key_first($pages);
        }

        /*
         * Keyed as a plain array rather than a collection: array access with
         * `??` says plainly that a block may have no override, where
         * `Collection::get()` reads as though one is always there.
         *
         * @var array<string, ContentBlock> $overrides
         */
        $overrides = ContentBlock::query()
            ->whereIn('key', array_keys(ContentCatalogue::blocksFor($page)))
            ->with('editor:id,name')
            ->get()
            ->keyBy('key')
            ->all();

        return Inertia::render('admin/content/index', [
            'page' => $page,
            'pages' => collect($pages)
                ->map(fn (array $definition, string $slug): array => [
                    'value' => $slug,
                    'label' => $definition['label'],
                ])
                ->values(),
            'groups' => collect($pages[$page]['groups'])
                ->map(fn (array $group, string $slug): array => [
                    'key' => $slug,
                    'label' => $group['label'],
                    'blocks' => collect($group['blocks'])
                        ->map(function (array $block, string $key) use ($overrides): array {
                            $override = $overrides[$key] ?? null;

                            return [
                                'key' => $key,
                                'label' => $block['label'],
                                'type' => $block['type'],
                                'hint' => $block['hint'] ?? null,
                                'original' => $block['default'],
                                // What is in the box: what they wrote, or the
                                // original, so nobody has to guess which. A
                                // description arrives as markup, because that
                                // is what the editor opens on.
                                'value' => $this->current($block, $override),
                                'image_url' => $override?->image_path === null
                                    ? null
                                    : Storage::disk('public')->url($override->image_path),
                                'changed' => $override !== null,
                                'changed_by' => $override?->editor?->name,
                                'changed_on' => $override?->updated_at?->toDateString(),
                            ];
                        })
                        ->values(),
                ])
                ->values(),
        ]);
    }

    /**
     * What a box should open on.
     *
     * @param  array{label: string, type: string, default: string, hint?: string}  $block
     */
    private function current(array $block, ?ContentBlock $override): string
    {
        $written = trim((string) $override?->value);

        if ($block['type'] !== 'rich') {
            return $written === '' ? $block['default'] : $written;
        }

        return $written === ''
            ? RichText::canonical($block['default'])
            : RichText::sanitise($written);
    }

    /**
     * Save the wording for one page.
     *
     * Anything matching what the page shipped with is removed rather than
     * stored, so the table holds only genuine changes and the catalogue
     * stays the single account of what the site says by default.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize(Permissions::MANAGE_CONTENT);

        $validated = $request->validate([
            'page' => ['required', 'string', Rule::in(array_keys(ContentCatalogue::pages()))],
            'blocks' => ['array'],
            'blocks.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $catalogue = ContentCatalogue::blocksFor($validated['page']);
        $changed = 0;

        foreach ($validated['blocks'] ?? [] as $key => $value) {
            if (! isset($catalogue[$key]) || $catalogue[$key]['type'] === 'image') {
                continue;
            }

            /*
             * A description is reduced to the vocabulary the page allows
             * before anything else happens to it, so what is compared,
             * stored and later printed are all one and the same string.
             */
            $value = $catalogue[$key]['type'] === 'rich'
                ? RichText::sanitise((string) $value)
                : trim((string) $value);

            $isOriginal = $value === '' || $value === ($catalogue[$key]['type'] === 'rich'
                ? RichText::canonical($catalogue[$key]['default'])
                : trim($catalogue[$key]['default']));

            if ($isOriginal) {
                $changed += (int) (bool) ContentBlock::where('key', $key)->whereNull('image_path')->delete();

                continue;
            }

            $block = ContentBlock::firstOrNew(['key' => $key]);

            if ($block->value !== $value) {
                $changed++;
            }

            $block->fill(['value' => $value, 'updated_by' => $request->user()->getKey()])->save();
        }

        return back()->with('success', match (true) {
            $changed === 0 => 'Nothing had changed, so nothing was saved.',
            $changed === 1 => 'One change saved. The public page is updated.',
            default => $changed.' changes saved. The public page is updated.',
        });
    }

    /**
     * Replace one of the photographs on a page.
     */
    public function image(Request $request): RedirectResponse
    {
        $this->authorize(Permissions::MANAGE_CONTENT);

        $validated = $request->validate([
            'key' => ['required', 'string', Rule::in(array_keys(ContentCatalogue::all()))],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'image.max' => 'That picture is over 5MB. A photograph for the web rarely needs to be.',
        ]);

        abort_unless(ContentCatalogue::all()[$validated['key']]['type'] === 'image', 422);

        $block = ContentBlock::firstOrNew(['key' => $validated['key']]);
        $previous = $block->image_path;

        $path = $request->file('image')->storeAs(
            'content',
            Str::uuid()->toString().'.'.$request->file('image')->extension(),
            'public',
        );

        $block->fill(['image_path' => $path, 'updated_by' => $request->user()->getKey()])->save();

        // Only once the new one is safely saved, so a failed upload cannot
        // leave the page with no picture at all.
        if ($previous !== null) {
            Storage::disk('public')->delete($previous);
        }

        return back()->with('success', 'The picture has been changed.');
    }

    /**
     * Put back the photograph the page ships with.
     */
    public function resetImage(Request $request): RedirectResponse
    {
        $this->authorize(Permissions::MANAGE_CONTENT);

        $validated = $request->validate([
            'key' => ['required', 'string', Rule::in(array_keys(ContentCatalogue::all()))],
        ]);

        $block = ContentBlock::where('key', $validated['key'])->first();

        if ($block?->image_path !== null) {
            Storage::disk('public')->delete($block->image_path);
            $block->value === null ? $block->delete() : $block->update(['image_path' => null]);
        }

        return back()->with('success', 'The original photograph is back.');
    }
}
