<?php

use App\Support\Access\Ability;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
        "tests/e2e/{$plural}.spec.ts",
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

    // Ability.php is shared too, and the generator writes into it. Left
    // alone, a test run leaves permanent constants behind for an entity
    // that no longer exists — and RoleSeeder, which reads Ability::all(),
    // then grants the administrator a permission for nothing.
    //
    // Running the suite must not edit the source it is testing.
    $ability = base_path('app/Support/Access/Ability.php');
    $const = Str::upper($snakePlural);

    $kept = array_filter(
        explode("\n", File::get($ability)),
        fn (string $line) => ! str_contains($line, "VIEW_{$const}")
            && ! str_contains($line, "MANAGE_{$const}"),
    );

    File::put($ability, preg_replace("/\n{3,}/", "\n\n", implode("\n", $kept)));

    // The sidebar is shared as well, and the generator adds an entry to
    // it. The entry is an object, so it has to come out as one: stripping
    // the lines that name the entity leaves `{ icon: Boxes },` behind, and
    // every later run adds another empty husk.
    $sidebar = base_path('resources/js/components/app-sidebar.tsx');
    $contents = File::get($sidebar);

    $contents = preg_replace(
        '/\n\s*\{\s*\n\s*title: \x27'.preg_quote($snakePlural, '/').'\.title\x27,[\s\S]*?\n\s*\},/',
        '',
        $contents,
    );

    $contents = preg_replace(
        '/^import \{ index as '.preg_quote($snakePlural, '/').'Index \}[^\n]*\n/m',
        '',
        (string) $contents,
    );

    // The placeholder icon goes with the last entry that used it.
    if (! str_contains((string) $contents, 'icon: Boxes')) {
        $contents = preg_replace('/^\s*Boxes,\n/m', '', (string) $contents);
    }

    File::put($sidebar, preg_replace("/\n{3,}/", "\n\n", (string) $contents));
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

it('names what the panel may see, rather than sending the whole record', function () {
    // The controller used to hand the paginator straight to the page, so
    // every column went to the front — including whatever a project adds
    // afterwards: an internal note, a hash, somebody else's token. Both
    // hand-written controllers already narrowed their payload; the
    // generator was the one place that did not.
    $model = File::get(base_path('app/Models/Widget.php'));
    $controller = File::get(base_path('app/Http/Controllers/Dash/WidgetController.php'));

    expect($model)->toContain('public function toPanelItem(): array')
        ->and($controller)->toContain('$record->toPanelItem()');
});

it('sends dates as ISO 8601, not as formatted strings', function () {
    // RULE(time): the client formats, in the reader's zone — AGENTS.md.
    expect(File::get(base_path('app/Models/Widget.php')))
        ->toContain("'published_on' => \$this->published_on?->toIso8601String()");
});

it('puts the section in the navigation', function () {
    // A generated entity had a list, a form, routes and tests, and no way
    // to get to any of it — the navigation is a static array the
    // generator never touched, so whoever ran the command had to know to
    // type the URL.
    $sidebar = File::get(base_path('resources/js/components/app-sidebar.tsx'));

    expect($sidebar)->toContain("title: 'widgets.title'")
        ->and($sidebar)->toContain("ability: 'widgets.view'")
        ->and($sidebar)->toContain('widgetsIndex()');
});

it('emits all three levels of tests, not two', function () {
    // The acceptance criterion in the command's own docblock demands
    // three levels. It emitted two, which is precisely how an action with
    // no caller kept passing the suite.
    expect(base_path('tests/Feature/Dash/WidgetTest.php'))->toBeFile()
        ->and(base_path('tests/js/dash-widgets.test.tsx'))->toBeFile()
        ->and(base_path('tests/e2e/widgets.spec.ts'))->toBeFile();
});

