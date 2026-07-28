<?php

namespace App\Support\Files;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * RULE(files): the column holds a path, the disk holds the file — AGENTS.md.
 *
 * Both halves of the story, in one place, on hooks that run once each.
 *
 * **Saving** turns an uploaded file into a path and deletes whatever it
 * replaced. Without the deletion the disk grows with the number of edits
 * rather than the number of records, and the first sign is a full volume.
 *
 * **Deleting** removes the files of a record that is going. Nothing
 * assigns to the attribute then, so no cast would ever hear about it —
 * which is why this is a trait and not only a cast.
 *
 * Which columns are files comes from the model's own casts, so a field
 * added later is covered without anybody remembering this exists.
 */
trait HasStoredFiles
{
    public static function bootHasStoredFiles(): void
    {
        static::saving(function (Model $model): void {
            foreach (self::fileAttributes($model) as $attribute) {
                $value = $model->getAttributes()[$attribute] ?? null;

                if ($value instanceof UploadedFile) {
                    $model->setRawAttributes(array_merge($model->getAttributes(), [
                        $attribute => self::store($model, $value),
                    ]));

                    self::forgetOriginal($model, $attribute);

                    continue;
                }

                // Cleared: the column goes null and the file goes with it.
                if ($value === null || $value === '') {
                    self::forgetOriginal($model, $attribute);
                }
            }
        });

        static::deleted(function (Model $model): void {
            foreach (self::fileAttributes($model) as $attribute) {
                StoredFile::forget($model->getRawOriginal($attribute));
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private static function fileAttributes(Model $model): array
    {
        return array_keys(array_filter(
            $model->getCasts(),
            fn (string $cast) => $cast === StoredFile::class,
        ));
    }

    private static function forgetOriginal(Model $model, string $attribute): void
    {
        $original = $model->getRawOriginal($attribute);

        if (is_string($original)) {
            StoredFile::forget($original);
        }
    }

    /**
     * A name that keeps the extension and discards everything else.
     *
     * The uploaded name is written by whoever uploaded it: it can carry
     * a path, a null byte, or four hundred characters of unicode that a
     * filesystem will cut in the middle of. The extension is the only
     * part worth keeping, and it comes from the detected type rather
     * than from the name.
     */
    private static function store(Model $model, UploadedFile $file): ?string
    {
        $extension = $file->extension() ?: $file->getClientOriginalExtension();

        $name = Str::uuid7().($extension === '' ? '' : '.'.Str::lower($extension));

        $stored = $file->storeAs(
            'files/'.$model->getTable(),
            $name,
            ['disk' => config('files.disk')],
        );

        return $stored === false ? null : $stored;
    }
}
