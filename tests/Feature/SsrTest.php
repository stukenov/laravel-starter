<?php

// Correct data
it('enables server side rendering', function () {
    expect(config('inertia.ssr.enabled'))->toBeTrue();
});

it('points at a bundle that the build actually produces', function () {
    // Имя задаёт плагин @inertiajs/vite: точка входа app.tsx → app.js.
    // Если конфиг ссылается на несуществующий файл, прод молча отдаёт
    // пустой контейнер вместо разметки — и никто этого не замечает.
    expect(config('inertia.ssr.bundle'))->toBe(base_path('bootstrap/ssr/app.js'));
});

it('has a built bundle on disk', function () {
    expect(config('inertia.ssr.bundle'))->toBeFile();
})->skip(
    fn () => ! file_exists(base_path('bootstrap/ssr/app.js')),
    'бандл не собран: npm run build:ssr',
);

// Wrong data
it('does not rely on a hand written ssr entry point', function () {
    // В Inertia 3 плагин сам оборачивает точку входа серверным кодом.
    // Наличие ssr.tsx означает, что кто-то принёс подход из Inertia 2.
    expect(resource_path('js/ssr.tsx'))->not->toBeFile()
        ->and(resource_path('js/ssr.ts'))->not->toBeFile();
});

// Empty data
it('has an ssr url to fall back on', function () {
    expect(config('inertia.ssr.url'))->not->toBeEmpty();
});