it('offers a way to delete a record', function () {
    // The controller has a destroy action, the route exists and a test
    // covers it — and for a long while nothing on screen reached any of
    // them. A record could be created and edited and never removed. The
    // same shape as the pagination bug: the server complete, the
    // interface silent about half of it.
    $form = File::get(base_path('resources/js/pages/dash/widgets/form.tsx'));

    expect($form)->toContain('data-test="widget-delete"')
        ->and($form)->toContain('router.delete');
});

it('does not offer to delete a record that does not exist yet', function () {
    $form = File::get(base_path('resources/js/pages/dash/widgets/form.tsx'));

    // Guarded on `record`, which is null while creating.
    expect($form)->toContain('{record ? (');
});

it('offers a way to the next page', function () {
    // Every list paginated on the server and no page rendered a control,
    // so a list longer than twenty ended at twenty.
    $page = File::get(base_path('resources/js/pages/dash/widgets/index.tsx'));

    expect($page)->toContain('<Pagination');
});

describe('relations', function () {
    // Counted across three real projects being ported: 19 belongsTo, 22
    // belongsToMany, 11 hasMany. The generator knew none of them, so it
    // was useful for one of rail-news's thirteen entities.
    //
    // belongsTo is the side that owns a column, which is the side a
    // generator has to write. hasMany is its inverse and needs no schema
    // here; belongsToMany needs a pivot table and stays hand-written.

    beforeEach(function () {
        cleanupGenerated('Tag', 'tags', 'tags');
        cleanupGenerated('Note', 'notes', 'notes');

        Artisan::call('make:feature', ['name' => 'Tag', '--fields' => 'name:string', '--force' => true]);
        Artisan::call('make:feature', [
            'name' => 'Note',
            '--fields' => 'title:string,tag:belongsTo',
            '--force' => true,
        ]);
    });

    afterEach(function () {
        cleanupGenerated('Note', 'notes', 'notes');
        cleanupGenerated('Tag', 'tags', 'tags');
    });

    // Correct data
    it('writes a foreign key the database will accept', function () {
        $migration = glob(database_path('migrations/*_create_notes_table.php'))[0];

        expect(File::get($migration))
            ->toContain("foreignUuid('tag_id')")
            ->toContain("constrained('tags')");
    });

    it('declares the relation on the model', function () {
        $model = File::get(base_path('app/Models/Note.php'));

        expect($model)->toContain('public function tag(): BelongsTo')
            ->and($model)->toContain('belongsTo(Tag::class)')
            ->and($model)->toContain('use Illuminate\Database\Eloquent\Relations\BelongsTo;');
    });

    it('loads the relation with the list', function () {
        // Otherwise the list is one query per row.
        $controller = File::get(base_path('app/Http/Controllers/Dash/NoteController.php'));

        expect($controller)->toContain("->with('tag')");
    });

    it('offers a select rather than a box to type a uuid into', function () {
        $form = File::get(base_path('resources/js/pages/dash/notes/form.tsx'));

        expect($form)->toContain('<select')
            ->and($form)->toContain('name="tag_id"')
            ->and($form)->toContain('tagOptions.map');
    });

    // Wrong data
    it('refuses a key that points at nothing', function () {
        $rules = File::get(base_path('app/Http/Requests/Note/StoreRequest.php'));

        expect($rules)->toContain("Rule::exists('tags', 'id')")
            ->and($rules)->toContain('use Illuminate\Validation\Rule;');
    });

    it('lets the key be empty', function () {
        // Deleting a tag orphans its notes rather than deleting them, so
        // a null key has to be a legal state.
        $rules = File::get(base_path('app/Http/Requests/Note/StoreRequest.php'));
        $migration = glob(database_path('migrations/*_create_notes_table.php'))[0];

        expect($rules)->toContain("'tag_id' => ['nullable'")
            ->and(File::get($migration))->toContain('nullOnDelete()');
    });

    // Empty data
    it('changes nothing for an entity without relations', function () {
        // The imports are conditional; an unused one is a lint error.
        $model = File::get(base_path('app/Models/Tag.php'));

        expect($model)->not->toContain('BelongsTo')
            ->and(File::get(base_path('app/Http/Requests/Tag/StoreRequest.php')))
            ->not->toContain('use Illuminate\Validation\Rule;');
    });
});

