<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * RULE(conventions): the whole slice at once, or nothing — AGENTS.md.
 *
 * Fifteen files per entity is an hour of mechanical work, and that hour is
 * where the speed goes. The command emits all of them, laid out exactly the
 * way the users slice is, because that layout is what the conventions
 * describe.
 *
 * Acceptance criterion, and it is strict: after generation
 * `php artisan test` and `npm run test:js` pass with no edits at all.
 * A generator that emits skipped or empty tests counts as unfinished —
 * a stub creates the appearance of coverage and the next person believes it.
 */
class MakeFeatureCommand extends Command
{
    protected $signature = 'make:feature
        {name : Entity name in singular StudlyCase, e.g. Post}
        {--fields= : Comma separated list, e.g. title:string,body:text}
        {--force : Overwrite existing files}';

    protected $description = 'Generate a full vertical slice: model, policy, controller, pages, translations and tests';

    /** Types the generator has always made nullable. */
    private const ALREADY_OPTIONAL = ['text', 'date', 'datetime', 'belongsTo'];

    /** @var array<int, array{name: string, type: string, optional: bool, values: array<int, string>}> */
    private array $fields = [];

    private string $studly;      // Post

    private string $studlyPlural; // Posts

    private string $snake;       // post

    private string $snakePlural; // posts

