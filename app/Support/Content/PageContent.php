<?php

namespace App\Support\Content;

use App\Models\ContentBlock;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves a page's wording: what the federation has changed, over what the
 * page shipped with.
 *
 * Read once per request and cached for the request, because several bands of
 * one page ask for it. The result is a flat map the page prints directly, so
 * a template never has to decide whether a block exists.
 */
class PageContent
{
    /** @var array<string, array<string, string>> */
    private array $resolved = [];

    /**
     * Every block on a page, with overrides applied.
     *
     * @return array<string, string>
     */
    public function forPage(string $page): array
    {
        if (isset($this->resolved[$page])) {
            return $this->resolved[$page];
        }

        $blocks = ContentCatalogue::blocksFor($page);

        if ($blocks === []) {
            return $this->resolved[$page] = [];
        }

        $overrides = ContentBlock::query()
            ->whereIn('key', array_keys($blocks))
            ->get()
            ->keyBy('key');

        $values = [];

        foreach ($blocks as $key => $block) {
            $override = $overrides->get($key);

            $values[$key] = $block['type'] === 'image'
                ? $this->image($override)
                : $this->text($override, $block['default']);
        }

        return $this->resolved[$page] = $values;
    }

    /**
     * A changed wording, or what the page shipped with.
     *
     * A row holding only whitespace counts as cleared, because that is what
     * somebody emptying a box means by it.
     */
    private function text(?ContentBlock $override, string $default): string
    {
        $value = trim((string) $override?->value);

        return $value === '' ? $default : $value;
    }

    /**
     * An uploaded picture's public URL, or an empty string to tell the page
     * to keep the photograph it ships with.
     */
    private function image(?ContentBlock $override): string
    {
        if ($override?->image_path === null) {
            return '';
        }

        return Storage::disk('public')->url($override->image_path);
    }
}