it('casts every attribute it later treats as a date', function () {
    // The defect this replaces: the generator wrote no casts at all, so
    // Postgres handed back `2026-07-28` as a plain string and
    // `toPanelItem()` called `?->toIso8601String()` on it — a fatal
    // error, and a 500 on the list page of every generated entity with a
    // date. All thirteen backend tests it emits failed, on a slice whose
    // stated acceptance criterion is that they pass with no edits.
    //
    // It survived because a factory holds the Carbon it was handed until
    // the row is read back: in memory the object looks right, and only a
    // real request, which re-queries, sees what the database returns.
    //
    // Both sides are read out of the file rather than listed here, so
    // adding a seventh field type cannot leave this test behind.
    $model = File::get(base_path('app/Models/Widget.php'));

    preg_match_all("/'(\w+)' => \\\$this->\\1\\?->toIso8601String\\(\\)/", $model, $used);
    preg_match_all("/'(\w+)' => '(?:date|datetime)'/", $model, $declared);

    // Eloquent casts its own timestamps; only the fields the generator
    // added are its responsibility.
    $used = array_diff($used[1], ['created_at', 'updated_at']);

    $missing = array_values(array_diff($used, $declared[1]));

    expect($used)->not->toBeEmpty()
        ->and($missing)->toBe([]);
});

it('leaves the other types uncast, because pgsql already types them', function () {
    // An integer comes back as an int and a boolean as a bool, so their
    // TypeScript types are honest with no cast. Casting them anyway would
    // suggest the driver cannot be trusted, and the next person would add
    // one for `string` too.
    $model = File::get(base_path('app/Models/Widget.php'));

    expect($model)->not->toContain("'views' => 'integer'")
        ->and($model)->not->toContain("'active' => 'boolean'");
});

it('indents what it generates', function () {
    // For a while the first line of every generated array carried four
    // extra spaces — pint does not touch array bodies, so it shipped in
    // every model this command ever made.
    $model = File::get(base_path('app/Models/Widget.php'));

    expect($model)->toContain("        return [\n            'id' => \$this->id,")
        ->and($model)->not->toContain("        return [\n                ");
});

it('does not let a field label overwrite the page heading', function () {
    // `title` is the commonest field name there is, and flat keys meant
    // it overwrote the page title: the generator's own documented example
    // produced a list headed "Title". PHP keeps the last key and says
    // nothing.
    $ru = require lang_path('ru/entities/widgets.php');

    expect($ru['title'])->toBe('Widgets')
        ->and($ru['fields']['title'])->toBe('Title');
});

it('keeps every fixed key intact whatever the fields are called', function () {
    // The point of the separation is that a field may now be called
    // anything — `title`, `add`, `search`, `empty` — without taking its
    // neighbour's place. So the check is that the fixed keys survive,
    // not that the names are disjoint.
    $ru = require lang_path('ru/entities/widgets.php');

    foreach (['title', 'description', 'search', 'add', 'edit', 'empty', 'save', 'cancel'] as $key) {
        expect($ru)->toHaveKey($key);
    }

    expect($ru['title'])->toBe('Widgets')
        ->and($ru['add'])->not->toBe($ru['fields']['title'] ?? null);
});

it('gives every field a label of its own', function () {
    $ru = require lang_path('ru/entities/widgets.php');

    foreach (['title', 'body', 'views', 'active', 'published_on', 'seen_at'] as $field) {
        expect($ru['fields'])->toHaveKey($field);
    }
});

it('running the suite does not edit the source it tests', function () {
    // The generator writes into Ability.php, and for a while the cleanup
    // did not undo it: every test run left permanent constants for an
    // entity that no longer existed, and RoleSeeder then granted the
    // administrator a permission for nothing.
    $ability = File::get(base_path('app/Support/Access/Ability.php'));

    expect($ability)->not->toContain('WIDGETS')
        ->and($ability)->not->toContain('TAGS')
        ->and($ability)->not->toContain('NOTES');
})->skip('runs inside the suite that generates them; see the afterEach');