    private string $kebabPlural; // posts

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) {
            $this->error('The name must be singular StudlyCase, e.g. Post.');

            return self::FAILURE;
        }

        $this->studly = $name;
        $this->studlyPlural = Str::plural($name);
        $this->snake = Str::snake($name);
        $this->snakePlural = Str::snake(Str::plural($name));
        $this->kebabPlural = Str::kebab(Str::plural($name));

        $this->fields = $this->parseFields((string) $this->option('fields'));

        if ($this->fields === []) {
            $this->error('At least one field is required, e.g. --fields=title:string');

            return self::FAILURE;
        }

        foreach ($this->plan() as $target => $stub) {
            $this->render($stub, $target);
        }

        $this->registerRoutes();
        $this->registerApiRoutes();
        $this->registerAbilities();
        $this->registerNavigation();

        // The generator cannot translate, and pretending otherwise is
        // worse than saying so: `lang/ru` and `lang/kk` receive the same
        // English labels as `lang/en`, which reads like a finished
        // translation and is not one.
        $this->components->warn(sprintf(
            'Field labels in lang/ru and lang/kk are English placeholders — %s and the rest. Translate them before this ships.',
            $this->fields[0]['name'],
        ));

        // RULE(ui): a short form may be a dialog, a long one gets its own
        // page — AGENTS.md. The generator only knows how to make the
        // dialog, so where the rule says otherwise it says so out loud
        // rather than quietly producing the wrong shape.
        if (count($this->fields) > 6) {
            $this->components->warn(sprintf(
                '%d fields is a lot for a dialog. Rule 10 says a long form gets its own page — the generator only writes the dialog, so this one is worth moving by hand.',
                count($this->fields),
            ));
        }

        $this->registerFileEntity();

        $this->tidyGeneratedFiles();

        $this->warnAboutMissingRelations();

        $this->components->info("Feature {$this->studly} generated. Run: php artisan migrate && php artisan test");

        return self::SUCCESS;
    }

    /**
     * Runs the project's own formatters over what was just written.
     *
     * Import order depends on the entity's name — where `NoteController`
     * sorts against `use-translations` is not something a template can
     * know — so the stubs cannot be written to satisfy the linter for
     * every possible name. Predicting it was three rounds of chasing
     * alphabetical order before this became obvious.
     *
     * Every entity this command has ever produced failed `npm run lint`,
     * and nobody saw it because the linter was not in the documented
     * commands until this morning and its output had never been read.
     *
     * Best effort: a machine without node still gets a working slice,
     * and is told what to run.
     */
    private function tidyGeneratedFiles(): void
    {
        $paths = [
            "resources/js/pages/dash/{$this->kebabPlural}",
            "tests/js/dash-{$this->kebabPlural}.test.tsx",
            "tests/e2e/{$this->kebabPlural}.spec.ts",
        ];

        $existing = array_values(array_filter($paths, fn (string $path) => file_exists(base_path($path))));

        if ($existing === []) {
            return;
        }

        $ok = $this->runQuietly(array_merge(['npx', 'prettier', '--write'], $existing))
            && $this->runQuietly(array_merge(['npx', 'eslint', '--fix'], $existing));

        if ($ok) {
            $this->components->task('formatted with prettier and eslint', fn () => true);

            return;
        }

        $this->components->warn('Could not run prettier and eslint. Run `npm run lint` before committing.');
    }

    /**
     * @param  array<int, string>  $command
     */
    private function runQuietly(array $command): bool
    {
        $process = new Process($command, base_path(), timeout: 120);

        $process->run();

        return $process->isSuccessful();
    }

    /**
     * A relation to a table nobody has made yet.
     *
     * The foreign key names a table by convention, and the convention is
     * silent about whether it exists. Generate `Article` with
     * `category:belongsTo` before generating `Category` and the migration
     * is written happily, then fails at `migrate` with a message about a
     * relation that does not exist — which reads like a database problem
     * rather than an order-of-operations one.
     *
     * A warning rather than a refusal: the table may be somebody else's,
     * already in the database and outside this repository's migrations.
     * Rule 3 allows exactly that.
     */
    private function warnAboutMissingRelations(): void
    {
        foreach ($this->fields as $field) {
            if ($field['type'] !== 'belongsTo') {
                continue;
            }

            $table = Str::snake(Str::plural($field['name']));

            if (glob(base_path("database/migrations/*_create_{$table}_table.php")) !== []) {
                continue;
            }

            $this->components->warn(sprintf(
                'Nothing creates the `%s` table. Generate it first — php artisan make:feature %s — or the migration fails on a relation that does not exist.',
                $table,
                Str::studly(Str::singular($field['name'])),
            ));
        }
    }

    /**
     * A trailing `?` marks a field optional: `age:integer?`.
     *
     * Without it there was one shape for two different things. A view
     * counter that starts at zero and an age nobody filled in were both
     * `integer not null default 0`, so "not given" arrived in the
     * database as a real number and nothing downstream could tell the
     * difference. Zero is a fact; the absence of a fact is not zero.
     *
     * `text`, `date`, `datetime` and `belongsTo` are already optional —
     * the marker is redundant there and the command says so rather than
     * accepting it silently.
     *
     * @return array<int, array{name: string, type: string, optional: bool, values: array<int, string>}>
     */
    private function parseFields(string $raw): array
    {
        // `belongsTo` is the one relation the generator can own: it is
        // the side that carries the column. `hasMany` is its inverse and
        // needs no schema here; `belongsToMany` needs a pivot table and a
        // multi-select, and stays hand-written — see AGENTS.md.
        $allowed = ['string', 'text', 'richtext', 'integer', 'boolean', 'date', 'datetime', 'file', 'image', 'belongsTo', 'enum'];
        $fields = [];

        // `status:enum(draft,published)` — the parentheses hold commas,
        // so the list is lifted out before the fields are split on them.
        $enums = [];

        $raw = preg_replace_callback(
            '/enum\(([^)]*)\)/',
            function (array $match) use (&$enums): string {
                $enums[] = array_values(array_filter(array_map('trim', explode(',', $match[1]))));

                return 'enum#'.(count($enums) - 1);
            },
            $raw,
        ) ?? $raw;

        foreach (array_filter(array_map('trim', explode(',', $raw))) as $pair) {
            [$field, $type] = array_pad(explode(':', $pair, 2), 2, 'string');

            $values = [];

            if (str_starts_with($type, 'enum#')) {
                $values = $enums[(int) substr($type, 5)] ?? [];
                // The placeholder swallowed the marker: `enum(a,b)?`
                // became `enum#0?`, and overwriting the type here dropped
                // the `?` before anything looked for it.
                $type = 'enum'.(str_ends_with($type, '?') ? '?' : '');

                if (count($values) < 2) {
                    throw new RuntimeException(
                        "A enum field needs at least two values: {$field}:enum(draft,published)"
                    );
                }
            }

            $field = Str::snake(trim($field));
            $type = trim($type);

            $optional = str_ends_with($type, '?');
            $type = rtrim($type, '?');

            if (! preg_match('/^[a-z][a-z0-9_]*$/', $field)) {
                throw new RuntimeException("Bad field name: {$field}");
            }

            if (! in_array($type, $allowed, true)) {
                throw new RuntimeException("Unsupported type {$type}, allowed: ".implode(', ', $allowed));
            }

            if ($optional && in_array($type, self::ALREADY_OPTIONAL, true)) {
                $this->components->warn(
                    "`{$field}:{$type}` is optional already — the `?` changes nothing here."
                );
            }

            $fields[] = [
                'name' => $field,
                'type' => $type,
                'optional' => $optional || in_array($type, self::ALREADY_OPTIONAL, true),
                'values' => $values,
            ];
        }

        return $fields;
    }

    /**
     * @return array<string, string> target path => stub name
     */
    private function plan(): array
    {
        return [
            $this->migrationPath() => 'migration',
            "app/Models/{$this->studly}.php" => 'model',
            "database/factories/{$this->studly}Factory.php" => 'factory',
            "app/Policies/{$this->studly}Policy.php" => 'policy',
            "app/Http/Requests/{$this->studly}/StoreRequest.php" => 'store-request',
            "app/Http/Requests/{$this->studly}/UpdateRequest.php" => 'update-request',
            "app/Http/Controllers/Dash/{$this->studly}Controller.php" => 'controller',
            "resources/js/pages/dash/{$this->kebabPlural}/index.tsx" => 'page-index',
            "resources/js/pages/dash/{$this->kebabPlural}/form.tsx" => 'page-form',
            "lang/ru/entities/{$this->snakePlural}.php" => 'lang-ru',
            "lang/kk/entities/{$this->snakePlural}.php" => 'lang-kk',
            "lang/en/entities/{$this->snakePlural}.php" => 'lang-en',
            "tests/Feature/Dash/{$this->studly}Test.php" => 'test-feature',
            "tests/js/dash-{$this->kebabPlural}.test.tsx" => 'test-js',
            // The third level. Its own acceptance criterion demands three
            // and it emitted two, which is how a working action with no
            // caller kept passing the suite.
            "tests/e2e/{$this->kebabPlural}.spec.ts" => 'test-e2e',
        ];
    }

    /**
     * Reuses the existing migration for this table if there is one.
     *
     * The name carries a timestamp, so a second `--force` run used to
     * write a second `create_widgets_table` beside the first. `--force`
     * looked like it had overwritten everything; the next `php artisan
     * test` died on "relation already exists", from a file the person had
     * not touched and would not think to look for.
     */
    private function migrationPath(): string
    {
        $existing = glob(base_path("database/migrations/*_create_{$this->snakePlural}_table.php")) ?: [];

        if ($existing !== []) {
            return 'database/migrations/'.basename($existing[0]);
        }

        // Strictly after every migration already there, not simply
        // "now". Two entities generated in the same second get the same
        // timestamp, and Laravel then orders them by filename — so
        // `articles` ran before `categories` and its foreign key failed
        // on a table that did not exist yet. Alphabetical order is not a
        // dependency order, and nothing says which one you got.
        $latest = collect(glob(base_path('database/migrations/*.php')) ?: [])
            ->map(fn (string $path) => substr(basename($path), 0, 17))
            ->filter(fn (string $stamp) => preg_match('/^\d{4}(_\d{2}){2}_\d{6}$/', $stamp) === 1)
            ->max();

        $stamp = now()->format('Y_m_d_His');

        if ($latest !== null && $stamp <= $latest) {
            $stamp = Carbon::createFromFormat('Y_m_d_His', $latest)
                ->addSecond()
                ->format('Y_m_d_His');
        }

        return "database/migrations/{$stamp}_create_{$this->snakePlural}_table.php";
    }

    private function render(string $stub, string $target): void
    {
        $path = base_path($target);

        if (File::exists($path) && ! $this->option('force')) {
            $this->components->warn("skipped (exists): {$target}");

            return;
        }

        $source = base_path("stubs/feature/{$stub}.stub");

        if (! File::exists($source)) {
            throw new RuntimeException("Missing stub: {$source}");
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->replace(File::get($source)));

        $this->components->task("created {$target}", fn () => true);
    }

    private function replace(string $contents): string
    {
        $first = $this->fields[0]['name'];

        $map = [
            '{{ studly }}' => $this->studly,
            '{{ studlyPlural }}' => $this->studlyPlural,
            '{{ snake }}' => $this->snake,
            '{{ snakePlural }}' => $this->snakePlural,
            '{{ constPlural }}' => Str::upper($this->snakePlural),
            '{{ kebabPlural }}' => $this->kebabPlural,
            '{{ firstField }}' => $first,
            '{{ searchColumns }}' => $this->searchColumns(),
            '{{ formImports }}' => $this->formImports(),
            '{{ indexImports }}' => $this->indexImports(),
            '{{ timeContext }}' => $this->timeContext(),
            '{{ inertiaImport }}' => $this->timeContext() === ''
                ? "import { Head, router } from '@inertiajs/react';"
                : "import { Head, router, usePage } from '@inertiajs/react';",
            '{{ searchSecondScenario }}' => $this->searchSecondScenario(),
            '{{ richTextScenario }}' => $this->richTextScenario(),
            '{{ fileTrait }}' => $this->hasFiles() ? ', HasStoredFiles' : '',
            '{{ fileTraitImport }}' => $this->hasFiles()
                ? "use App\\Support\\Files\\HasStoredFiles;\n"
                : '',
            '{{ migrationColumns }}' => $this->migrationColumns(),
            '{{ fillable }}' => $this->fillable(),
            '{{ rules }}' => $this->rules(),
            '{{ factoryFields }}' => $this->factoryFields(),
            '{{ tsFields }}' => $this->tsFields(),
            '{{ formFields }}' => $this->formFields(),
            '{{ tableHeaders }}' => $this->tableHeaders(),
            '{{ tableCells }}' => $this->tableCells(),
            '{{ langFields }}' => $this->langFields(),
            '{{ payloadFields }}' => $this->payloadFields(),
            '{{ relations }}' => $this->relations(),
            '{{ enumConstants }}' => $this->enumConstants(),
            '{{ casts }}' => $this->casts(),
            '{{ panelItem }}' => $this->panelItem(),
            '{{ enumOptions }}' => $this->enumOptions(),
            '{{ enumLangSections }}' => $this->enumLangSections(),
            '{{ enumChecks }}' => $this->enumChecks(),
            '{{ enumOptionProps }}' => implode('', array_map(
                fn (array $f) => "\n    {$f['name']}Options: string[];",
                $this->enumFields(),
            )),
            '{{ enumOptionPass }}' => implode('', array_map(
                fn (array $f) => "\n                {$f['name']}Options={{$f['name']}Options}",
                $this->enumFields(),
            )),
            '{{ enumOptionDestructure }}' => implode('', array_map(
                fn (array $f) => "\n    {$f['name']}Options,",
                $this->enumFields(),
            )),
            '{{ relationImports }}' => $this->relationImports(),
            '{{ eagerLoad }}' => $this->eagerLoad(),
            '{{ relationOptions }}' => $this->relationOptions(),
            '{{ relationOptionProps }}' => $this->relationOptionProps(),
            '{{ relationOptionPass }}' => $this->relationOptionPass(),
            '{{ relationOptionDestructure }}' => implode('', array_map(
                fn (array $f) => "\n    {$f['name']}Options,",
                $this->relationFields(),
            )),
            '{{ relationModelImports }}' => $this->relationModelImports(),
            // `Rule::exists` is only emitted for a relation, so the
            // import follows the same condition — an unused import is a
            // lint error, a missing one is a fatal.
            // Both a relation and an enum emit `Rule::`, so the import
            // follows either. Tied to relations alone, an enum-only
            // entity was a fatal error — found by generating one.
            // Both a relation and an enum emit `Rule::`, so the import
            // follows either. Tied to relations alone, an enum-only
            // entity was a fatal error — found by generating one. The
            // model itself is already imported by the stub.
            '{{ requestImports }}' => $this->hasRelations() || $this->enumFields() !== []
                ? "use Illuminate\\Validation\\Rule;\n"
                : '',
        ];

        return str_replace(array_keys($map), array_values($map), $contents);
    }

    private function migrationColumns(): string
    {
        $types = [
            'string' => "\$table->string('%s');",
            'text' => "\$table->text('%s')->nullable();",
            // Formatted text is text. The cleaning happens in the cast,
            // not in the column type.
            'richtext' => "\$table->text('%s')->nullable();",
            // The column holds a path; the disk holds the file.
            'file' => "\$table->string('%s')->nullable();",
            'image' => "\$table->string('%s')->nullable();",
            // `default(0)` only where zero is the honest starting value.
            // An optional number has no default: a counter starts at
            // nothing, an unknown age is not nought.
            'integer' => "\$table->integer('%s')->default(0);",
            'boolean' => "\$table->boolean('%s')->default(false);",
            // `date`, not `dateTz`: that method does not exist, and a
            // calendar date has no timezone to carry. A moment in time is
            // a different field type — see `datetime` below, which is
            // timestamptz like everything else.
            'date' => "\$table->date('%s')->nullable();",
            'datetime' => "\$table->timestampTz('%s')->nullable();",
        ];

        return $this->lines(
            fn (array $f) => $f['type'] === 'enum'
                // A string with a check constraint, not a native enum
                // type: Postgres enums cannot have a value removed and
                // adding one takes an exclusive lock. The constraint
                // gives the same guarantee and can be replaced in a
                // migration like anything else.
                ? sprintf(
                    $f['optional']
                        ? "\$table->string('%s')->nullable();"
                        : "\$table->string('%s')->default('%s');",
                    $f['name'],
                    $f['values'][0],
                )
                : ($f['type'] === 'belongsTo'
                // Nullable and nullOnDelete: deleting a category should
                // orphan its articles, not delete them. A project that
                // wants the opposite says so by hand — that is a
                // business decision, not a default.
                ? sprintf(
                    "\$table->foreignUuid('%s')->nullable()->constrained('%s')->nullOnDelete();",
                    $this->foreignKey($f['name']),
                    Str::snake(Str::plural($f['name'])),
                )
                : sprintf(
                    $f['optional'] && ! in_array($f['type'], self::ALREADY_OPTIONAL, true)
                        ? str_replace(['->default(0)', ';'], ['', '->nullable();'], $types[$f['type']])
                        : $types[$f['type']],
                    $f['name'],
                )),
            indent: 12,
        );
    }

    /**
     * The allowed values of each enum field, as constants on the model.
     *
     * One place that the migration's check constraint, the validation
     * rule and the form's select all agree with. A list repeated in three
     * files drifts apart on the first change.
     */
    private function enumConstants(): string
    {
        $enums = array_filter($this->fields, fn (array $f) => $f['type'] === 'enum');

        if ($enums === []) {
            return '';
        }

        return implode('', array_map(function (array $f): string {
            $const = Str::upper($f['name']);
            $values = "'".implode("', '", $f['values'])."'";

            return <<<PHP

    /** @var array<int, string> */
    public const {$const}_VALUES = [{$values}];

PHP;
        }, $enums));
    }

    /** `use BelongsTo;` when there is a relation, nothing otherwise. */
    private function relationImports(): string
    {
        return $this->hasRelations()
            ? "use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\n"
            : '';
    }

    /** `->with('category:id,name')` for the list, or nothing. */
    private function eagerLoad(): string
    {
        $names = array_map(
            fn (array $f) => "'{$f['name']}'",
            array_filter($this->fields, fn (array $f) => $f['type'] === 'belongsTo'),
        );

        // Without this the list is one query per row — the oldest
        // performance mistake there is.
        return $names === [] ? '' : "\n                ->with(".implode(', ', $names).')';
    }

    private function hasRelations(): bool
    {
        foreach ($this->fields as $field) {
            if ($field['type'] === 'belongsTo') {
                return true;
            }
        }

        return false;
    }

    /**
     * The option lists the form's selects read.
     *
     * The label is the related model's first fillable attribute. For an
     * entity this generator made, that is the first field its author
     * named — `make:feature Category --fields=name:string` gives `name`.
     * A model written by hand may want something else; then this line is
     * the one to change, and it is one line.
     */
    private function relationOptions(): string
    {
        return implode('', array_map(function (array $f): string {
            $model = $this->relatedModel($f['name']);

            return <<<PHP

            '{$f['name']}Options' => {$model}::query()
                ->get()
                ->map(fn ({$model} \$option) => [
                    'id' => \$option->id,
                    'name' => (string) \$option->getAttribute(\$option->getFillable()[0] ?? 'id'),
                ])
                ->sortBy('name')
                ->values(),
PHP;
        }, $this->relationFields()));
    }

    /** `categoryOptions: Option[];` for the page and form props. */
    private function relationOptionProps(): string
    {
        return implode('', array_map(
            fn (array $f) => "\n    {$f['name']}Options: { id: string; name: string }[];",
            $this->relationFields(),
        ));
    }

    /** `categoryOptions={categoryOptions}` where the page renders the form. */
    private function relationOptionPass(): string
    {
        return implode('', array_map(
            fn (array $f) => "\n                {$f['name']}Options={{$f['name']}Options}",
            $this->relationFields(),
        ));
    }

    private function relationModelImports(): string
    {
        return implode('', array_map(
            fn (array $f) => 'use App\\Models\\'.$this->relatedModel($f['name']).";\n",
            $this->relationFields(),
        ));
    }

    /** @return array<int, array{name: string, type: string, optional: bool, values: array<int, string>}> */
    private function relationFields(): array
    {
        return array_values(array_filter(
            $this->fields,
            fn (array $f) => $f['type'] === 'belongsTo',
        ));
    }

    /** A select over the values the model declares. */
    private function enumField(string $name): string
    {
        return <<<TSX
        <div className="grid gap-1.5">
                                <Label htmlFor="{$name}">
                                    {t('{$this->snakePlural}.fields.{$name}')}
                                </Label>
                                <select
                                    id="{$name}"
                                    name="{$name}"
                                    defaultValue={record?.{$name} ?? {$name}Options[0]}
                                    className="border-input h-11 rounded-md border bg-transparent px-3 text-base sm:h-9 md:text-sm"
                                    data-test="{$this->snake}-{$name}"
                                >
                                    {{$name}Options.map((option) => (
                                        <option key={option} value={option}>
                                            {t(`{$this->snakePlural}.{$name}_options.\${option}`)}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.{$name}} />
                            </div>
        TSX;
    }

    /** A select over the options the controller supplies. */
    private function relationField(string $name): string
    {
        $key = $this->foreignKey($name);

        return <<<TSX
        <div className="grid gap-1.5">
                                <Label htmlFor="{$key}">
                                    {t('{$this->snakePlural}.fields.{$name}')}
                                </Label>
                                <select
                                    id="{$key}"
                                    name="{$key}"
                                    defaultValue={record?.{$key} ?? ''}
                                    className="border-input h-11 rounded-md border bg-transparent px-3 text-base sm:h-9 md:text-sm"
                                    data-test="{$this->snake}-{$name}"
                                >
                                    <option value="">—</option>
                                    {{$name}Options.map((option) => (
                                        <option key={option.id} value={option.id}>
                                            {option.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.{$key}} />
                            </div>
        TSX;
    }

    /** `category` → `category_id`. */
    private function foreignKey(string $name): string
    {
        return Str::snake($name).'_id';
    }

    /** The related model's StudlyCase name: `category` → `Category`. */
    private function relatedModel(string $name): string
    {
        return Str::studly(Str::singular($name));
    }

    /** @return array<int, array{name: string, type: string, optional: bool, values: array<int, string>}> */
    private function enumFields(): array
    {
        return array_values(array_filter($this->fields, fn (array $f) => $f['type'] === 'enum'));
    }

    /**
     * The check constraints for the enum columns.
     *
     * A string plus a constraint rather than a native Postgres enum:
     * that type cannot have a value removed at all, and adding one takes
     * an exclusive lock on every table using it. A constraint is dropped
     * and recreated in a migration like anything else.
     *
     * Written as a raw statement because `Blueprint` has no `check()` —
     * checked rather than assumed, after the first version of this
     * generated a migration that died on `method does not exist`.
     */
    private function enumChecks(): string
    {
        return implode('', array_map(function (array $f): string {
            $values = implode("','", $f['values']);

            return "\n        DB::statement(\"alter table {$this->snakePlural} add constraint {$this->snakePlural}_{$f['name']}_check check ({$f['name']} in ('{$values}'))\");";
        }, $this->enumFields()));
    }

    /**
     * A translated label per value, in its own section.
     *
     * Without it the screen shows `draft` — a database word leaking into
     * the interface, untranslatable and identical in all three locales.
     */
    private function enumLangSections(): string
    {
        return implode('', array_map(function (array $f): string {
            $lines = implode("\n", array_map(
                fn (string $value) => "        '{$value}' => '".Str::headline($value)."',",
                $f['values'],
            ));

            return "\n    '{$f['name']}_options' => [\n{$lines}\n    ],\n";
        }, $this->enumFields()));
    }

    /** The option lists the form's enum selects read, for the controller. */
    private function enumOptions(): string
    {
        $enums = array_filter($this->fields, fn (array $f) => $f['type'] === 'enum');

        return implode('', array_map(function (array $f): string {
            $const = Str::upper($f['name']);

            return "\n            '{$f['name']}Options' => {$this->studly}::{$const}_VALUES,";
        }, $enums));
    }

    /**
     * The shape the panel receives, declared on the model.
     *
     * The generated controller used to hand the paginator straight to the
     * page, so every column went to the front — including whatever a
     * project adds later: an internal note, a hash, somebody else's
     * token. Both hand-written controllers already narrowed their
     * payload; the generator was the one place that did not.
     *
     * One method, so the list, the form and any future consumer agree
     * about what an entity looks like from outside.
     */
    /**
     * Without this a date column is a string, and every generated entity
     * with one answered 500 on its own list page.
     *
     * Postgres hands PHP a `date` back as `2026-07-28` and a
     * `timestamptz` as `2026-07-27 23:22:45+00` — plain strings, neither
     * of them ISO 8601, neither of them a Carbon. `toPanelItem()` calls
     * `?->toIso8601String()` on them, which is a fatal error on a string.
     *
     * It went unnoticed because a factory holds the Carbon it was given
     * until the row is read back: the object in memory looks right and
     * only a real request, which re-queries, sees what the database
     * actually returns.
     *
     * The other types need nothing — pgsql already returns `integer` as
     * an int and `boolean` as a bool, so their TypeScript types are
     * honest as they stand.
     */
    /**
     * Every column a person could plausibly type into the box.
     *
     * It used to be one — the first field — so an entity with a name and
     * an email found nothing when searched by email, and said "nothing
     * found", which was not true.
     *
     * Text columns only, and the reason is not that the others fail.
     * Laravel's pgsql grammar casts the column to `::text`, so
     * `ilike` against an integer runs happily — and searching for `3`
     * then returns everyone aged 3, 13, 30 and 43. That is noise wearing
     * the shape of a result.
     *
     * A date is worse: what is stored is `2026-07-28`, and what the
     * reader saw was `28 июля`. An enum holds a database word — `draft`,
     * not its label — so searching it matches a string that appears
     * nowhere on screen.
     */
    /**
     * Only the imports the generated file actually uses.
     *
     * The stubs imported `Checkbox`, `Textarea`, `formatDate` and the
     * rest unconditionally, so an entity of two string fields arrived
     * with four unused imports — and `npm run lint` refuses those. Every
     * entity this command has ever made failed the project's own linter,
     * which nobody noticed because the linter was not in the documented
     * commands until this morning.
     */
    private function formImports(): string
    {
        $types = array_column($this->fields, 'type');

        $imports = [];

        if (in_array('richtext', $types, true)) {
            $imports[] = "import RichText from '@/components/rich-text';";
        }

        if (in_array('boolean', $types, true)) {
            $imports[] = "import { Checkbox } from '@/components/ui/checkbox';";
        }

        if (in_array('text', $types, true)) {
            $imports[] = "import { Textarea } from '@/components/ui/textarea';";
        }

        return implode("\n", $imports);
    }

    private function indexImports(): string
    {
        $types = array_column($this->fields, 'type');

        $datetime = array_values(array_filter(
            ['date' => 'formatDate', 'datetime' => 'formatDateTime'],
            fn (string $type) => in_array($type, $types, true),
            ARRAY_FILTER_USE_KEY,
        ));

        $imports = [];

        if ($datetime !== []) {
            $imports[] = 'import { '.implode(', ', $datetime)." } from '@/lib/datetime';";
        }

        if (in_array('richtext', $types, true)) {
            $imports[] = "import { excerpt } from '@/lib/html';";
        }

        return implode("\n", $imports).($imports === [] ? '' : "\n");
    }

    /**
     * The reader's zone and language, pulled in only where a date is
     * rendered — they are the arguments `formatDate` takes and nothing
     * else on the page uses them.
     */
    private function timeContext(): string
    {
        $types = array_column($this->fields, 'type');

        if (! array_intersect(['date', 'datetime'], $types)) {
            return '';
        }

        return <<<'TSX'
                // RULE(time): the server sends ISO 8601, the client formats it
                // in the reader's zone — AGENTS.md.
                const { auth, locale } = usePage().props;
                const timezone = (auth.user.timezone as string) ?? 'UTC';
        TSX;
    }

    private function searchColumns(): string
    {
        $columns = array_values(array_filter(
            $this->fields,
            fn (array $field) => in_array($field['type'], ['string', 'text'], true),
        ));

        $lines = [];

        foreach ($columns as $index => $field) {
            $method = $index === 0 ? 'where' : 'orWhere';
            $lines[] = "            \$query->{$method}('{$field['name']}', 'ilike', \$like);";
        }

        return implode("\n", $lines);
    }

    /**
     * The generated suite searched by the first field and stopped there,
     * so the day search stopped covering the second column, every test
     * still passed.
     *
     * Emitted only when there is a second text column — for a one-field
     * entity there is nothing to say, and an empty scenario would be a
     * test that asserts nothing.
     */
    /**
     * A browser scenario for the editor, when the entity has one.
     *
     * The other two levels cannot answer this. Pest sees the value the
     * request carried; Vitest sees a component in isolation. Only a real
     * browser can say whether typing into Tiptap puts anything into the
     * hidden input the form actually submits — and that wiring is the one
     * part of a rich-text field that silently does nothing.
     *
     * `\${Date.now()}` is escaped: inside a heredoc PHP reads `${Date`
     * as its own interpolation and fails on a constant named Date, which
     * it reports from a line number in this file and not from the
     * template.
     */
    private function hasFiles(): bool
    {
        return (bool) array_intersect(['file', 'image'], array_column($this->fields, 'type'));
    }

    /**
     * Lets the download route resolve this entity, and only this way.
     *
     * `FileController` maps a URL segment to a class through
     * `config/files.php` and refuses anything not listed. Turning a name
     * from the URL into a class name directly is how a download route
     * becomes a way to instantiate whatever the caller likes.
     */
    private function registerFileEntity(): void
    {
        if (! $this->hasFiles()) {
            return;
        }

        $path = base_path('config/files.php');
        $contents = File::get($path);

        if (str_contains($contents, "'{$this->kebabPlural}' =>")) {
            return;
        }

        $line = "        '{$this->kebabPlural}' => App\\Models\\{$this->studly}::class,";

        $patched = preg_replace(
            "/(    'entities' => \[\n)/",
            '$1'.$line."\n",
            $contents,
            1,
        );

        if ($patched === null || $patched === $contents) {
            $this->components->error(
                "Add '{$this->kebabPlural}' => App\\Models\\{$this->studly}::class to config/files.php by hand, or its files cannot be downloaded."
            );

            return;
        }

        File::put($path, $patched);
        $this->components->task('config/files.php updated', fn () => true);
    }

    private function richTextScenario(): string
    {
        $field = collect($this->fields)->firstWhere('type', 'richtext');

        if ($field === null) {
            return '';
        }

        $name = $field['name'];

        // The first field by name, not `{{ firstField }}`: this string is
        // built after the placeholder pass, so a token left here would
        // reach the generated file verbatim — which it did.
        $first = $this->fields[0]['name'];

        return <<<TSX


        test('types formatted text and stores it', async ({ page }) => {
            const words = `Абзац \${Date.now()}`;

            await page.getByTestId('{$this->snakePlural}-add').click();
            await page.getByTestId('{$this->snake}-{$first}').fill(words);

            // Into the editor surface, not the hidden input: the point is
            // that Tiptap writes through to what the form submits.
            await page.getByTestId('{$this->snake}-{$name}-surface').click();
            await page.keyboard.type('Первый абзац');

            await page.getByTestId('{$this->snake}-save').click();

            // Scoped to the row, not the page. Looking anywhere found the
            // text inside the still-mounted editor, so the scenario passed
            // with the hidden input wired to nothing — which is the one
            // thing it exists to catch.
            const row = page.locator('[data-test="{$this->snakePlural}-row"]', {
                hasText: words,
            });

            await expect(row).toBeVisible();
            await expect(row).toContainText('Первый абзац');

            // Words, not markup — see lib/html.ts.
            await expect(row).not.toContainText('<p>');
        });
        TSX;
    }

    private function searchSecondScenario(): string
    {
        $columns = array_values(array_filter(
            $this->fields,
            fn (array $field) => in_array($field['type'], ['string', 'text'], true),
        ));

        if (count($columns) < 2) {
            return '';
        }

        $first = $columns[0]['name'];
        $second = $columns[1]['name'];

        return <<<PHP


it('finds a record by a column that is not the first', function () {
    // Searching an entity by its second text column — an email, a code, a
    // note — used to return nothing and report "nothing found", which was
    // not true.
    {$this->studly}::factory()->create([
        '{$first}' => 'Ничем не примечательное',
        '{$second}' => 'Иголка в стоге',
    ]);

    \$this->actingAs({$this->snake}Admin())
        ->get(route('dash.{$this->snakePlural}.index', ['search' => 'Иголка']))
        ->assertInertia(fn (\$page) => \$page->has('records.data', 1));
});
PHP;
    }

    private function casts(): string
    {
        $casts = [];

        foreach ($this->fields as $field) {
            $cast = match ($field['type']) {
                'date' => 'date',
                'datetime' => 'datetime',
                // The cleaning lives on the column, not on a form
                // request: a seeder, a console command and a queued
                // import all write here and none of them pass through
                // one. See App\Support\Html\SanitizedHtml.
                'richtext' => 'App\\Support\\Html\\SanitizedHtml',
                // Stores on assignment, deletes what it replaces. The
                // other half — deleting on record removal — is the
                // HasStoredFiles trait on the model.
                'file', 'image' => 'App\\Support\\Files\\StoredFile',
                default => null,
            };

            if ($cast !== null) {
                $casts[] = "            '{$field['name']}' => '{$cast}',";
            }
        }

        if ($casts === []) {
            return '';
        }

        $body = implode("\n", $casts);

        return <<<PHP


    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
{$body}
        ];
    }
PHP;
    }

    private function panelItem(): string
    {
        $lines = ["            'id' => \$this->id,"];

        foreach ($this->fields as $field) {
            $lines[] = match ($field['type']) {
                'belongsTo' => sprintf(
                    "            '%s' => \$this->%s,\n            '%s' => \$this->relationLoaded('%s') && \$this->%s\n                ? ['id' => \$this->%s->id, 'name' => (string) \$this->%s->getAttribute(\$this->%s->getFillable()[0] ?? 'id')]\n                : null,",
                    $this->foreignKey($field['name']), $this->foreignKey($field['name']),
                    $field['name'], $field['name'], $field['name'],
                    $field['name'], $field['name'], $field['name'],
                ),
                // ISO 8601, never a formatted string: the client decides
                // how a date looks, in the reader's zone. See AGENTS.md.
                'date', 'datetime' => sprintf(
                    "            '%s' => \$this->%s?->toIso8601String(),",
                    $field['name'], $field['name'],
                ),
                default => sprintf(
                    "            '%s' => \$this->%s,",
                    $field['name'], $field['name'],
                ),
            };
        }

        $lines[] = "            'created_at' => \$this->created_at?->toIso8601String(),";
        $body = implode("\n", $lines);

        return <<<PHP


    /**
     * What the panel is allowed to see.
     *
     * Named rather than implied: a page that receives the whole model
     * receives every column added to it afterwards, whether or not
     * anybody meant it to.
     *
     * @return array<string, mixed>
     */
    public function toPanelItem(): array
    {
        return [
{$body}
        ];
    }
PHP;
    }

    /** The relation methods the model needs, or an empty string. */
    private function relations(): string
    {
        $relations = array_filter($this->fields, fn (array $f) => $f['type'] === 'belongsTo');

        if ($relations === []) {
            return '';
        }

        return "\n".implode("\n", array_map(function (array $f): string {
            $model = $this->relatedModel($f['name']);

            return <<<PHP

    /** @return BelongsTo<{$model}, \$this> */
    public function {$f['name']}(): BelongsTo
    {
        return \$this->belongsTo({$model}::class);
    }
PHP;
        }, $relations));
    }

    private function fillable(): string
    {
        return implode(', ', array_map(
            fn (array $f) => "'".($f['type'] === 'belongsTo' ? $this->foreignKey($f['name']) : $f['name'])."'",
            $this->fields,
        ));
    }

    private function rules(): string
    {
        $types = [
            'string' => "['required', 'string', 'max:255']",
            'text' => "['nullable', 'string']",
            // No length limit and no html rule: what survives is decided
            // by the allowlist in Sanitizer, not by validation.
            'richtext' => "['nullable', 'string']",
            // Two checks, not one: `mimes` reads the extension and
            // `image` reads what the file actually is. A .png that is a
            // PHP script passes the first and fails the second.
            'file' => "['nullable', 'file', 'max:'.config('files.max_kilobytes')]",
            'image' => "['nullable', 'image', 'mimes:'.implode(',', config('files.image_mimes')), 'max:'.config('files.max_kilobytes')]",
            'integer' => "['required', 'integer']",
            // Not `required`: a false checkbox is a legitimate answer,
            // and `required` rejects it along with the empty string.
            'boolean' => "['boolean']",
            'date' => "['nullable', 'date']",
            'datetime' => "['nullable', 'date']",
        ];

        return $this->lines(
            fn (array $f) => $f['type'] === 'enum'
                // Named after the field, not the model: two enum
                // fields on one entity would otherwise share a constant.
                ? sprintf(
                    "'%s' => ['%s', Rule::in(%s::%s_VALUES)],",
                    $f['name'],
                    $f['optional'] ? 'nullable' : 'required',
                    $this->studly,
                    Str::upper($f['name']),
                )
                : ($f['type'] === 'belongsTo'
                ? sprintf(
                    "'%s' => ['nullable', 'uuid', Rule::exists('%s', 'id')],",
                    $this->foreignKey($f['name']),
                    Str::snake(Str::plural($f['name'])),
                )
                : sprintf(
                    "'%s' => %s,",
                    $f['name'],
                    $f['optional']
                        ? str_replace("'required'", "'nullable'", $types[$f['type']])
                        : $types[$f['type']],
                )),
            indent: 12,
        );
    }

    private function factoryFields(): string
    {
        $types = [
            'string' => 'fake()->sentence(3)',
            'text' => 'fake()->paragraph()',
            'richtext' => "'<p>'.fake()->paragraph().'</p>'",
            'file' => 'null',
            'image' => 'null',
            'integer' => 'fake()->numberBetween(1, 100)',
            'boolean' => 'false',
            'date' => 'now()',
            'datetime' => 'now()',
        ];

        return $this->lines(
            fn (array $f) => $f['type'] === 'enum'
                ? "'{$f['name']}' => fake()->randomElement({$this->studly}::".Str::upper($f['name']).'_VALUES),'
                : ($f['type'] === 'belongsTo'
                // Null by default: a factory that always fills the key
                // makes "no category" untestable, and one that never
                // fills it never exercises the relation. The test sets
                // it when it needs it.
                ? "'{$this->foreignKey($f['name'])}' => null,"
                : sprintf(
                    "'%s' => %s,",
                    $f['name'],
                    $f['optional']
                        ? str_replace("'required'", "'nullable'", $types[$f['type']])
                        : $types[$f['type']],
                )),
            indent: 12,
        );
    }

    private function payloadFields(): string
    {
        $types = [
            'string' => "'Тестовое значение'",
            'text' => "'Более длинный текст'",
            'richtext' => "'<p>Абзац с <strong>акцентом</strong>.</p>'",
            'file' => "UploadedFile::fake()->create('документ.pdf', 40)",
            'image' => "UploadedFile::fake()->image('снимок.jpg')",
            'integer' => '42',
            'boolean' => 'true',
            'date' => "'2026-07-27'",
            'datetime' => "'2026-07-27T10:00:00Z'",
        ];

        return $this->lines(
            fn (array $f) => $f['type'] === 'enum'
                ? "'{$f['name']}' => '{$f['values'][0]}',"
                : ($f['type'] === 'belongsTo'
                ? "'{$this->foreignKey($f['name'])}' => null,"
                : "'{$f['name']}' => {$types[$f['type']]},"),
            indent: 8,
        );
    }

    private function tsFields(): string
    {
        $types = [
            'string' => 'string', 'text' => 'string | null',
            'richtext' => 'string | null',
            'file' => 'string | null', 'image' => 'string | null',
            'integer' => 'number', 'boolean' => 'boolean', 'date' => 'string | null',
            'datetime' => 'string | null',
        ];

        return $this->lines(
            // An optional column really can be null, and a type that
            // says otherwise makes the compiler agree with a lie.
            fn (array $f) => $f['type'] === 'enum'
                ? "{$f['name']}: string".($f['optional'] ? ' | null' : '').';'
                : ($f['type'] === 'belongsTo'
                // Both: the key is what the form submits, the object is
                // what the list shows.
                ? "{$this->foreignKey($f['name'])}: string | null;\n    {$f['name']}: { id: string; name: string } | null;"
                : "{$f['name']}: {$types[$f['type']]}".(
                    $f['optional'] && ! str_contains($types[$f['type']], 'null') ? ' | null' : ''
                ).';'),
            indent: 4,
        );
    }

    /**
     * The input each field gets, chosen by its type.
     *
     * It used to emit a plain text box for every type, which made a
     * `boolean` field impossible to submit: an untouched checkbox is an
     * empty string, Laravel's `boolean` rule accepts only true/false/1/0,
     * and creating the record failed validation with an error pointing at
     * a field the person never saw a control for.
     */
    private function formFields(): string
    {
        return $this->lines(fn (array $f) => match ($f['type']) {
            'boolean' => $this->checkboxField($f['name']),
            // A relation is picked from a list, and the list comes from
            // the controller — there is nothing sensible to type into a
            // free-text box for a foreign key.
            'belongsTo' => $this->relationField($f['name']),
            'enum' => $this->enumField($f['name']),
            'richtext' => $this->field($f['name'], <<<TSX
        <RichText
                                    id="{$f['name']}"
                                    name="{$f['name']}"
                                    defaultValue={record?.{$f['name']} ?? ''}
                                    data-test="{$this->snake}-{$f['name']}"
                                />
        TSX),
            'text' => $this->field($f['name'], <<<TSX
        <Textarea
                                    id="{$f['name']}"
                                    name="{$f['name']}"
                                    rows={4}
                                    defaultValue={String(record?.{$f['name']} ?? '')}
                                    data-test="{$this->snake}-{$f['name']}"
                                />
        TSX),
            'integer' => $this->field($f['name'], <<<TSX
        <Input
                                    id="{$f['name']}"
                                    name="{$f['name']}"
                                    type="number"
                                    inputMode="numeric"
                                    defaultValue={String(record?.{$f['name']} ?? '')}
                                    data-test="{$this->snake}-{$f['name']}"
                                />
        TSX),
            'date', 'datetime' => $this->field($f['name'], <<<TSX
        <Input
                                    id="{$f['name']}"
                                    name="{$f['name']}"
                                    type="date"
                                    defaultValue={String(record?.{$f['name']} ?? '')}
                                    data-test="{$this->snake}-{$f['name']}"
                                />
        TSX),
            default => $this->field($f['name'], <<<TSX
        <Input
                                    id="{$f['name']}"
                                    name="{$f['name']}"
                                    defaultValue={String(record?.{$f['name']} ?? '')}
                                    data-test="{$this->snake}-{$f['name']}"
                                />
        TSX),
        }, indent: 24);
    }

    /** Label, control, error — the shape every field shares. */
    private function field(string $name, string $control): string
    {
        return <<<TSX
        <div className="grid gap-1.5">
                                <Label htmlFor="{$name}">
                                    {t('{$this->snakePlural}.fields.{$name}')}
                                </Label>
                                {$control}
                                <InputError message={errors.{$name}} />
                            </div>
        TSX;
    }

    /**
     * A checkbox, plus a hidden "0" in front of it.
     *
     * An unchecked box sends nothing at all, which is indistinguishable
     * from the field not being on the form. The hidden input means the
     * server always receives a value, and the checkbox overrides it when
     * ticked — the same trick Laravel's own `@checked` blade helper
     * exists for.
     */
    private function checkboxField(string $name): string
    {
        return <<<TSX
        <label className="flex min-h-11 items-center gap-2">
                                <input type="hidden" name="{$name}" value="0" />
                                <Checkbox
                                    id="{$name}"
                                    name="{$name}"
                                    value="1"
                                    defaultChecked={Boolean(record?.{$name})}
                                    data-test="{$this->snake}-{$name}"
                                />
                                {t('{$this->snakePlural}.fields.{$name}')}
                                <InputError message={errors.{$name}} />
                            </label>
        TSX;
    }

    private function tableHeaders(): string
    {
        return $this->lines(
            // The header only exists from `sm` up — below it the row is
            // a card and there are no columns left to label.
            fn (array $f) => "<span className=\"flex-1\">{t('{$this->snakePlural}.fields.{$f['name']}')}</span>",
            indent: 28,
        );
    }

    /**
     * RULE(mobile): the base class is the phone — AGENTS.md.
     *
     * `flex-1` unprefixed makes every cell an equal share of one row,
     * which is a desktop idea: at 390px four equal shares are four
     * columns of about ninety pixels, and everything in them is cut off
     * inside the container's `overflow-hidden` — no wrapping, no
     * scrollbar, no trace anywhere. So the share only applies from `sm`
     * up, and below it each cell is simply a line of a card.
     *
     * The first field is the one a person looks for, so it carries the
     * weight on the narrow screen and gives it back on the wide one,
     * where the column header already says what it is.
     */
    private function tableCells(): string
    {
        $first = true;

        return $this->lines(function (array $f) use (&$first) {
            $class = $first
                ? 'truncate font-medium sm:flex-1 sm:font-normal'
                : 'truncate text-muted-foreground sm:flex-1';

            $first = false;

            $body = match (true) {
                // The label, not the stored word: `draft` on screen is a
                // database value leaking into the interface, and it
                // cannot be translated.
                $f['type'] === 'enum' => sprintf(
                    '{t(`%s.%s_options.${record.%s}`)}',
                    $this->snakePlural,
                    $f['name'],
                    $f['name'],
                ),
                // RULE(time): the server sends ISO 8601 and the client
                // formats it in the reader's zone — AGENTS.md. The
                // generated list printed the raw string, so a date came
                // out as 2026-07-28T00:00:00+00:00 in every language.
                in_array($f['type'], ['date', 'datetime'], true) => sprintf(
                    '{%s(record.%s, timezone, locale)}',
                    $f['type'] === 'date' ? 'formatDate' : 'formatDateTime',
                    $f['name'],
                ),
                // A row is one line, and headings or lists would wreck
                // it — while the markup printed as text is worse than
                // either. See lib/html.ts.
                $f['type'] === 'richtext' => "{excerpt(record.{$f['name']})}",
                // The label, not the key: a column of uuids tells the
                // reader nothing.
                $f['type'] === 'belongsTo' => "{record.{$f['name']}?.name ?? '—'}",
                default => "{String(record.{$f['name']} ?? '')}",
            };

            return "<span className=\"{$class}\">{$body}</span>";
        }, indent: 32);
    }

    private function langFields(): string
    {
        return $this->lines(
            fn (array $f) => "'{$f['name']}' => '".Str::headline($f['name'])."',",
            indent: 8,
        );
    }

    /**
     * @param  callable(array{name: string, type: string, optional: bool, values: array<int, string>}): string  $format
     */
    /**
     * Formats one line per field, indenting all but the first.
     *
     * The callback shape is spelled out so static analysis keeps it:
     * without it, a branch on `type === 'enum'` narrows the array to the
     * two keys it saw and forgets `values` exists.
     *
     * @param  callable(array{name: string, type: string, optional: bool, values: array<int, string>}): string  $format
     */
    private function lines(callable $format, int $indent): string
    {
        $pad = str_repeat(' ', $indent);

        return implode("\n", array_map(
            fn (array $field, int $i) => ($i === 0 ? '' : $pad).$format($field),
            $this->fields,
            array_keys($this->fields),
        ));
    }

    /**
     * RULE(access): a permission exists because code checks it — AGENTS.md.
     *
     * The generated policy asks for `Ability::VIEW_POSTS`. Without this
     * the constant does not exist and every generated slice is a fatal
     * error the moment anything loads the policy — `php artisan test`
     * included. The generator shipped that way and the acceptance
     * criterion in this very file ("tests pass with no edits") was never
     * true for anybody who ran it.
     *
     * RoleSeeder is idempotent and reads Ability::all(), so the admin role
     * picks the new permissions up on the next deploy without anyone
     * creating them by hand.
     */
    private function registerAbilities(): void
    {
        $path = base_path('app/Support/Access/Ability.php');
        $contents = File::get($path);
        $const = Str::upper($this->snakePlural);

        if (str_contains($contents, "VIEW_{$const} =")) {
            return;
        }

        $declarations = <<<PHP

    public const VIEW_{$const} = '{$this->snakePlural}.view';

    public const MANAGE_{$const} = '{$this->snakePlural}.manage';
PHP;

        $patched = preg_replace(
            '/(\n)(\s*\/\*\*\n\s+\* @return array<int, string>)/',
            $declarations."\n\$1\$2",
            $contents,
            1,
        );

        $patched = preg_replace(
            '/(\n\s+)(\];\n\s*\}\n\})/',
            "\$1    self::VIEW_{$const},\$1    self::MANAGE_{$const},\$1\$2",
            (string) $patched,
            1,
        );

        if ($patched === null || $patched === $contents) {
            $this->components->error(
                "Could not add VIEW_{$const} to Ability. Add both constants by hand, or the policy will not load."
            );

            return;
        }

        File::put($path, $patched);
        $this->components->task('app/Support/Access/Ability.php updated', fn () => true);
    }

    /**
     * RULE(reachable): a route nobody can reach is a feature nobody has
     * — AGENTS.md.
     *
     * A generated entity had a list page, a form, routes and tests, and
     * no way to get to any of it: the navigation is a static array and
     * the generator never touched it. The person who ran the command had
     * to know to type the URL.
     */
    private function registerNavigation(): void
    {
        $path = base_path('resources/js/components/app-sidebar.tsx');
        $contents = File::get($path);
        $const = Str::upper($this->snakePlural);

        if (str_contains($contents, "{$this->snakePlural}.title")) {
            return;
        }

        $entry = <<<TSX
            {
                title: '{$this->snakePlural}.title',
                href: {$this->snakePlural}Index(),
                icon: Boxes,
                ability: '{$this->snakePlural}.view',
            },
        TSX;

        $patched = preg_replace(
            '/(\n\];\n)/',
            "\n".$entry.'$1',
            $contents,
            1,
        );

        $patched = str_replace(
            "import type { NavItem } from '@/types';",
            "import { index as {$this->snakePlural}Index } from '@/routes/dash/{$this->kebabPlural}';\nimport type { NavItem } from '@/types';",
            (string) $patched,
        );

        // Boxes is the placeholder icon: an entity the generator knows
        // nothing about has no better one, and a wrong specific icon is
        // worse than an obviously generic one.
        //
        // Checked against the import block, not the whole file — the
        // entry added just above already contains the word, so looking
        // for it anywhere finds it and skips the import that makes it
        // work.
        $imports = Str::before($patched, "from 'lucide-react'");

        if (! str_contains($imports, 'Boxes')) {
            $patched = preg_replace(
                '/(import \\{\\n)(\\s+)/',
                "\$1\$2Boxes,\n\$2",
                $patched,
                1,
            );
        }

        if ($patched === null || $patched === $contents) {
            $this->components->warn(
                'Could not add a navigation entry. Add it to app-sidebar.tsx by hand, or the page has no way in.'
            );

            return;
        }

        File::put($path, $patched);
        $this->components->task('app-sidebar.tsx updated', fn () => true);
    }

    /**
     * RULE(api): every panel resource has an API — AGENTS.md.
     *
     * Written at the same time as the panel routes, not later. An API
     * that is added per entity, by hand, when somebody remembers, is an
     * API that covers whichever entities somebody remembered — and the
     * gaps are invisible until a client hits one.
     *
     * The controller is an empty subclass under `Api\V1`: same actions,
     * same policies, same form requests. It exists because Wayfinder
     * generates a helper per action, and an action reachable at two URLs
     * loses the `.form()` variant the auth flow uses.
     */
    private function registerApiRoutes(): void
    {
        $subclass = base_path("app/Http/Controllers/Api/V1/{$this->studly}Controller.php");

        File::ensureDirectoryExists(dirname($subclass));

        // Built by concatenation and interpolated as a whole: a
        // backslash immediately before `{` is the escape for a literal
        // brace, so `Dash\{$this->studly}Controller` writes
        // `Dash\{Probe}Controller` and the file does not parse.
        $parent = 'App\\Http\\Controllers\\Dash\\'.$this->studly.'Controller';

        if (! File::exists($subclass)) {
            File::put($subclass, <<<PHP
            <?php

            namespace App\Http\Controllers\Api\V1;

            use {$parent} as {$this->studly}Actions;

            /**
             * RULE(api): the same controller, the same policies — AGENTS.md.
             *
             * Empty on purpose. Everything is inherited; this class exists
             * so Wayfinder sees one controller per surface and keeps the
             * `.form()` helpers unambiguous.
             */
            class {$this->studly}Controller extends {$this->studly}Actions {}

            PHP);
        }

        $path = base_path('routes/api.php');
        $contents = File::get($path);

        if (str_contains($contents, "Api\\V1\\{$this->studly}Controller")) {
            return;
        }

        // The heredoc's first line carries the indentation of the
        // closing marker, so it is written flush and the rest lines up.
        $block = "\n    ".<<<PHP
        Route::get('{$this->kebabPlural}', [{$this->studly}Controller::class, 'index'])->name('api.{$this->snakePlural}.index');
            Route::post('{$this->kebabPlural}', [{$this->studly}Controller::class, 'store'])->name('api.{$this->snakePlural}.store');
            Route::put('{$this->kebabPlural}/{{$this->snake}}', [{$this->studly}Controller::class, 'update'])->name('api.{$this->snakePlural}.update');
            Route::delete('{$this->kebabPlural}/{{$this->snake}}', [{$this->studly}Controller::class, 'destroy'])->name('api.{$this->snakePlural}.destroy');
        PHP;

        $contents = str_replace(
            'use Illuminate\Http\Request;',
            // Concatenated, not interpolated. In a double-quoted string
            // `\{` is the documented escape for a literal brace, so
            // "...V1\{$this->studly}Controller" writes `{Probe}Controller`
            // and the routes file stops parsing.
            'use App\Http\Controllers\Api\V1\\'.$this->studly.'Controller;'."\n".'use Illuminate\Http\Request;',
            $contents,
        );

        // Same anchor discipline as the panel routes: the versioned group
        // has to close the file, and a silent no-match here would be an
        // API that answers 404 with nothing to show why.
        $patched = preg_replace('/(\n\}\);\n)$/', $block."\n});\n", rtrim($contents)."\n");

        if ($patched === null || $patched === rtrim($contents)."\n") {
            $this->components->error(
                'routes/api.php must end with the v1 group. Add the routes by hand.'
            );

            return;
        }

        File::put($path, $patched);
        $this->components->task('routes/api.php updated', fn () => true);
    }

    private function registerRoutes(): void
    {
        $path = base_path('routes/dash.php');
        $contents = File::get($path);

        if (str_contains($contents, "{$this->studly}Controller")) {
            return;
        }

        $block = <<<PHP

                Route::get('{$this->kebabPlural}', [{$this->studly}Controller::class, 'index'])->name('{$this->snakePlural}.index');
                Route::post('{$this->kebabPlural}', [{$this->studly}Controller::class, 'store'])->name('{$this->snakePlural}.store');
                Route::put('{$this->kebabPlural}/{{$this->snake}}', [{$this->studly}Controller::class, 'update'])->name('{$this->snakePlural}.update');
                Route::delete('{$this->kebabPlural}/{{$this->snake}}', [{$this->studly}Controller::class, 'destroy'])->name('{$this->snakePlural}.destroy');
        PHP;

        $contents = str_replace(
            'use Illuminate\Support\Facades\Route;',
            "use App\\Http\\Controllers\\Dash\\{$this->studly}Controller;\nuse Illuminate\\Support\\Facades\\Route;",
            $contents,
        );

        // The anchor is the closing of the `dash.` group, which has to stay
        // the last thing in the file. Anything appended after it — another
        // route, a conditional block — used to make this quietly match
        // nothing: the generator reported success and wrote no routes, and
        // the only sign was a 404 later. So it is checked.
        $patched = preg_replace('/(\n    \}\);\n)$/', $block."\n    });\n", rtrim($contents)."\n");

        if ($patched === null || $patched === rtrim($contents)."\n") {
            $this->components->error(
                'routes/dash.php must end with the dash group. Add the routes by hand, then move whatever follows the group into routes/web.php.'
            );

            return;
        }

        File::put($path, $patched);
        $this->components->task('routes/dash.php updated', fn () => true);
    }
}
