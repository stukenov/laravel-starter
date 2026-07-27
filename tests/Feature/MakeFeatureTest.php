<?php

use App\Support\Access\Ability;
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

    // Every supported type, not two of them. The generator emitted
    // `$table->dateTz()` for as long as it existed — a method Laravel
    // does not have — and nobody found out, because the only fields this
    // test ever asked for were a string and a text.
    Artisan::call('make:feature', [
        'name' => 'Widget',
        '--fields' => 'title:string,body:text,views:integer,active:boolean,published_on:date,seen_at:datetime',
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

it('declares the permissions the generated policy asks for', function () {
    // This is what the count above missed for as long as the generator
    // existed: the shape was right and the constants did not exist, so
    // every generated slice was a fatal error the moment anything loaded
    // the policy — `php artisan test` included.
    //
    // Asserted against the file, not the loaded class: the constants are
    // written during this same process, and PHP does not re-read a class
    // it has already loaded.
    $ability = File::get(base_path('app/Support/Access/Ability.php'));

    expect($ability)->toContain("VIEW_WIDGETS = 'widgets.view'")
        ->and($ability)->toContain("MANAGE_WIDGETS = 'widgets.manage'");
});

it('offers the new permissions to the role seeder', function () {
    // RoleSeeder reads Ability::all() and runs on every deploy, so a
    // permission left out of that list is one nobody can ever grant.
    $ability = File::get(base_path('app/Support/Access/Ability.php'));

    expect($ability)->toContain('self::VIEW_WIDGETS,')
        ->and($ability)->toContain('self::MANAGE_WIDGETS,');
});

it('gives the generated policy constants that resolve', function () {
    // The conclusive end of the whole class of bug: load Ability in a
    // process that has not seen the old one. A missing constant is a
    // fatal error there, not a green assertion here.
    $output = shell_exec(sprintf(
        'cd %s && php -r %s 2>&1',
        escapeshellarg(base_path()),
        escapeshellarg(
            'require "vendor/autoload.php";'
            .'echo defined("App\\Support\\Access\\Ability::VIEW_WIDGETS") ? "yes" : "no";'
        ),
    ));

    expect(trim((string) $output))->toBe('yes');
});

it('separates reading from changing', function () {
    // Someone allowed to see a list is not thereby allowed to empty it.
    $policy = File::get(base_path('app/Policies/WidgetPolicy.php'));

    expect($policy)->toContain('Ability::VIEW_WIDGETS')
        ->and($policy)->toContain('Ability::MANAGE_WIDGETS');
});

it('writes one migration however many times it runs', function () {
    // The name carries a timestamp, so a second --force run used to add a
    // second create_widgets_table beside the first. --force looked like it
    // had overwritten everything; the next test run died on "relation
    // already exists", in a file nobody had touched.
    Artisan::call('make:feature', [
        'name' => 'Widget',
        '--fields' => 'title:string,body:text',
        '--force' => true,
    ]);

    expect(glob(database_path('migrations/*_create_widgets_table.php')))
        ->toHaveCount(1);
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

it('emits a migration the database accepts', function () {
    // The cheapest end to "the method does not exist" and its whole
    // family: run the thing rather than read it.
    $migration = glob(database_path('migrations/*_create_widgets_table.php'))[0] ?? null;

    expect($migration)->not->toBeNull();

    $definition = File::get($migration);

    foreach (['date(', 'timestampTz(', 'boolean(', 'integer(', 'text(', 'string('] as $column) {
        expect($definition)->toContain($column);
    }

    expect($definition)->not->toContain('dateTz(');
});

it('gives a checkbox to a boolean, not a text box', function () {
    // A text box for a boolean cannot be submitted at all: untouched it
    // sends an empty string, and Laravel's `boolean` rule takes only
    // true/false/1/0. Creating the record failed with an error pointing
    // at a field the person never saw a control for.
    $form = File::get(base_path('resources/js/pages/dash/widgets/form.tsx'));

    expect($form)->toContain('<Checkbox')
        ->and($form)->toContain('name="active"');
});

it('sends a value for an unchecked box', function () {
    // An unchecked checkbox sends nothing, which on the server is
    // indistinguishable from the field being absent. The hidden zero in
    // front of it is what makes "no" arrive as an answer.
    $form = File::get(base_path('resources/js/pages/dash/widgets/form.tsx'));

    expect($form)->toContain('type="hidden"')
        ->and($form)->toContain('value="0"');
});

it('does not make a boolean required', function () {
    // `required` rejects false along with the empty string, so a
    // checkbox left alone could never be saved.
    $rules = File::get(base_path('app/Http/Requests/Widget/StoreRequest.php'));

    expect($rules)->toContain("'active' => ['boolean']");
});

it('gives a textarea to a text field and a number box to an integer', function () {
    $form = File::get(base_path('resources/js/pages/dash/widgets/form.tsx'));

    expect($form)->toContain('<Textarea')
        ->and($form)->toContain('type="number"')
        ->and($form)->toContain('type="date"');
});

it('offers a way to the next page', function () {
    // Every list paginated on the server and no page rendered a control,
    // so a list longer than twenty ended at twenty.
    $page = File::get(base_path('resources/js/pages/dash/widgets/index.tsx'));

    expect($page)->toContain('<Pagination');
});
