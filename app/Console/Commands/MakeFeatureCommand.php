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

    /** @var array<int, array{name: string, type: string}> */
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

        $this->components->info("Feature {$this->studly} generated. Run: php artisan migrate && php artisan test");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{name: string, type: string}>
     */
    private function parseFields(string $raw): array
    {
        $allowed = ['string', 'text', 'integer', 'boolean', 'date', 'datetime'];
        $fields = [];

        foreach (array_filter(array_map('trim', explode(',', $raw))) as $pair) {
            [$field, $type] = array_pad(explode(':', $pair, 2), 2, 'string');

            $field = Str::snake(trim($field));
            $type = trim($type);

            if (! preg_match('/^[a-z][a-z0-9_]*$/', $field)) {
                throw new RuntimeException("Bad field name: {$field}");
            }

            if (! in_array($type, $allowed, true)) {
                throw new RuntimeException("Unsupported type {$type}, allowed: ".implode(', ', $allowed));
            }

            $fields[] = ['name' => $field, 'type' => $type];
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
            fn (array $f) => sprintf($types[$f['type']], $f['name']),
            indent: 12,
        );
    }

    private function fillable(): string
    {
        return implode(', ', array_map(
            fn (array $f) => "'{$f['name']}'",
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
            fn (array $f) => "'{$f['name']}' => {$types[$f['type']]},",
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
            fn (array $f) => "'{$f['name']}' => {$types[$f['type']]},",
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
            fn (array $f) => "'{$f['name']}' => {$types[$f['type']]},",
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
            fn (array $f) => "{$f['name']}: {$types[$f['type']]};",
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
                                    {t('{$this->snakePlural}.{$name}')}
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
                                {t('{$this->snakePlural}.{$name}')}
                                <InputError message={errors.{$name}} />
                            </label>
        TSX;
    }

    private function tableHeaders(): string
    {
        return $this->lines(
            fn (array $f) => "<span className=\"flex-1\">{t('{$this->snakePlural}.{$f['name']}')}</span>",
            indent: 28,
        );
    }

    private function tableCells(): string
    {
        return $this->lines(
            fn (array $f) => "<span className=\"flex-1 truncate\">{String(record.{$f['name']} ?? '')}</span>",
            indent: 32,
        );
    }

    private function langFields(): string
    {
        return $this->lines(
            fn (array $f) => "'{$f['name']}' => '".Str::headline($f['name'])."',",
            indent: 4,
        );
    }

    /**
     * @param  callable(array{name: string, type: string}): string  $format
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
