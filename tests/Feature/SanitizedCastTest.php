<?php

use App\Support\Html\SanitizedHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * RULE(html): formatted text is cleaned on the way in, once — AGENTS.md.
 *
 * The cast, not the sanitizer. Removing the cleaning from
 * `SanitizedHtml::set()` failed nothing at all — `SanitizerTest` calls
 * the sanitizer directly and never goes near a model, so the class that
 * actually stands between a request and the database was unguarded.
 * Found by falsification: a script tag went into the table and came back
 * out intact.
 *
 * A cast rather than a form request is the point. A seeder, a console
 * command and a queued import all write here and none of them pass
 * through validation.
 */
beforeEach(function () {
    Schema::create('sanitized_probes', function ($table) {
        $table->uuid('id')->primary();
        $table->text('body')->nullable();
    });
});

afterEach(fn () => Schema::dropIfExists('sanitized_probes'));

function probe(?string $body): SanitizedProbe
{
    $probe = new SanitizedProbe;
    $probe->id = (string) Str::uuid7();
    $probe->body = $body;
    $probe->save();

    return $probe->fresh();
}

// ── Correct data ─────────────────────────────────────────────────────

it('stores formatting an author wrote', function () {
    expect(probe('<p>Текст с <strong>акцентом</strong></p>')->body)
        ->toBe('<p>Текст с <strong>акцентом</strong></p>');
});

it('reads back exactly what was stored', function () {
    // Not cleaned again on read: it cannot have changed, and a full
    // parse per row per page load is a real cost for nothing.
    $stored = probe('<p>Текст</p>');

    expect($stored->body)->toBe('<p>Текст</p>');
});

// ── Wrong data ───────────────────────────────────────────────────────

it('never lets a script tag reach the table', function () {
    // Checked in the database, not on the model: a mutator that returned
    // clean text while writing dirty text would pass any check that
    // asked the object.
    $stored = probe('<script>alert(1)</script><p>после</p>');

    $raw = DB::table('sanitized_probes')->where('id', $stored->id)->value('body');

    expect($raw)->toBe('<p>после</p>')
        ->and($raw)->not->toContain('script');
});

it('cleans a write that never saw a form request', function () {
    // The whole reason this is a cast. Nothing here validated anything.
    $stored = probe('<p onclick="steal()">клик</p>');

    expect($stored->body)->toBe('<p>клик</p>');
});

// ── Empty data ───────────────────────────────────────────────────────

it('turns an empty editor into null', function () {
    expect(probe('<p></p>')->body)->toBeNull();
});

it('keeps null as null', function () {
    expect(probe(null)->body)->toBeNull();
});

class SanitizedProbe extends Model
{
    protected $table = 'sanitized_probes';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'body'];

    protected function casts(): array
    {
        return ['body' => SanitizedHtml::class];
    }
}
