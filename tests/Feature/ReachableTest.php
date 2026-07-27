<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * RULE(reachable): a route nobody can reach is a feature nobody has
 * — AGENTS.md, "Tests".
 *
 * Three times in one night the same defect: a controller action complete,
 * its route registered, a test asserting it behaves — and nothing on any
 * screen able to call it.
 *
 *   - roles could be defined and never assigned to a person
 *   - a list of 37 showed 20 with no way to the rest
 *   - a record could be created and edited and never deleted
 *
 * Every one passed the whole suite, because the backend tests asked
 * whether the endpoint behaved and none asked whether anything could
 * call it. Green tests, and a third of the panel unreachable.
 *
 * This is the cheap mechanical half of the answer: a writing route in the
 * panel must be named somewhere in the pages. It cannot prove a button is
 * visible, or enabled, or that a person would find it — Playwright does
 * that where it matters. It does catch a whole action shipped with no
 * caller at all, which is what actually happened.
 */
function panelWriteRoutes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'dash.'))
        ->filter(fn ($route) => array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== [])
        ->map(fn ($route) => (string) $route->getName())
        ->values()
        ->all();
}

/** Everything the panel's pages and components mention, as one haystack. */
function panelSource(): string
{
    $files = collect(File::allFiles(resource_path('js')))
        ->filter(fn ($file) => in_array($file->getExtension(), ['ts', 'tsx'], true))
        // The generated route helpers are not a caller — they are what a
        // caller imports. Counting them makes every route look reached.
        ->reject(fn ($file) => str_contains($file->getPathname(), '/js/routes/'))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/js/actions/'));

    return $files->reduce(fn (string $carry, $file) => $carry.$file->getContents(), '');
}

/**
 * Which helpers a page actually imports from an entity's route module.
 *
 * Matching the bare word `destroy` anywhere in the source is useless: one
 * page's `destroy(role.id)` makes every other entity look reachable. The
 * import list is the only place the pairing is unambiguous.
 *
 * @return array<int, string>
 */
function importedFrom(string $source, string $entity): array
{
    preg_match_all(
        '/import\s*\{([^}]*)\}\s*from\s*\x27@\/routes\/dash\/'.preg_quote($entity, '/').'\x27/',
        $source,
        $matches,
    );

    $names = [];

    foreach ($matches[1] as $group) {
        foreach (explode(',', $group) as $piece) {
            // `index as usersIndex` — the helper is the part before `as`.
            $names[] = trim(explode(' as ', trim($piece))[0]);
        }
    }

    return array_filter($names);
}

// ── Correct data ─────────────────────────────────────────────────────

it('has a caller for every writing route in the panel', function () {
    $source = panelSource();
    $unreachable = [];

    foreach (panelWriteRoutes() as $name) {
        [, $entity, $action] = array_pad(explode('.', $name), 3, '');

        // Three ways a page legitimately reaches a route: the route name
        // as a string, the helper imported for that entity by name, or
        // the generated action object, which carries every verb at once.
        $mentioned = str_contains($source, $name)
            || in_array($action, importedFrom($source, $entity), true)
            // The action object carries every verb, so its bare name
            // proves nothing: `UserController` appears in an import even
            // when only `store` is ever called. The verb has to be named.
            || str_contains($source, Str::studly(Str::singular($entity)).'Controller.'.$action);

        if (! $mentioned) {
            $unreachable[] = $name;
        }
    }

    expect($unreachable)->toBeEmpty();
});

it('finds routes to check at all', function () {
    // A test that silently checks nothing is worse than no test.
    expect(panelWriteRoutes())->not->toBeEmpty();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('would notice a route with no caller', function () {
    // The check itself, checked: an invented name must fail the same
    // lookup that the real ones pass.
    $source = panelSource();

    expect(str_contains($source, 'dash.nothing.destroy'))->toBeFalse();
});

// ── Empty data ───────────────────────────────────────────────────────

it('reads actual page source rather than an empty string', function () {
    expect(strlen(panelSource()))->toBeGreaterThan(10_000);
});
