<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * RULE(conventions): the generator emits the whole slice — AGENTS.md.
 *
 * The entity is generated into a scratch name, inspected, and removed
 * again. Running the generated suite from here would mean a nested
 * `php artisan test`, which is slow and fragile; the acceptance run is
 * documented in AGENTS.md and exercised by hand. What is checked here is
 * everything that can be checked cheaply — and above all that the emitted
 * tests are real.
 */
function generatedPaths(string $studly, string $plural, string $snakePlural): array
{
    return [
        "app/Models/{$studly}.php",
        "database/factories/{$studly}Factory.php",
        "app/Policies/{$studly}Policy.php",
        "app/Http/Requests/{$studly}/StoreRequest.php",
        "app/Http/Requests/{$studly}/UpdateRequest.php",
        "app/Http/Controllers/Dash/{$studly}Controller.php",
        "resources/js/pages/dash/{$plural}/index.tsx",
        "resources/js/pages/dash/{$plural}/form.tsx",
        "lang/ru/entities/{$snakePlural}.php",
        "lang/kk/entities/{$snakePlural}.php",
        "lang/en/entities/{$snakePlural}.php",
        "tests/Feature/Dash/{$studly}Test.php",
        "tests/js/dash-{$plural}.test.tsx",
    ];
}

function cleanupGenerated(string $studly, string $plural, string $snakePlural): void
{
    foreach (generatedPaths($studly, $plural, $snakePlural) as $path) {
        File::delete(base_path($path));
    }

    File::deleteDirectory(base_path("app/Http/Requests/{$studly}"));
    File::deleteDirectory(base_path("resources/js/pages/dash/{$plural}"));

    foreach (glob(database_path("migrations/*_create_{$snakePlural}_table.php")) ?: [] as $migration) {
        File::delete($migration);
    }

    // The routes file is shared, so the generated block is stripped rather
    // than the file being restored wholesale.
    //
    // Line by line, not by regex: a pattern ending in \n consumes the
    // newline, so consecutive matching lines get skipped and half the
    // block survives. That bug left routes behind on the first run.
    $routes = base_path('routes/dash.php');

    $kept = array_filter(
        explode("\n", File::get($routes)),
        fn (string $line) => ! str_contains($line, "{$studly}Controller")
            && ! str_contains($line, "'{$plural}"),
    );

    File::put($routes, preg_replace("/\n{3,}/", "\n\n", implode("\n", $kept)));
}

beforeEach(function () {
    cleanupGenerated('Widget', 'widgets', 'widgets');

    Artisan::call('make:feature', [
        'name' => 'Widget',
        '--fields' => 'title:string,body:text',
        '--force' => true,
    ]);
});

afterEach(function () {
    cleanupGenerated('Widget', 'widgets', 'widgets');
});

// ── Correct data ─────────────────────────────────────────────────────

it('emits every file of the slice', function () {
    foreach (generatedPaths('Widget', 'widgets', 'widgets') as $path) {
        expect(base_path($path))->toBeFile($path);
    }
});

it('emits a migration', function () {
    expect(glob(database_path('migrations/*_create_widgets_table.php')))->not->toBeEmpty();
});

it('registers the routes', function () {
    $routes = File::get(base_path('routes/dash.php'));

    expect($routes)->toContain('WidgetController')
        ->and($routes)->toContain("'widgets.index'")
        ->and($routes)->toContain("'widgets.destroy'");
});

it('follows the naming conventions', function () {
    // The generator only works because a file's place follows from the
    // entity name. A deviation here breaks every later generation.
    $model = File::get(base_path('app/Models/Widget.php'));

    expect($model)->toContain('class Widget extends Model')
        ->and($model)->toContain('HasUuids');
});

it('produces translations in all three locales with matching keys', function () {
    $ru = array_keys(require lang_path('ru/entities/widgets.php'));
    $kk = array_keys(require lang_path('kk/entities/widgets.php'));
    $en = array_keys(require lang_path('en/entities/widgets.php'));

    expect($kk)->toBe($ru)->and($en)->toBe($ru);
});

// ── Wrong data ───────────────────────────────────────────────────────

it('emits tests that actually assert something', function () {
    // The whole point. A generator that emits skipped or empty tests is
    // worse than one that emits none: a stub creates the appearance of
    // coverage and the next person believes it.
    $feature = File::get(base_path('tests/Feature/Dash/WidgetTest.php'));
    $rendering = File::get(base_path('tests/js/dash-widgets.test.tsx'));

    // Count both kinds: Pest's expect() and Laravel's assert* helpers are
    // equally real assertions, and the generated backend tests lean on the
    // latter. Counting only expect( measured the wrong thing.
    $backendAssertions = substr_count($feature, 'expect(')
        + preg_match_all('/->assert[A-Z]\w*\(/', $feature);

    expect($feature)->not->toContain('markTestSkipped')
        ->and($feature)->not->toContain('->skip(')
        ->and($rendering)->not->toContain('it.skip')
        ->and($backendAssertions)->toBeGreaterThanOrEqual(9)
        ->and(substr_count($rendering, 'expect('))->toBeGreaterThanOrEqual(6);
});

it('emits at least nine scenarios per level', function () {
    // Three levels times three data states is the project minimum.
    $feature = File::get(base_path('tests/Feature/Dash/WidgetTest.php'));
    $rendering = File::get(base_path('tests/js/dash-widgets.test.tsx'));

    expect(substr_count($feature, "it('"))->toBeGreaterThanOrEqual(9)
        ->and(substr_count($rendering, "it('"))->toBeGreaterThanOrEqual(6);
});

it('covers all three data states in the generated tests', function () {
    $feature = File::get(base_path('tests/Feature/Dash/WidgetTest.php'));

    expect($feature)->toContain('Correct data')
        ->and($feature)->toContain('Wrong data')
        ->and($feature)->toContain('Empty data');
});

it('gates the generated policy on permissions', function () {
    // Registration is open, so a policy returning true everywhere hands
    // the panel to anyone who signs up. Every verb must check something.
    $policy = File::get(base_path('app/Policies/WidgetPolicy.php'));

    expect(substr_count($policy, 'Ability::'))->toBe(5)
        ->and($policy)->not->toContain('return true;');
});

it('refuses a lowercase entity name', function () {
    $code = Artisan::call('make:feature', ['name' => 'widget', '--fields' => 'title:string']);

    expect($code)->toBe(1);
});

it('refuses an unsupported field type', function () {
    expect(fn () => Artisan::call('make:feature', [
        'name' => 'Gadget',
        '--fields' => 'title:jsonb',
    ]))->toThrow(RuntimeException::class);
});

// ── Empty data ───────────────────────────────────────────────────────

it('refuses to run without fields', function () {
    $code = Artisan::call('make:feature', ['name' => 'Gadget', '--fields' => '']);

    expect($code)->toBe(1);
});

it('leaves existing files alone without --force', function () {
    File::put(base_path('app/Models/Widget.php'), '<?php // hand written');

    Artisan::call('make:feature', ['name' => 'Widget', '--fields' => 'title:string']);

    expect(File::get(base_path('app/Models/Widget.php')))->toContain('hand written');
});
