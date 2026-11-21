<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pictures the federation uploads: a sponsor's logo, an article's photograph.
 *
 * Gathered here because the same three rules apply wherever one is attached,
 * and each is easy to get subtly wrong on its own: the file is stored under a
 * name nobody chose, so two uploads called logo.png cannot overwrite each
 * other; the one it replaces is deleted only once the new one is safely
 * saved; and a record being removed takes its picture with it rather than
 * leaving it on the disk for ever.
 */
class Images
{
    private const DISK = 'public';

    /**
     * Where a stored picture can be seen, or null if there is none.
     */
    public static function url(?string $path): ?string
    {
        return $path === null ? null : Storage::disk(self::DISK)->url($path);
    }

    /**
     * Attach a newly uploaded picture, and drop the one it replaces.
     *
     * Does nothing when no file came with the request, so a form that saves
     * the rest of its fields leaves the existing picture alone.
     */
    public static function replace(Request $request, Model $model, string $field, string $column, string $folder): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $file = $request->file($field);

        if (is_array($file)) {
            return;
        }

        $previous = $model->getAttribute($column);

        $model->forceFill([
            $column => $file->storeAs(
                $folder,
                Str::uuid()->toString().'.'.$file->extension(),
                self::DISK,
            ),
        ])->save();

        // Only once the new one is saved, so a failed upload cannot leave the
        // record with no picture at all.
        if (is_string($previous)) {
            Storage::disk(self::DISK)->delete($previous);
        }
    }

    /**
     * Drop the picture a record is about to stop needing.
     */
    public static function forget(Model $model, string $column): void
    {
        $path = $model->getAttribute($column);

        if (is_string($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
