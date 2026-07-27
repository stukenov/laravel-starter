<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Правильные данные
it('keeps only the auth features we asked for', function () {
    $enabled = config('fortify.features');

    expect(Features::enabled(Features::registration()))->toBeTrue()
        ->and(Features::enabled(Features::resetPasswords()))->toBeTrue()
        ->and(Features::enabled(Features::emailVerification()))->toBeTrue()
        ->and($enabled)->toHaveCount(3);
});

it('serves the home page from our own component', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('home'));
});

// Неправильные данные
it('has no two factor or passkey routes', function () {
    $forbidden = collect(Route::getRoutes())
        ->map(fn ($route) => $route->uri())
        ->filter(fn (string $uri) => str_contains($uri, 'two-factor')
            || str_contains($uri, 'passkey'))
        ->values();

    expect($forbidden)->toBeEmpty();
});

it('has no appearance route', function () {
    expect(Route::has('appearance.edit'))->toBeFalse();
});

it('has no dark mode logic in the layout', function () {
    $blade = file_get_contents(resource_path('views/app.blade.php'));

    expect($blade)->not->toContain('prefers-color-scheme')
        ->and($blade)->not->toContain("'dark'")
        ->and($blade)->not->toContain('appearance');
});

// Пустые данные
it('has no leftover demo or theme files', function () {
    $forbidden = [
        'resources/js/pages/welcome.tsx',
        'resources/js/pages/settings/appearance.tsx',
        'resources/js/pages/auth/two-factor-challenge.tsx',
        'resources/js/hooks/use-appearance.tsx',
        'resources/js/hooks/use-two-factor-auth.ts',
        'resources/js/components/appearance-tabs.tsx',
        'resources/js/components/ui/placeholder-pattern.tsx',
        'resources/js/components/manage-passkeys.tsx',
        'resources/js/components/manage-two-factor.tsx',
        'tests/Feature/ExampleTest.php',
        'tests/Unit/ExampleTest.php',
    ];

    foreach ($forbidden as $path) {
        expect(base_path($path))->not->toBeFile();
    }
});

it('has no dark variants left in our own components', function () {
    // components/ui/ намеренно исключены: это апстрим shadcn. Без класса
    // dark на <html> их варианты не срабатывают, а правки в них ломают
    // будущие обновления через `shadcn add`. См. AGENTS.md.
    $ours = array_merge(
        glob(resource_path('js/components/*.tsx')),
        glob(resource_path('js/layouts/*.tsx')),
        glob(resource_path('js/layouts/*/*.tsx')),
        glob(resource_path('js/pages/*.tsx')),
    );

    $offenders = [];

    foreach ($ours as $file) {
        if (str_contains(file_get_contents($file), 'dark:')) {
            $offenders[] = str_replace(base_path().'/', '', $file);
        }
    }

    expect($offenders)->toBeEmpty();
});
