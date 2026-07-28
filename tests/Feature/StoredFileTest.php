<?php

use App\Support\Files\HasStoredFiles;
use App\Support\Files\StoredFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * RULE(files): the column holds a path, the disk holds the file — AGENTS.md.
 *
 * Three things have to be true and each fails silently on its own.
 *
 * The file is stored — a column holding a path to nothing looks fine in
 * every list and breaks only when somebody clicks it. The old file is
 * deleted when a new one replaces it — otherwise the disk grows with the
 * number of edits rather than the number of records, and the first sign
 * is a full volume. And deleting the record deletes its files — the cast
 * never sees that, because nothing assigns to the attribute, which is why
 * `HasStoredFiles` exists as a separate half.
 */
beforeEach(function () {
    Storage::fake('local');

    Schema::create('file_probes', function ($table) {
        $table->uuid('id')->primary();
        $table->string('attachment')->nullable();
    });
});

afterEach(fn () => Schema::dropIfExists('file_probes'));

function fileProbe(mixed $attachment): FileProbe
{
    $probe = new FileProbe;
    $probe->id = (string) Str::uuid7();
    $probe->attachment = $attachment;
    $probe->save();

    return $probe;
}

// ── Correct data ─────────────────────────────────────────────────────

it('puts the file on the disk and the path in the column', function () {
    $probe = fileProbe(UploadedFile::fake()->create('отчёт.pdf', 20));

    $path = $probe->fresh()->attachment;

    expect($path)->toStartWith('files/file_probes/')
        ->and(Storage::disk('local')->exists($path))->toBeTrue();
});

it('keeps the extension and discards the rest of the name', function () {
    // The uploaded name is written by whoever uploaded it: it can carry
    // a path, a null byte, or four hundred characters of unicode a
    // filesystem will cut in the middle of.
    $probe = fileProbe(UploadedFile::fake()->create('../../etc/passwd.pdf', 10));

    $path = $probe->fresh()->attachment;

    expect($path)->toEndWith('.pdf')
        ->and($path)->not->toContain('passwd')
        ->and($path)->not->toContain('..');
});

it('deletes the file it replaces', function () {
    $probe = fileProbe(UploadedFile::fake()->create('первый.pdf', 10));
    $first = $probe->fresh()->attachment;

    $probe->attachment = UploadedFile::fake()->create('второй.pdf', 10);
    $probe->save();

    $second = $probe->fresh()->attachment;

    expect($second)->not->toBe($first)
        ->and(Storage::disk('local')->exists($second))->toBeTrue()
        ->and(Storage::disk('local')->exists($first))->toBeFalse();
});

it('deletes the file when the record goes', function () {
    // The half a cast cannot do: nothing assigns to the attribute, so
    // nothing calls it.
    $probe = fileProbe(UploadedFile::fake()->create('прощальный.pdf', 10));
    $path = $probe->fresh()->attachment;

    $probe->delete();

    expect(Storage::disk('local')->exists($path))->toBeFalse();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('leaves a path alone when a path is assigned', function () {
    // A copy, a seeder, a re-save of an untouched model. Nothing to
    // store and — above all — nothing to delete.
    $probe = fileProbe(UploadedFile::fake()->create('файл.pdf', 10));
    $path = $probe->fresh()->attachment;

    $probe->attachment = $path;
    $probe->save();

    expect($probe->fresh()->attachment)->toBe($path)
        ->and(Storage::disk('local')->exists($path))->toBeTrue();
});

it('removes the file when the field is cleared', function () {
    $probe = fileProbe(UploadedFile::fake()->create('ненужный.pdf', 10));
    $path = $probe->fresh()->attachment;

    $probe->attachment = null;
    $probe->save();

    expect($probe->fresh()->attachment)->toBeNull()
        ->and(Storage::disk('local')->exists($path))->toBeFalse();
});

// ── Empty data ───────────────────────────────────────────────────────

it('stores nothing when nothing was uploaded', function () {
    $probe = fileProbe(null);

    expect($probe->fresh()->attachment)->toBeNull()
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('leaves no file behind for a record that never had one', function () {
    $probe = fileProbe(null);

    $probe->delete();

    expect(Storage::disk('local')->allFiles())->toBe([]);
});

class FileProbe extends Model
{
    use HasStoredFiles;

    protected $table = 'file_probes';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'attachment'];

    protected function casts(): array
    {
        return ['attachment' => StoredFile::class];
    }
}
