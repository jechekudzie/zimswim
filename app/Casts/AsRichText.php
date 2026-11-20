<?php

namespace App\Casts;

use App\Support\Content\RichText;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A field somebody writes in the editor rather than types into a box.
 *
 * Put on the attribute rather than left to each controller, because these
 * fields are written from forms, seeders, factories and the console, and read
 * by pages, mail and exports. One cast means neither end has to remember:
 * whatever is written is reduced to the vocabulary a page may show, and
 * whatever is read is safe to print, including rows that predate the editor
 * and hold plain prose.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class AsRichText implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $rendered = RichText::render(is_string($value) ? $value : null);

        // Null rather than an empty string, so `$gala->description` stays
        // something a template can test for in the usual way.
        return $rendered === '' ? null : $rendered;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $rendered = RichText::render(is_string($value) ? $value : null);

        return $rendered === '' ? null : $rendered;
    }
}
