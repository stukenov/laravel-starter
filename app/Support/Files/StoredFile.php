<?php

namespace App\Support\Files;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * RULE(files): the column holds a path, the disk holds the file — AGENTS.md.
 *
 * A marker, not a worker. It says "this column is a file" so that
 * `HasStoredFiles` and `FileController` can find such columns by asking
 * the model, and it passes values through untouched.
 *
 * Storing used to happen here, and it was wrong in a way that only a
 * test showed: Eloquent calls a cast's `set` more than once per save, so
 * each call stored the upload again and deleted the path the previous
 * call had just written. The file that survived was the one nothing
 * pointed at. The work moved to a single `saving` hook, which runs once.
 *
 * @implements CastsAttributes<string|null, mixed>
 */
class StoredFile implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // Untouched, including an UploadedFile: the saving hook needs to
        // see it as it was handed over.
        return $value;
    }

    public static function forget(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk(config('files.disk'))->delete($path);
    }
}
