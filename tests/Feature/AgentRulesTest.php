<?php

use Illuminate\Support\Facades\File;

// Правильные данные
it('has agent instructions in the project root', function () {
    expect(base_path('AGENTS.md'))->toBeFile()
        ->and(base_path('CLAUDE.md'))->toBeFile()
        ->and(base_path('.cursor/rules/project.mdc'))->toBeFile()
        ->and(base_path('zettel/README.md'))->toBeFile();
});

it('states every hard rule', function () {
    $rules = file_get_contents(base_path('AGENTS.md'));

    foreach (['PostgreSQL', 'zettel', 'UTC', 'Asia/Almaty', 'CSP', 'uuidv7', 'timestamptz'] as $needle) {
        expect($rules)->toContain($needle);
    }

    foreach (['ru', 'kk', 'en'] as $locale) {
        expect($rules)->toContain("`{$locale}`");
    }
});

it('keeps a single source of truth', function () {
    // Две копии правил неизбежно разъезжаются, и агент выбирает удобную.
    $claude = file_get_contents(base_path('CLAUDE.md'));
    $cursor = file_get_contents(base_path('.cursor/rules/project.mdc'));

    expect($claude)->toContain('@AGENTS.md')
        ->and(strlen($claude))->toBeLessThan(300)
        ->and($cursor)->toContain('AGENTS.md')
        ->and(strlen($cursor))->toBeLessThan(500);
});

it('keeps nested rule files short enough to be read', function () {
    // Длинный файл правил не читают — ни человек, ни агент.
    foreach (['app/Models', 'database/migrations', 'resources/js', 'lang'] as $dir) {
        $path = base_path("{$dir}/AGENTS.md");

        expect($path)->toBeFile();
        expect(substr_count(file_get_contents($path), "\n"))->toBeLessThan(20, $dir);
    }
});

// Неправильные данные
it('lists what the project deliberately lacks', function () {
    // Проверять отсутствие слов «SQLite» и «Redis» в тексте бессмысленно:
    // они там есть — в запретах и в списке «чего намеренно нет».
    // Реальную защиту даёт DatabaseDriverGuardTest, а здесь важно, что
    // список исключений вообще существует и агент его увидит.
    $rules = file_get_contents(base_path('AGENTS.md'));

    expect($rules)->toContain('Чего в проекте намеренно нет')
        ->and($rules)->toContain('Redis')
        ->and($rules)->toContain('Прежде чем добавить что-то из этого списка');
});

it('has no orphaned rule comments in the code', function () {
    // Комментарий ПРАВИЛО(...) обязан иметь опору в AGENTS.md.
    // Устаревшее правило опаснее отсутствующего: ему верят.
    $files = collect(File::allFiles(base_path()))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/vendor/'))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/node_modules/'))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/public/build/'))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/bootstrap/'))
        ->filter(fn ($file) => in_array($file->getExtension(), ['php', 'ts', 'tsx', 'css', 'xml']));

    $topics = [];

    foreach ($files as $file) {
        preg_match_all('/ПРАВИЛО\(([^)]+)\)/u', $file->getContents(), $matches);
        $topics = array_merge($topics, $matches[1]);
    }

    expect(array_unique($topics))->not->toBeEmpty();

    // Второй аргумент toContain() — это ещё одна искомая строка, а не
    // сообщение об ошибке. Поэтому имена нарушителей собираем сами.
    $orphans = [];

    foreach ($files as $file) {
        $contents = $file->getContents();

        if (str_contains($contents, 'ПРАВИЛО(') && ! str_contains($contents, 'AGENTS.md')) {
            $orphans[] = str_replace(base_path().'/', '', $file->getPathname());
        }
    }

    expect($orphans)->toBeEmpty();
});

// Пустые данные
it('leaves no example tests behind', function () {
    expect(base_path('tests/Feature/ExampleTest.php'))->not->toBeFile()
        ->and(base_path('tests/Unit/ExampleTest.php'))->not->toBeFile();
});

it('creates no sqlite file on install', function () {
    // Стартер приезжает со строкой, создающей database/database.sqlite.
    // У нас только Postgres — файл бессмыслен и провоцирует.
    $composer = file_get_contents(base_path('composer.json'));

    expect($composer)->not->toContain('database.sqlite')
        ->and(base_path('database/database.sqlite'))->not->toBeFile();
});
