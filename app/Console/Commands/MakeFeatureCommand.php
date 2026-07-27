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

        $this->components->info("Feature {$this->studly} generated. Run: php artisan migrate && php artisan test");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{name: string, type: string}>
     */
    private function parseFields(string $raw): array
    {
        $allowed = ['string', 'text', 'integer', 'boolean', 'date'];
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
        $timestamp = now()->format('Y_m_d_His');

        return [
            "database/migrations/{$timestamp}_create_{$this->snakePlural}_table.php" => 'migration',
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
            'date' => "\$table->dateTz('%s')->nullable();",
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
            'boolean' => "['required', 'boolean']",
            'date' => "['nullable', 'date']",
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
        ];

        return $this->lines(
            fn (array $f) => "{$f['name']}: {$types[$f['type']]};",
            indent: 4,
        );
    }

    private function formFields(): string
    {
        return $this->lines(fn (array $f) => <<<TSX
        <div className="grid gap-1.5">
                                <Label htmlFor="{$f['name']}">
                                    {t('{$this->snakePlural}.{$f['name']}')}
                                </Label>
                                <Input
                                    id="{$f['name']}"
                                    name="{$f['name']}"
                                    defaultValue={String(record?.{$f['name']} ?? '')}
                                    data-test="{$this->snake}-{$f['name']}"
                                />
                                <InputError message={errors.{$f['name']}} />
                            </div>
        TSX, indent: 24);
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

        $contents = preg_replace('/(\n    \}\);\n)$/', $block."\n    });\n", rtrim($contents)."\n");

        File::put($path, $contents);
        $this->components->task('routes/dash.php updated', fn () => true);
    }
}
