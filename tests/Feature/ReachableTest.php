<?php

declare(strict_types=1);

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

/**
 * Which controller and method each writing route actually lands on.
 *
 * The route name alone is not enough. Splitting `dash.entity.action` into
 * three assumes three segments, and a nested route like
 * `dash.projects.touches.store` has four — so it looked unreachable no
 * matter how the page called it. The route object knows without guessing.
 *
 * @return array<string, array{short: string, method: string}>
 */
function panelWriteRouteActions(): array
{
    $map = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $name = (string) $route->getName();

        if (! str_starts_with($name, 'dash.')) {
            continue;
        }

        if (array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) === []) {
            continue;
        }

        $action = $route->getAction('controller');

        if (! is_string($action) || $action === '') {
            continue;
        }

        // `Route::post($uri, SomeController::class)` records the class with
        // no `@method` at all — an invokable controller. Read as "not a
        // controller", such a route was skipped here and then reported as
        // unreachable whatever called it.
        [$class, $method] = str_contains($action, '@')
            ? explode('@', $action, 2)
            : [$action, '__invoke'];

        $map[$name] = ['short' => class_basename($class), 'method' => $method];
    }

    return $map;
}

/**
 * Which methods a page imports from a controller's generated action module.
 *
 * `import { store as writeNote } from '@/actions/.../ProjectNoteController'`
 * is a real caller and the older checks could not see it: the helper is
 * renamed at the import, so the bare word never appears again.
 *
 * @return array<int, string>
 */
function importedFromAction(string $source, string $controllerShort): array
{
    preg_match_all(
        '/import\s*\{([^}]*)\}\s*from\s*\x27@\/actions\/[^\x27]*'.preg_quote($controllerShort, '/').'\x27/',
        $source,
        $matches,
    );

    $names = [];

    foreach ($matches[1] as $group) {
        foreach (explode(',', $group) as $piece) {
            $names[] = trim(explode(' as ', trim($piece))[0]);
        }
    }

    return array_filter($names);
}

/**
 * Whether a page imports an invokable controller's action module at all.
 *
 * A single-action controller has one default export, usually renamed at the
 * import, and one route — so importing the module is the whole of the
 * proof. There is no verb to look for.
 */
function defaultImportsAction(string $source, string $controllerShort): bool
{
    return preg_match(
        '/import\s+\w+\s+from\s*\x27@\/actions\/[^\x27]*'.preg_quote($controllerShort, '/').'\x27/',
        $source,
    ) === 1;
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

    $actions = panelWriteRouteActions();

    foreach (panelWriteRoutes() as $name) {
        [, $entity, $action] = array_pad(explode('.', $name), 3, '');

        $target = $actions[$name] ?? null;

        // Five ways a page legitimately reaches a route: the route name as
        // a string, the helper imported for that entity by name, the
        // generated action object with the verb named, a named import out
        // of that controller's action module — renamed or not — or, for a
        // single-action controller, the module itself.
        $mentioned = str_contains($source, $name)
            || in_array($action, importedFrom($source, $entity), true)
            // The action object carries every verb, so its bare name
            // proves nothing: `UserController` appears in an import even
            // when only `store` is ever called. The verb has to be named.
            || str_contains($source, Str::studly(Str::singular($entity)).'Controller.'.$action)
            || ($target !== null && str_contains($source, $target['short'].'.'.$target['method']))
            || ($target !== null && in_array($target['method'], importedFromAction($source, $target['short']), true))
            || ($target !== null && $target['method'] === '__invoke'
                && defaultImportsAction($source, $target['short']));

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

/*
 * The three shapes the older check could not see. There is no writing
 * invokable route under `dash.` in the starter itself, so these exercise
 * the predicates directly rather than waiting for the first project that
 * adds one — which is how the gap was found.
 */

// ── Correct data ─────────────────────────────────────────────────────

it('sees a renamed named import out of an action module', function () {
    $source = "import { store as writeNote } from '@/actions/App/Http/Controllers/Dash/ProjectNoteController';";

    expect(importedFromAction($source, 'ProjectNoteController'))->toBe(['store']);
});

it('sees a default import of a single-action controller', function () {
    $source = "import startRun from '@/actions/App/Http/Controllers/Dash/StartRunController';";

    expect(defaultImportsAction($source, 'StartRunController'))->toBeTrue();
});

it('reads the method off an invokable route rather than skipping it', function () {
    // `Route::get('activity/stream', ActivityStreamController::class)` is a
    // read, so it is not in the writing map — but the same shape with a
    // POST used to be dropped on the floor here.
    $invokable = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route) => $route->getName() === 'dash.activity.stream');

    $action = $invokable?->getAction('controller');

    expect($action)->toBeString()
        ->and(str_contains((string) $action, '@'))->toBeFalse();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('does not mistake a different controller for this one', function () {
    $source = "import { store } from '@/actions/App/Http/Controllers/Dash/UserController';";

    expect(importedFromAction($source, 'RoleController'))->toBe([])
        ->and(defaultImportsAction($source, 'RoleController'))->toBeFalse();
});

// ── Empty data ───────────────────────────────────────────────────────

it('finds nothing in a page that imports nothing', function () {
    expect(importedFromAction('', 'UserController'))->toBe([])
        ->and(defaultImportsAction('', 'UserController'))->toBeFalse();
});
