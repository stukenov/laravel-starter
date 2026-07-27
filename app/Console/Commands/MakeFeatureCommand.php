<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

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

    /** @var array<int, array{name: string, type: string, values: array<int, string>}> */
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
        $this->registerAbilities();
        $this->registerNavigation();

        $this->components->info("Feature {$this->studly} generated. Run: php artisan migrate && php artisan test");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{name: string, type: string, values: array<int, string>}>
     */
    private function parseFields(string $raw): array
    {
        // `belongsTo` is the one relation the generator can own: it is
        // the side that carries the column. `hasMany` is its inverse and
        // needs no schema here; `belongsToMany` needs a pivot table and a
        // multi-select, and stays hand-written — see AGENTS.md.
        $allowed = ['string', 'text', 'integer', 'boolean', 'date', 'datetime', 'belongsTo', 'enum'];
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
                $type = 'enum';

                if (count($values) < 2) {
                    throw new RuntimeException(
                        "A enum field needs at least two values: {$field}:enum(draft,published)"
                    );
                }
            }

            $field = Str::snake(trim($field));
            $type = trim($type);

            if (! preg_match('/^[a-z][a-z0-9_]*$/', $field)) {
                throw new RuntimeException("Bad field name: {$field}");
            }

            if (! in_array($type, $allowed, true)) {
                throw new RuntimeException("Unsupported type {$type}, allowed: ".implode(', ', $allowed));
            }

            $fields[] = ['name' => $field, 'type' => $type, 'values' => $values];
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

        return 'database/migrations/'.now()->format('Y_m_d_His')."_create_{$this->snakePlural}_table.php";
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
                    "\$table->string('%s')->default('%s');",
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
                : sprintf($types[$f['type']], $f['name'])),
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

    /** @return array<int, array{name: string, type: string, values: array<int, string>}> */
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

    /** @return array<int, array{name: string, type: string, values: array<int, string>}> */
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
                    "'%s' => ['required', Rule::in(%s::%s_VALUES)],",
                    $f['name'],
                    $this->studly,
                    Str::upper($f['name']),
                )
                : ($f['type'] === 'belongsTo'
                ? sprintf(
                    "'%s' => ['nullable', 'uuid', Rule::exists('%s', 'id')],",
                    $this->foreignKey($f['name']),
                    Str::snake(Str::plural($f['name'])),
                )
                : "'{$f['name']}' => {$types[$f['type']]},"),
            indent: 12,
        );
    }

    private function factoryFields(): string
    {
        $types = [
            'string' => 'fake()->sentence(3)',
            'text' => 'fake()->paragraph()',
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
                : "'{$f['name']}' => {$types[$f['type']]},"),
            indent: 12,
        );
    }

    private function payloadFields(): string
    {
        $types = [
            'string' => "'Тестовое значение'",
            'text' => "'Более длинный текст'",
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
            'integer' => 'number', 'boolean' => 'boolean', 'date' => 'string | null',
            'datetime' => 'string | null',
        ];

        return $this->lines(
            fn (array $f) => $f['type'] === 'enum'
                ? "{$f['name']}: string;"
                : ($f['type'] === 'belongsTo'
                // Both: the key is what the form submits, the object is
                // what the list shows.
                ? "{$this->foreignKey($f['name'])}: string | null;\n    {$f['name']}: { id: string; name: string } | null;"
                : "{$f['name']}: {$types[$f['type']]};"),
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
            fn (array $f) => "<span className=\"flex-1\">{t('{$this->snakePlural}.fields.{$f['name']}')}</span>",
            indent: 28,
        );
    }

    private function tableCells(): string
    {
        return $this->lines(
            fn (array $f) => match (true) {
                // The label, not the stored word: `draft` on screen is a
                // database value leaking into the interface, and it
                // cannot be translated.
                $f['type'] === 'enum' => sprintf(
                    '<span className="flex-1 truncate">{t(`%s.%s_options.${record.%s}`)}</span>',
                    $this->snakePlural,
                    $f['name'],
                    $f['name'],
                ),
                // RULE(time): the server sends ISO 8601 and the client
                // formats it in the reader's zone — AGENTS.md. The
                // generated list printed the raw string, so a date came
                // out as 2026-07-28T00:00:00+00:00 in every language.
                in_array($f['type'], ['date', 'datetime'], true) => sprintf(
                    '<span className="flex-1 truncate">{%s(record.%s, timezone, locale)}</span>',
                    $f['type'] === 'date' ? 'formatDate' : 'formatDateTime',
                    $f['name'],
                ),
                default => null,
            } ?? ($f['type'] === 'belongsTo'
                // The label, not the key: a column of uuids tells the
                // reader nothing.
                ? "<span className=\"flex-1 truncate\">{record.{$f['name']}?.name ?? '—'}</span>"
                : "<span className=\"flex-1 truncate\">{String(record.{$f['name']} ?? '')}</span>"),
            indent: 32,
        );
    }

    private function langFields(): string
    {
        return $this->lines(
            fn (array $f) => "'{$f['name']}' => '".Str::headline($f['name'])."',",
            indent: 8,
        );
    }

    /**
     * @param  callable(array{name: string, type: string}): string  $format
     */
    /**
     * Formats one line per field, indenting all but the first.
     *
     * The callback shape is spelled out so static analysis keeps it:
     * without it, a branch on `type === 'enum'` narrows the array to the
     * two keys it saw and forgets `values` exists.
     *
     * @param  callable(array{name: string, type: string, values: array<int, string>}): string  $format
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