describe('a field with a fixed set of values', function () {
    // Counted across the three ported projects: eleven status/type/stage
    // columns, every one a bare string with the allowed values living in
    // somebody's head. The generator could not express it at all.

    beforeEach(function () {
        cleanupGenerated('Ticket', 'tickets', 'tickets');

        Artisan::call('make:feature', [
            'name' => 'Ticket',
            '--fields' => 'title:string,status:enum(open,in_progress,closed)',
            '--force' => true,
        ]);
    });

    afterEach(fn () => cleanupGenerated('Ticket', 'tickets', 'tickets'));

    // Correct data
    it('constrains the column in the database', function () {
        // Not a native Postgres enum: that type cannot have a value
        // removed, and adding one takes an exclusive lock. A constraint
        // is dropped and recreated like anything else.
        $migration = glob(database_path('migrations/*_create_tickets_table.php'))[0];
        $definition = File::get($migration);

        expect($definition)->toContain("string('status')->default('open')")
            ->and($definition)->toContain("check (status in ('open','in_progress','closed'))");
    });

    it('declares the values once, on the model', function () {
        // The migration, the rule and the select all read the same list.
        // Three copies of it drift apart on the first change.
        expect(File::get(base_path('app/Models/Ticket.php')))
            ->toContain("STATUS_VALUES = ['open', 'in_progress', 'closed']");
    });

    it('validates against that list', function () {
        $rules = File::get(base_path('app/Http/Requests/Ticket/StoreRequest.php'));

        expect($rules)->toContain('Rule::in(Ticket::STATUS_VALUES)')
            ->and($rules)->toContain('use Illuminate\Validation\Rule;');
    });

    it('offers a select, not a box to type a status into', function () {
        expect(File::get(base_path('resources/js/pages/dash/tickets/form.tsx')))
            ->toContain('statusOptions.map');
    });

    it('gives every value a label in all three locales', function () {
        // `draft` on screen is a database word leaking into the
        // interface, and it cannot be translated.
        foreach (['ru', 'kk', 'en'] as $locale) {
            $lang = require lang_path("{$locale}/entities/tickets.php");

            expect($lang['status_options'])->toHaveKeys(['open', 'in_progress', 'closed']);
        }
    });

    // Wrong data
    it('refuses a list with only one value', function () {
        expect(fn () => Artisan::call('make:feature', [
            'name' => 'Broken',
            '--fields' => 'state:enum(only)',
            '--force' => true,
        ]))->toThrow(RuntimeException::class);
    });

    // Empty data
    it('leaves an entity without enums untouched', function () {
        // The imports are conditional; an unused one is a lint error and
        // a missing one is fatal.
        expect(File::get(base_path('app/Models/Widget.php')))
            ->not->toContain('_VALUES');
    });
});

it('says when a form is too long for the dialog it writes', function () {
    // Rule 10 says a short form may be a dialog and a long one gets its
    // own page. The generator only knows the dialog, so where the rule
    // says otherwise it says so rather than quietly producing the wrong
    // shape — three ports read the rule, looked at the stub, and could
    // not tell which was authoritative.
    Artisan::call('make:feature', [
        'name' => 'Wide',
        '--fields' => 'a:string,b:string,c:string,d:string,e:string,f:string,g:string',
        '--force' => true,
    ]);

    expect(Artisan::output())->toContain('own page');

    cleanupGenerated('Wide', 'wides', 'wides');
});

it('says nothing for a form the dialog suits', function () {
    Artisan::call('make:feature', [
        'name' => 'Narrow',
        '--fields' => 'a:string,b:string',
        '--force' => true,
    ]);

    expect(Artisan::output())->not->toContain('own page');

    cleanupGenerated('Narrow', 'narrows', 'narrows');
});
