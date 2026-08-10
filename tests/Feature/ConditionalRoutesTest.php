<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Fortify\Features;

/*
 * RULE(reachable): a route that can be switched off must not be imported
 * statically — AGENTS.md.
 *
 * `REGISTRATION_ENABLED=false` is a documented switch and the state most
 * projects built on this starter want. It also stops Wayfinder generating
 * `resources/js/routes/register`, and every page importing that module then
 * fails the *build*:
 *
 *     [UNLOADABLE_DEPENDENCY] Could not load .../resources/js/routes/register
 *
 * Not a test, not a request — the build. The whole backend suite stayed
 * green and the frontend suite stayed green, because neither of them builds
 * a bundle. The first person to close registration on a fresh clone found
 * out when `npm run build` died.
 *
 * A runtime guard does not help. `{registrationEnabled && <Link …>}` still
 * needs the import at the top of the file, and an import is resolved
 * whether or not the branch beneath it ever runs. Conditional rendering and
 * a conditional dependency are different things.
 *
 * The urls come from shared props instead. This is the mechanical half of
 * keeping it that way.
 */

/** @return array<int, string> */
function pagesImporting(string $module): array
{
    $offenders = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        // The generated modules import each other; they are what a caller
        // imports, not a caller.
        if (str_contains($file->getPathname(), '/js/routes/')
            || str_contains($file->getPathname(), '/js/actions/')) {
            continue;
        }

        if (str_contains($file->getContents(), "from '{$module}'")) {
            $offenders[] = str_replace(base_path().'/', '', $file->getPathname());
        }
    }

    return $offenders;
}

// ── Correct data ─────────────────────────────────────────────────────

it('shares the registration urls instead of importing them', function () {
    $props = File::get(app_path('Http/Middleware/HandleInertiaRequests.php'));

    expect($props)->toContain('registerUrl')
        ->and($props)->toContain('registerAction');
});

it('still offers registration while it is open', function () {
    // The other half. A build that survives because the feature was
    // removed is not the fix that was asked for.
    expect(route('register'))->toContain('/register')
        ->and(resource_path('js/pages/auth/register.tsx'))->toBeFile();
})->skip(
    fn () => ! Features::enabled(Features::registration()),
    'registration is closed here; the page is still on disk either way',
);

it('keeps the registration page on disk whatever the switch says', function () {
    // Closing registration must not mean deleting the feature. A clone
    // that turns it back on has to find the page waiting.
    expect(resource_path('js/pages/auth/register.tsx'))->toBeFile();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('imports no module that disappears with the route', function () {
    expect(pagesImporting('@/routes/register'))->toBe([]);
});

it('takes no named register helper out of the route index', function () {
    // `import { register } from '@/routes'` fails the same way: the export
    // is gone from the generated index, and MISSING_EXPORT reads exactly
    // like a typo.
    $offenders = [];

    foreach (File::allFiles(resource_path('js/pages')) as $file) {
        if ($file->getExtension() !== 'tsx') {
            continue;
        }

        if (preg_match('/import\s*\{[^}]*\bregister\b[^}]*\}\s*from\s*\x27@\/routes\x27/', $file->getContents())) {
            $offenders[] = str_replace(base_path().'/', '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([]);
});

// ── Empty data ───────────────────────────────────────────────────────

it('would notice a page that imported it', function () {
    // A scanner that always answers "none" is worse than no scanner. This
    // asks for a module every page imports and expects to find some.
    expect(pagesImporting('@/hooks/use-translations'))->not->toBe([]);
});
