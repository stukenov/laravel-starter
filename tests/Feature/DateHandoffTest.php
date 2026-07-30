<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * RULE(time): the server never sends a preformatted date — AGENTS.md.
 *
 * Only ISO 8601 goes out; the client renders it through
 * `Intl.DateTimeFormat` in the reader's zone and language. Break it and
 * nothing errors: a page simply shows one person's Tuesday to somebody
 * for whom it is Wednesday, in a language they do not read.
 *
 * Three ports called this the easiest rule to break by accident, because
 * `->format('d.m.Y')` is what a Laravel developer reaches for without
 * thinking. It was also the only rule with nothing watching it.
 */
function serverSideDateFormatting(): array
{
    $offenders = [];

    // Where payloads are built. A console command formatting a
    // migration's filename is not sending anybody a date, and scoping
    // this to the whole of app/ flagged exactly that.
    $files = collect([
        ...File::allFiles(app_path('Http')),
        ...File::allFiles(app_path('Models')),
    ])->filter(fn ($file) => $file->getExtension() === 'php');

    foreach ($files as $file) {
        foreach (preg_split('/\R/', $file->getContents()) ?: [] as $number => $line) {
            // A comment explaining the rule is not a violation of it.
            if (preg_match('/^\s*(\/\/|\*|#)/', $line)) {
                continue;
            }

            if (preg_match('/->(format\(|toDateTimeString\(|toDateString\(|diffForHumans\()/', $line)) {
                $offenders[] = str_replace(base_path().'/', '', $file->getPathname()).':'.($number + 1);
            }
        }
    }

    return $offenders;
}

// ── Correct data ─────────────────────────────────────────────────────

it('never formats a date on the server', function () {
    // `toIso8601String()` is the only conversion allowed out of here.
    expect(serverSideDateFormatting())->toBe([]);
});

it('sends timestamps as ISO 8601', function () {
    $payloads = File::get(app_path('Models/Activity.php'))
        .File::get(app_path('Http/Controllers/Dash/UserController.php'));

    expect($payloads)->toContain('toIso8601String()');
});

it('has a client-side formatter to hand off to', function () {
    // A rule that forbids something without providing the alternative is
    // a rule people work around.
    expect(resource_path('js/lib/datetime.ts'))->toBeFile();

    expect(File::get(resource_path('js/lib/datetime.ts')))
        ->toContain('Intl.DateTimeFormat');
});

// ── Wrong data ───────────────────────────────────────────────────────

it('would notice a formatted date', function () {
    // The check itself, checked: the pattern has to match the thing it
    // is looking for, or the test above passes on an empty search.
    $sample = "        'created_at' => \$this->created_at->format('d.m.Y'),";

    expect(preg_match('/->(format\(|toDateTimeString\()/', $sample))->toBe(1);
});

// ── Empty data ───────────────────────────────────────────────────────

it('actually reads files', function () {
    expect(count(File::allFiles(app_path('Http'))))->toBeGreaterThan(10);
});
