<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Files\HasStoredFiles;
use App\Support\Files\StoredFile;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * RULE(files): private disk, served through a policy — AGENTS.md.
 *
 * A file attached to a record is exactly as private as the record. The
 * `public` disk cannot express that: it is a symlink into the web root,
 * and anything on it is readable by anybody who guesses the URL — no
 * session, no policy, no log. So the disk is private and one route
 * serves everything, authorised by the record's own `view`.
 *
 * Two things make that route safe rather than merely convenient, and
 * both have a case here: it resolves only the entities named in
 * `config/files.php`, and it serves only columns the model itself casts
 * as files. Without the first, a name from the URL becomes a class name.
 * Without the second, the URL reads any column it likes.
 */
beforeEach(function () {
    Storage::fake('local');

    Schema::create('download_probes', function ($table) {
        $table->uuid('id')->primary();
        $table->string('attachment')->nullable();
        $table->string('secret')->nullable();
    });

    config(['files.entities.download-probes' => DownloadProbe::class]);

    Gate::policy(DownloadProbe::class, DownloadProbePolicy::class);
});

afterEach(fn () => Schema::dropIfExists('download_probes'));

function downloadProbe(): DownloadProbe
{
    $probe = new DownloadProbe;
    $probe->id = (string) Str::uuid7();
    $probe->attachment = UploadedFile::fake()->create('отчёт.pdf', 10);
    $probe->secret = 'not-a-file-column';
    $probe->save();

    return $probe->fresh();
}

function download(User $actor, DownloadProbe $probe, string $field = 'attachment')
{
    return test()->actingAs($actor)
        ->get("/dash/files/download-probes/{$probe->id}/{$field}");
}

// ── Correct data ─────────────────────────────────────────────────────

it('serves the file to somebody who may see the record', function () {
    $probe = downloadProbe();

    download(User::factory()->admin()->create(), $probe)
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('sends an arbitrary type as an attachment', function () {
    // A browser asked to display an unknown type is a browser deciding
    // for itself what to execute.
    $probe = downloadProbe();

    $response = download(User::factory()->admin()->create(), $probe);

    expect($response->headers->get('content-disposition'))->toStartWith('attachment');
});

// ── Wrong data ───────────────────────────────────────────────────────

it('refuses somebody the policy refuses', function () {
    $probe = downloadProbe();

    download(User::factory()->create(), $probe)->assertForbidden();
});

it('refuses an entity nobody registered', function () {
    // A name from the URL turned into a class name is how a download
    // route becomes a way to instantiate whatever the caller likes.
    //
    // The segment names no class at all, on purpose. Asking for `users`
    // gave 404 either way — the id belonged to another table, so the
    // lookup failed and the test passed without the guard. This one
    // answers 500 the moment the guard is gone.
    $this->actingAs(User::factory()->admin()->create())
        ->get('/dash/files/nothing-of-the-sort/019fa000-0000-7000-8000-000000000001/attachment')
        ->assertNotFound();
});

it('refuses a column that is not a file', function () {
    // The column holds a real, readable path — the same one — so the
    // only thing that can refuse this is the cast check. Written with a
    // meaningless value it was the disk saying "no such file", and the
    // test passed with the guard removed.
    $probe = downloadProbe();

    $probe->secret = $probe->attachment;
    $probe->save();

    download(User::factory()->admin()->create(), $probe->fresh(), 'secret')
        ->assertNotFound();
});

it('refuses an anonymous request', function () {
    $probe = downloadProbe();

    $this->get("/dash/files/download-probes/{$probe->id}/attachment")
        ->assertRedirect(route('login'));
});

// ── Empty data ───────────────────────────────────────────────────────

it('answers 404 when the column is empty', function () {
    $probe = downloadProbe();
    $probe->attachment = null;
    $probe->save();

    download(User::factory()->admin()->create(), $probe->fresh())->assertNotFound();
});

it('answers 404 when the file is gone from the disk', function () {
    // A path pointing at nothing looks fine in every list and breaks
    // only when somebody clicks it.
    $probe = downloadProbe();

    Storage::disk('local')->delete($probe->attachment);

    download(User::factory()->admin()->create(), $probe)->assertNotFound();
});

class DownloadProbe extends Model
{
    use HasStoredFiles;

    protected $table = 'download_probes';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'attachment', 'secret'];

    protected function casts(): array
    {
        return ['attachment' => StoredFile::class];
    }
}

class DownloadProbePolicy
{
    public function view(User $user): bool
    {
        return $user->hasRole(RoleSeeder::ADMIN);
    }
}
