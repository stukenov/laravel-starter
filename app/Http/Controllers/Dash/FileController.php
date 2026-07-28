<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Support\Files\StoredFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * RULE(files): private disk, served through a policy — AGENTS.md.
 *
 * One route for every entity, because the alternative is a download
 * action per entity and a policy check somebody eventually skips. What
 * makes it safe is that it resolves nothing it was not told about:
 * `config('files.entities')` maps a URL segment to a class, and a name
 * taken from the URL and turned into a class name is how a download
 * route becomes a way to instantiate whatever the caller likes.
 *
 * The file is as private as the record. `view` on the record is the
 * whole authorisation — no signed URLs, no guessable paths, no second
 * set of rules to keep in step with the first.
 */
class FileController extends Controller
{
    public function __invoke(string $entity, string $id, string $field): StreamedResponse
    {
        $class = config("files.entities.{$entity}");

        abort_if($class === null, 404);

        /** @var Model $record */
        $record = $class::query()->findOrFail($id);

        $this->authorize('view', $record);

        // The field has to be a file column on that model, not any
        // attribute: without this the URL reads any column it likes and
        // asks the disk for whatever is in it.
        abort_unless(
            ($record->getCasts()[$field] ?? null) === StoredFile::class,
            404,
        );

        $path = $record->getRawOriginal($field);

        abort_if($path === null || $path === '', 404);

        $disk = Storage::disk(config('files.disk'));

        abort_unless($disk->exists($path), 404);

        // Inline for an image, attachment for anything else: a browser
        // asked to display an arbitrary file type is a browser deciding
        // for itself what to execute.
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $inline = str_starts_with($mime, 'image/');

        return $disk->response($path, basename($path), [
            'Content-Type' => $mime,
            // A file uploaded by one person and opened by another must
            // not be sniffed into something executable.
            'X-Content-Type-Options' => 'nosniff',
        ], $inline ? 'inline' : 'attachment');
    }
}
