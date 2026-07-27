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
 * So the door stays visibly shut rather than quietly unlocked. Opening it
 * means a sanitizer, and a sanitizer means a dependency the owner picks —
 * see the open questions in the migration report.
 */
function rawHtmlSites(): array
{
    $sites = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
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

it('ships no field type that stores html', function () {
    // The reason the assertion above can hold at all. Lose this and the
    // pressure to open the door arrives with it.
    $generator = File::get(app_path('Console/Commands/MakeFeatureCommand.php'));

    expect($generator)->not->toContain("'html'")
        ->and($generator)->not->toContain("'richtext'");
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
