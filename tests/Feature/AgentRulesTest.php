<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// Correct data
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
    // Two copies of the rules always drift; an agent then picks the
    // one that suits it.
    $claude = file_get_contents(base_path('CLAUDE.md'));
    $cursor = file_get_contents(base_path('.cursor/rules/project.mdc'));

    expect($claude)->toContain('@AGENTS.md')
        ->and(strlen($claude))->toBeLessThan(300)
        ->and($cursor)->toContain('AGENTS.md')
        ->and(strlen($cursor))->toBeLessThan(500);
});

it('keeps nested rule files short enough to be read', function () {
    // A long rules file goes unread — by humans and agents alike.
    foreach (['app/Models', 'database/migrations', 'resources/js', 'lang'] as $dir) {
        $path = base_path("{$dir}/AGENTS.md");

        expect($path)->toBeFile();
        expect(substr_count(file_get_contents($path), "\n"))->toBeLessThan(20, $dir);
    }
});

// Wrong data
it('lists what the project deliberately lacks', function () {
    // Asserting the absence of "SQLite" or "Redis" would be pointless:
    // both appear, in the prohibitions and in the "deliberately absent"
    // list. Real protection comes from DatabaseDriverGuardTest. What
    // matters here is that the exclusion list exists at all.
    $rules = file_get_contents(base_path('AGENTS.md'));

    expect($rules)->toContain('Deliberately absent')
        ->and($rules)->toContain('Redis')
        ->and($rules)->toContain('Ask before adding');
});

it('states the language rule for agents', function () {
    // Agent-facing files are English; answers to the owner are Russian.
    // Cyrillic costs roughly twice the tokens per character, and nobody
    // but agents reads these files.
    $rules = file_get_contents(base_path('AGENTS.md'));

    expect($rules)->toContain('think in English')
        ->and($rules)->toContain('answer in Russian');
});

it('keeps agent-facing files free of cyrillic', function () {
    // lang/ is the one place where Russian and Kazakh belong.
    $files = array_merge(
        [base_path('AGENTS.md'), base_path('CLAUDE.md'), base_path('zettel/README.md')],
        glob(base_path('*/AGENTS.md')),
        glob(base_path('*/*/AGENTS.md')),
    );

    $offenders = [];

    foreach (array_filter($files, 'is_file') as $file) {
        if (preg_match('/[а-яёА-ЯЁ]/u', file_get_contents($file))) {
            $offenders[] = str_replace(base_path().'/', '', $file);
        }
    }

    expect($offenders)->toBeEmpty();
});

/*
 * README is the only file somebody reads before running anything, and a
 * step missing from it is a step nobody takes.
 *
 * Both of these were missing and both produce a failure that looks like
 * something else. Without `user:admin` the panel answers 403 to everybody
 * and reads as a broken permission system. Without a build the backend
 * tests fail on `Vite manifest not found` and read as a broken suite.
 */

it('tells a new clone how to get its first administrator', function () {
    // Verified by running it: roles are seeded and permissions declared in
    // code, but nothing hands a role to a person.
    //
    // Scoped to both sections, not to the file. The first version asked
    // whether the string appeared anywhere in README, and it did — in the
    // development instructions — while the production recipe went without
    // it. The neighbouring test carries a comment about exactly this trap
    // and this one walked into it anyway.
    $readme = File::get(base_path('README.md'));

    $running = Str::between($readme, '## Running it', '## Tests');
    $deploying = Str::between($readme, '### First time', '### Three processes');

    expect($running)->toContain('php artisan user:admin')
        ->and($deploying)->toContain('php artisan user:admin');
});

it('seeds the roles on every deploy, not only the first', function () {
    // AGENTS.md promises a permission added in code appears without
    // anybody creating it by hand. The recipe seeded once, so it did not:
    // the new permission never reached the database and its section
    // answered 403 to everybody.
    $repeat = Str::between(File::get(base_path('README.md')), '### Every deploy after that', '### nginx');

    expect($repeat)->toContain('RoleSeeder');
});

it('says to close the renderer port', function () {
    // It binds 0.0.0.0 and Inertia's own server exposes an
    // unauthenticated /shutdown that calls process.exit().
    expect(File::get(base_path('README.md')))->toContain('13714')
        ->and(Str::between(File::get(base_path('README.md')), '**SSR.**', '### nginx'))
        ->toContain('deny 13714');
});

it('tells a new clone to build before the backend tests', function () {
    // Verified by moving public/build aside: five smoke tests became one.
    //
    // Scoped to the section by its real neighbour, not by '##'.
    // `Str::between` takes the LAST occurrence of the closing needle, so
    // '##' swallowed the whole rest of the file — including the deploy
    // section, where `npm run build:ssr` made this pass with the line
    // deleted. Caught by falsification and by nothing else.
    $readme = File::get(base_path('README.md'));

    $tests = Str::between($readme, '## Tests', '## Renaming');

    expect($tests)->toContain('npm run build')
        ->and($tests)->toContain('php artisan test');
});

it('names the same command the operations section does', function () {
    // Two documents drifting apart is how a starter ends up with an
    // install guide that does not install it.
    expect(File::get(base_path('AGENTS.md')))->toContain('php artisan user:admin');
});

it('has no orphaned rule comments in the code', function () {
    // A RULE(...) comment has to be anchored in AGENTS.md. A stale rule
    // is more dangerous than a missing one, because people believe it.
    $files = collect(File::allFiles(base_path()))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/vendor/'))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/node_modules/'))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/public/build/'))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/bootstrap/'))
        ->filter(fn ($file) => in_array($file->getExtension(), ['php', 'ts', 'tsx', 'css', 'xml']));

    $topics = [];

    foreach ($files as $file) {
        preg_match_all('/RULE\(([^)]+)\)/u', $file->getContents(), $matches);
        $topics = array_merge($topics, $matches[1]);
    }

    expect(array_unique($topics))->not->toBeEmpty();

    // toContain()'s second argument is another needle, not a failure
    // message — so collect the offenders by hand.
    $orphans = [];

    foreach ($files as $file) {
        $contents = $file->getContents();

        if (str_contains($contents, 'RULE(') && ! str_contains($contents, 'AGENTS.md')) {
            $orphans[] = str_replace(base_path().'/', '', $file->getPathname());
        }
    }

    expect($orphans)->toBeEmpty();
});

// Empty data
it('leaves no example tests behind', function () {
    expect(base_path('tests/Feature/ExampleTest.php'))->not->toBeFile()
        ->and(base_path('tests/Unit/ExampleTest.php'))->not->toBeFile();
});

it('creates no sqlite file on install', function () {
    // The starter ships a hook that creates database/database.sqlite.
    // We are PostgreSQL-only: the file is meaningless and tempting.
    $composer = file_get_contents(base_path('composer.json'));

    expect($composer)->not->toContain('database.sqlite')
        ->and(base_path('database/database.sqlite'))->not->toBeFile();
});
