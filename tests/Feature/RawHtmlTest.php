<?php

use Illuminate\Support\Facades\File;

/**
 * React escapes everything it renders. `dangerouslySetInnerHTML` is the
 * one door out of that, and it is how stored XSS gets in.
 *
 * The starter has no field that stores HTML — the generator's eight types
 * all produce plain text, which `{value}` renders safely. So this door is
 * shut today and there is no sanitizer in the project, because nothing
 * needs one.
 *
 * That is exactly the shape of a trap. The first project on this starter
 * with a formatting editor — a blog, a catalogue, a news site — reaches
 * for `dangerouslySetInnerHTML` because it is the only way, finds no
 * sanitizer, notices nothing wrong, and ships an editor account that can
 * run scripts against every visitor. One port did precisely this, in five
 * files, and the only thing that flagged it was a person reading the diff.
 *
 * The door is open now, in exactly one place. `components/safe-html.tsx`
 * renders formatted text that has already been through
 * `App\Support\Html\Sanitizer` on the way into the database — nothing
 * it receives was written by a browser, it was written by HTMLPurifier.
 *
 * Everywhere else is still refused. One place to remember is a place
 * people remember; two is a place somebody forgets.
 */

/** The single component allowed to render stored, sanitized HTML. */
const SAFE_HTML = 'resources/js/components/safe-html.tsx';
function rawHtmlSites(): array
{
    $sites = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        if (str_ends_with($file->getPathname(), basename(SAFE_HTML))) {
            continue;
        }

        foreach (preg_split('/\R/', $file->getContents()) ?: [] as $number => $line) {
            if (str_contains($line, 'dangerouslySetInnerHTML')) {
                $sites[] = str_replace(base_path().'/', '', $file->getPathname()).':'.($number + 1);
            }
        }
    }

    return $sites;
}

// ── Correct data ─────────────────────────────────────────────────────

it('renders no unescaped html', function () {
    // If this fails, the fix is not to add the file to an exemption list.
    // It is to sanitize on the way in — on write, in PHP, once — so that
    // what the database holds is already safe and every reader of it is
    // safe for free.
    expect(rawHtmlSites())->toBe([]);
});

it('keeps the one exception where the test can see it', function () {
    // An exemption that drifts out of date exempts a file nobody decided
    // to exempt — the same reasoning as the foreign-table list in
    // KeyTypesTest.
    expect(base_path(SAFE_HTML))->toBeFile();

    expect(File::get(base_path(SAFE_HTML)))
        ->toContain('dangerouslySetInnerHTML')
        ->and(File::get(base_path(SAFE_HTML)))->toContain('Sanitizer');
});

// ── Wrong data ───────────────────────────────────────────────────────

it('would notice raw html', function () {
    // The check itself, checked: a scan that matches nothing passes.
    $sample = '    <div dangerouslySetInnerHTML={{ __html: article.body }} />';

    expect(str_contains($sample, 'dangerouslySetInnerHTML'))->toBeTrue();
});

// ── Empty data ───────────────────────────────────────────────────────

it('actually reads the front end', function () {
    $files = collect(File::allFiles(resource_path('js')))
        ->filter(fn ($file) => $file->getExtension() === 'tsx');

    expect($files->count())->toBeGreaterThan(20);
});
